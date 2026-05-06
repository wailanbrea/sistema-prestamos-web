<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_payment_and_generate_commission(): void
    {
        $user = $this->adminUser();
        [$loan, $collector] = $this->loanWithCollector((int) $user->company_id);

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'collector_id' => $collector->id,
                'payment_date' => '2026-05-06',
                'amount' => 1100,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'company_id' => $user->company_id,
            'loan_id' => $loan->id,
            'collector_id' => $collector->id,
            'amount' => 1100,
            'principal_paid' => 1000,
            'interest_paid' => 100,
            'new_balance' => 0,
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'paid',
            'remaining_balance' => 0,
        ]);
        $this->assertDatabaseHas('collector_commissions', [
            'collector_id' => $collector->id,
            'base_amount' => 1100,
            'commission_amount' => 55,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'company_id' => $user->company_id,
            'type' => 'payment_received',
            'direction' => 'in',
            'amount' => 1100,
        ]);
    }

    public function test_payment_cannot_exceed_pending_balance(): void
    {
        $user = $this->adminUser();
        [$loan] = $this->loanWithCollector((int) $user->company_id);

        $this->actingAs($user)
            ->from('/cobros/crear')
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'amount' => 1200,
                'payment_method' => 'cash',
            ])
            ->assertRedirect('/cobros/crear')
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_cannot_use_loan_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        [$foreignLoan] = $this->loanWithCollector((int) $otherCompany->id);

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $foreignLoan->id,
                'payment_date' => '2026-05-06',
                'amount' => 500,
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('loan_id');
    }

    public function test_user_cannot_view_payment_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        [$foreignLoan] = $this->loanWithCollector((int) $otherCompany->id);
        $payment = Payment::query()->create([
            'company_id' => $otherCompany->id,
            'loan_id' => $foreignLoan->id,
            'client_id' => $foreignLoan->client_id,
            'receipt_number' => 'REC-FOREIGN',
            'payment_date' => '2026-05-06',
            'amount' => 100,
            'payment_method' => 'cash',
            'previous_balance' => 1000,
            'new_balance' => 900,
        ]);

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertNotFound();
    }

    public function test_admin_can_cancel_payment_and_reverse_balances(): void
    {
        $user = $this->adminUser();
        [$loan, $collector] = $this->loanWithCollector((int) $user->company_id);

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'collector_id' => $collector->id,
                'payment_date' => '2026-05-06',
                'amount' => 1100,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $payment = Payment::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('payments.cancel', $payment), [
                'cancellation_reason' => 'Pago registrado por error operativo.',
            ])
            ->assertRedirect(route('payments.show', $payment));

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'cancelled',
            'cancelled_by' => $user->id,
            'cancellation_reason' => 'Pago registrado por error operativo.',
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'active',
            'paid_principal' => 0,
            'paid_interest' => 0,
            'remaining_balance' => 1000,
        ]);
        $this->assertDatabaseHas('loan_installments', [
            'loan_id' => $loan->id,
            'status' => 'pending',
            'paid_principal' => 0,
            'paid_interest' => 0,
            'total_paid' => 0,
        ]);
        $this->assertDatabaseHas('collector_commissions', [
            'payment_id' => $payment->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'company_id' => $user->company_id,
            'type' => 'adjustment',
            'direction' => 'out',
            'amount' => 1100,
        ]);
    }

    public function test_payment_cancellation_respects_company_setting(): void
    {
        $user = $this->adminUser();
        CompanySetting::query()->create([
            'company_id' => $user->company_id,
            'allow_payment_cancellation' => false,
        ]);
        [$loan, $collector] = $this->loanWithCollector((int) $user->company_id);

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'collector_id' => $collector->id,
                'payment_date' => '2026-05-06',
                'amount' => 1100,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $payment = Payment::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('payments.cancel', $payment), [
                'cancellation_reason' => 'Intento de anulación no permitida.',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'valid',
        ]);
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create([
            'name' => 'Empresa Test',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Admin Test',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }

    /**
     * @return array{0:Loan,1:Collector}
     */
    private function loanWithCollector(int $companyId): array
    {
        $client = Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente Cobro',
            'status' => 'active',
            'risk_level' => 'low',
        ]);
        $collector = Collector::query()->create([
            'company_id' => $companyId,
            'name' => 'Cobrador Test',
            'commission_type' => 'percentage',
            'commission_value' => 5,
            'status' => 'active',
        ]);
        $loan = Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'collector_id' => $collector->id,
            'loan_number' => 'PRE-TEST-'.fake()->unique()->numerify('####'),
            'principal_amount' => 1000,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 1,
            'installment_amount' => 1100,
            'total_interest' => 100,
            'total_amount' => 1100,
            'remaining_balance' => 1000,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-06-01',
            'status' => 'active',
        ]);

        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-06-01',
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'status' => 'pending',
        ]);

        return [$loan, $collector];
    }
}
