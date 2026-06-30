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
use Carbon\CarbonImmutable;
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
            'status' => 'late',
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

    public function test_interest_only_payment_does_not_reduce_balance(): void
    {
        $user = $this->adminUser();
        [$loan] = $this->loanWithCollector((int) $user->company_id);

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'allocation_mode' => 'interest_only',
                'amount' => 100,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'loan_id' => $loan->id,
            'principal_paid' => 0,
            'interest_paid' => 100,
            'new_balance' => 1000,
        ]);
        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'remaining_balance' => 1000, 'status' => 'active']);
    }

    public function test_principal_only_payment_reduces_balance_and_keeps_interest_due(): void
    {
        $user = $this->adminUser();
        [$loan] = $this->loanWithCollector((int) $user->company_id);

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'allocation_mode' => 'principal_only',
                'amount' => 400,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'loan_id' => $loan->id,
            'principal_paid' => 400,
            'interest_paid' => 0,
            'new_balance' => 600,
        ]);
        $this->assertDatabaseHas('loan_installments', [
            'loan_id' => $loan->id,
            'paid_principal' => 400,
            'paid_interest' => 0,
            'status' => 'partial',
        ]);
    }

    public function test_custom_payment_applies_explicit_installment_amount(): void
    {
        $user = $this->adminUser();
        [$loan] = $this->loanWithCollector((int) $user->company_id);
        $installment = $loan->installments()->firstOrFail();

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'allocation_mode' => 'custom',
                'payment_method' => 'cash',
                'allocations' => [
                    ['installment_id' => $installment->id, 'amount' => 600],
                ],
            ])
            ->assertRedirect();

        // 600 => 100 interés + 500 capital
        $this->assertDatabaseHas('payments', [
            'loan_id' => $loan->id,
            'amount' => 600,
            'principal_paid' => 500,
            'interest_paid' => 100,
            'new_balance' => 500,
        ]);
    }

    public function test_capital_prepayment_recalculates_remaining_installments(): void
    {
        $user = $this->adminUser();
        // Préstamo flat 10000 @10% en 10 cuotas mensuales (cuota 1100: 1000 capital + 100 interés).
        $loan = $this->multiInstallmentLoan((int) $user->company_id);
        $first = $loan->installments()->orderBy('installment_number')->first();

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'allocation_mode' => 'auto',
                'target_installment_id' => $first->id,
                'amount' => 2000,
                'excess_action' => 'prepayment',
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'loan_id' => $loan->id,
            'principal_paid' => 1900, // 1000 cuota + 900 abono
            'capital_prepaid' => 900,
            'change_given' => 0,
            'amount' => 2000,
        ]);
        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'remaining_balance' => 8100]);

        // 9 cuotas restantes re-amortizadas: capital 900, interés 90, cuota 990.
        $this->assertDatabaseHas('loan_installments', [
            'loan_id' => $loan->id,
            'installment_number' => 2,
            'principal_amount' => 900,
            'interest_amount' => 90,
            'installment_amount' => 990,
        ]);
    }

    public function test_interest_only_current_plus_capital_pays_current_interest_and_settles_principal(): void
    {
        $user = $this->adminUser();
        $loan = $this->interestOnlyLoan((int) $user->company_id);
        $currentInstallment = $loan->installments()->orderBy('installment_number')->firstOrFail();

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-06-15',
                'allocation_mode' => 'current_plus_capital',
                'target_installment_id' => $currentInstallment->id,
                'amount' => 5100,
                'capital_prepayment_amount' => 5000,
                'excess_action' => 'prepayment',
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'loan_id' => $loan->id,
            'amount' => 5100,
            'principal_paid' => 5000,
            'interest_paid' => 100,
            'capital_prepaid' => 5000,
            'new_balance' => 0,
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'remaining_balance' => 0,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('loan_installments', [
            'id' => $currentInstallment->id,
            'paid_interest' => 100,
            'paid_principal' => 0,
            'status' => 'paid',
        ]);
        $this->assertSame(
            0.0,
            (float) $loan->fresh()->installments()
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->sum('installment_amount')
        );
    }

    public function test_admin_can_update_financial_terms_after_payments_and_recalculate_future_installments(): void
    {
        $user = $this->adminUser();
        $loan = $this->multiInstallmentLoan((int) $user->company_id);
        $first = $loan->installments()->orderBy('installment_number')->firstOrFail();

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-06-01',
                'allocation_mode' => 'auto',
                'target_installment_id' => $first->id,
                'amount' => 1100,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->put(route('loans.update', $loan), [
                'collector_id' => null,
                'currency' => 'RD$',
                'principal_amount' => 9000,
                'interest_rate' => 10,
                'interest_type' => 'fixed',
                'payment_frequency' => 'monthly',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 5,
                'late_fee_type' => 'none',
                'late_fee_value' => 0,
                'start_date' => '2026-05-01',
                'first_payment_date' => '2026-06-01',
                'allows_capital_prepayment' => 1,
            ])
            ->assertRedirect(route('loans.show', $loan));

        $this->assertDatabaseHas('loan_installments', [
            'id' => $first->id,
            'installment_number' => 1,
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'total_paid' => 1100,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('loan_installments', [
            'loan_id' => $loan->id,
            'installment_number' => 2,
            'principal_amount' => 2000,
            'interest_amount' => 200,
            'installment_amount' => 2200,
            'total_paid' => 0,
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'principal_amount' => 9000,
            'remaining_balance' => 8000,
            'total_interest' => 900,
            'total_amount' => 9900,
        ]);
        $this->assertSame(5, $loan->fresh()->installments()->count());
    }

    public function test_overpayment_can_be_returned_as_change(): void
    {
        $user = $this->adminUser();
        $loan = $this->multiInstallmentLoan((int) $user->company_id);
        $first = $loan->installments()->orderBy('installment_number')->first();

        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'allocation_mode' => 'auto',
                'target_installment_id' => $first->id,
                'amount' => 1600,
                'excess_action' => 'change',
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'loan_id' => $loan->id,
            'amount' => 1100,
            'change_given' => 500,
            'capital_prepaid' => 0,
        ]);
        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'remaining_balance' => 9000]);
    }

    public function test_partial_payment_rejected_when_not_allowed(): void
    {
        $user = $this->adminUser();
        CompanySetting::query()->create([
            'company_id' => $user->company_id,
            'allow_partial_payments' => false,
        ]);
        [$loan] = $this->loanWithCollector((int) $user->company_id);

        // Pago parcial (500 < cuota 1100) => la cuota quedaría parcial => rechazado.
        $this->actingAs($user)
            ->from('/cobros/crear')
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'amount' => 500,
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);

        // Pago completo de la cuota (1100) => aceptado.
        $this->actingAs($user)
            ->post('/cobros', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'amount' => 1100,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();
        $this->assertDatabaseCount('payments', 1);
    }

    private function multiInstallmentLoan(int $companyId): Loan
    {
        $client = Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente Abono',
            'status' => 'active',
            'risk_level' => 'low',
        ]);
        $loan = Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'loan_number' => 'PRE-ABONO-'.fake()->unique()->numerify('####'),
            'principal_amount' => 10000,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 10,
            'installment_amount' => 1100,
            'total_interest' => 1000,
            'total_amount' => 11000,
            'remaining_balance' => 10000,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'allows_capital_prepayment' => true,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-06-01',
            'status' => 'active',
        ]);
        $due = CarbonImmutable::parse('2026-06-01');
        for ($n = 1; $n <= 10; $n++) {
            $loan->installments()->create([
                'installment_number' => $n,
                'due_date' => $due->toDateString(),
                'principal_amount' => 1000,
                'interest_amount' => 100,
                'installment_amount' => 1100,
                'status' => 'pending',
            ]);
            $due = $due->addMonthNoOverflow();
        }

        return $loan;
    }

    private function interestOnlyLoan(int $companyId): Loan
    {
        $client = Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente Interes Only',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        $loan = Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'loan_number' => 'PRE-INTERES-'.fake()->unique()->numerify('####'),
            'principal_amount' => 5000,
            'interest_rate' => 2,
            'interest_type' => 'fixed',
            'payment_frequency' => 'weekly',
            'calculation_method' => 'interest_only',
            'term_quantity' => 4,
            'installment_amount' => 100,
            'total_interest' => 400,
            'total_amount' => 5400,
            'remaining_balance' => 5000,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'allows_capital_prepayment' => true,
            'start_date' => '2026-06-01',
            'first_payment_date' => '2026-06-08',
            'status' => 'active',
        ]);

        $due = CarbonImmutable::parse('2026-06-08');
        for ($n = 1; $n <= 4; $n++) {
            $loan->installments()->create([
                'installment_number' => $n,
                'due_date' => $due->toDateString(),
                'principal_amount' => $n === 4 ? 5000 : 0,
                'interest_amount' => 100,
                'installment_amount' => $n === 4 ? 5100 : 100,
                'status' => 'pending',
            ]);
            $due = $due->addWeek();
        }

        return $loan;
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
