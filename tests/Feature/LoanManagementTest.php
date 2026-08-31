<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanQuote;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_loan_index(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->get('/prestamos')
            ->assertOk()
            ->assertSee('Préstamos')
            ->assertSee('Activo');
    }

    public function test_loan_index_hides_paid_loans_until_show_all_is_selected(): void
    {
        $user = $this->adminUser();
        $client = $this->clientForCompany((int) $user->company_id);

        $activeLoan = $this->loanForClient((int) $user->company_id, (int) $client->id, 'PRE-ACTIVA-001', 'active');
        $paidLoan = $this->loanForClient((int) $user->company_id, (int) $client->id, 'PRE-PAGADA-001', 'paid');

        $this->actingAs($user)
            ->get('/prestamos')
            ->assertOk()
            ->assertSee($activeLoan->loan_number)
            ->assertDontSee($paidLoan->loan_number);

        $this->actingAs($user)
            ->get('/prestamos?show_all=1')
            ->assertOk()
            ->assertSee($activeLoan->loan_number)
            ->assertSee($paidLoan->loan_number);
    }

    public function test_admin_can_search_loans_by_client_name(): void
    {
        $user = $this->adminUser();
        $matchedClient = $this->namedClientForCompany((int) $user->company_id, 'Maria Buscada');
        $otherClient = $this->namedClientForCompany((int) $user->company_id, 'Pedro Oculto');

        $this->loanForClient((int) $user->company_id, (int) $matchedClient->id, 'PRE-SEARCH-001');
        $this->loanForClient((int) $user->company_id, (int) $otherClient->id, 'PRE-SEARCH-002');

        $this->actingAs($user)
            ->get('/prestamos?q=Maria')
            ->assertOk()
            ->assertSee('Maria Buscada')
            ->assertSee('PRE-SEARCH-001')
            // Pedro Oculto sigue apareciendo en el dropdown de filtro por
            // cliente, por eso solo se valida que su prestamo no se liste.
            ->assertDontSee('PRE-SEARCH-002');
    }

    public function test_admin_can_view_loan_create_form(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->get('/prestamos/crear')
            ->assertOk()
            ->assertSee('Nuevo préstamo')
            ->assertSee('Interés fijo');
    }

    public function test_admin_can_create_loan_from_scratch(): void
    {
        $user = $this->adminUser();
        $client = $this->clientForCompany((int) $user->company_id);

        $this->actingAs($user)
            ->post('/prestamos', [
                'client_id' => $client->id,
                'currency' => 'RD$',
                'principal_amount' => 10000,
                'interest_rate' => 10,
                'interest_type' => 'fixed',
                'payment_frequency' => 'monthly',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 10,
                'late_fee_type' => 'none',
                'late_fee_value' => 0,
                'start_date' => '2026-05-01',
                'first_payment_date' => '2026-06-01',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('loans', [
            'company_id' => $user->company_id,
            'client_id' => $client->id,
            'principal_amount' => 10000,
            'installment_amount' => 1100,
            'remaining_balance' => 10000,
            'status' => 'active',
        ]);

        $this->assertDatabaseCount('loan_installments', 10);
        $this->assertDatabaseHas('cash_movements', [
            'company_id' => $user->company_id,
            'type' => 'loan_disbursement',
            'direction' => 'out',
            'amount' => 10000,
        ]);
    }

    public function test_admin_can_convert_quote_to_loan(): void
    {
        $user = $this->adminUser();
        $client = $this->clientForCompany((int) $user->company_id);
        $quote = LoanQuote::query()->create([
            'company_id' => $user->company_id,
            'client_id' => $client->id,
            'amount' => 5000,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'weekly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 5,
            'installment_amount' => 1100,
            'total_interest' => 500,
            'total_to_pay' => 5500,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-05-08',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post('/prestamos', [
                'quote_id' => $quote->id,
                'client_id' => $client->id,
                'currency' => 'RD$',
                'late_fee_type' => 'fixed',
                'late_fee_value' => 100,
                'start_date' => '2026-05-01',
                'first_payment_date' => '2026-05-08',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('loan_quotes', [
            'id' => $quote->id,
            'status' => 'converted',
        ]);
        $this->assertDatabaseHas('loans', [
            'quote_id' => $quote->id,
            'principal_amount' => 5000,
            'payment_frequency' => 'weekly',
        ]);
        $this->assertDatabaseCount('loan_installments', 5);
    }

    public function test_user_cannot_create_loan_for_client_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignClient = $this->clientForCompany((int) $otherCompany->id);

        $this->actingAs($user)
            ->post('/prestamos', [
                'client_id' => $foreignClient->id,
                'currency' => 'RD$',
                'principal_amount' => 10000,
                'interest_rate' => 10,
                'interest_type' => 'fixed',
                'payment_frequency' => 'monthly',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 10,
                'late_fee_type' => 'none',
                'start_date' => '2026-05-01',
                'first_payment_date' => '2026-06-01',
            ])
            ->assertSessionHasErrors('client_id');
    }

    public function test_loan_number_uses_configured_prefix(): void
    {
        $user = $this->adminUser();
        CompanySetting::query()->create([
            'company_id' => $user->company_id,
            'loan_prefix' => 'TST',
        ]);
        $client = $this->clientForCompany((int) $user->company_id);

        $this->actingAs($user)->post('/prestamos', [
            'client_id' => $client->id,
            'currency' => 'RD$',
            'principal_amount' => 5000,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 5,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-06-01',
        ])->assertRedirect();

        $this->assertStringStartsWith('TST-', Loan::query()->firstOrFail()->loan_number);
    }

    public function test_loan_requires_approval_when_enabled(): void
    {
        $user = $this->adminUser();
        CompanySetting::query()->create([
            'company_id' => $user->company_id,
            'require_approval_for_loans' => true,
        ]);
        $client = $this->clientForCompany((int) $user->company_id);

        $this->actingAs($user)->post('/prestamos', [
            'client_id' => $client->id,
            'currency' => 'RD$',
            'principal_amount' => 8000,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 8,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-06-01',
        ])->assertRedirect();

        $loan = Loan::query()->firstOrFail();
        $this->assertSame('pending', $loan->status);
        // No hay desembolso todavía.
        $this->assertDatabaseMissing('cash_movements', ['type' => 'loan_disbursement', 'reference_id' => $loan->id]);

        // Aprobar => activo + desembolso.
        $this->actingAs($user)->post(route('loans.approve', $loan))->assertRedirect();
        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'active']);
        $this->assertDatabaseHas('cash_movements', [
            'type' => 'loan_disbursement',
            'reference_id' => $loan->id,
            'amount' => 8000,
        ]);
    }

    public function test_admin_can_delete_loan_with_payments_and_restore_it(): void
    {
        $user = $this->adminUser();
        $client = $this->clientForCompany((int) $user->company_id);
        $loan = $this->loanForClient((int) $user->company_id, (int) $client->id, 'PRE-DELETE-001');
        $installment = LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-06-01',
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'paid_principal' => 100,
            'paid_interest' => 10,
            'total_paid' => 110,
            'status' => 'partial',
        ]);
        $payment = Payment::query()->create([
            'company_id' => $user->company_id,
            'loan_id' => $loan->id,
            'client_id' => $client->id,
            'receipt_number' => 'REC-DELETE-001',
            'payment_date' => '2026-06-02',
            'amount' => 110,
            'principal_paid' => 100,
            'interest_paid' => 10,
            'previous_balance' => 1000,
            'new_balance' => 900,
            'status' => 'valid',
        ]);

        $this->actingAs($user)
            ->delete(route('loans.destroy', $loan))
            ->assertRedirect(route('loans.index'));

        $this->assertSoftDeleted('loans', ['id' => $loan->id]);
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('loan_installments', ['id' => $installment->id, 'loan_id' => $loan->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'loan_id' => $loan->id, 'status' => 'valid']);
        $this->assertDatabaseMissing('cash_movements', [
            'type' => 'adjustment',
            'reference_type' => Loan::class,
            'reference_id' => $loan->id,
        ]);

        $this->actingAs($user)
            ->get(route('loans.trashed'))
            ->assertOk()
            ->assertSee($loan->loan_number)
            ->assertSee('Recuperar');

        $this->actingAs($user)
            ->post(route('loans.restore', $loan->id))
            ->assertRedirect(route('loans.show', $loan->id));

        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('loan_installments', ['id' => $installment->id, 'loan_id' => $loan->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'loan_id' => $loan->id, 'status' => 'valid']);
    }

    public function test_supervisor_cannot_delete_loan(): void
    {
        $admin = $this->adminUser();
        $supervisor = $this->userWithRole((int) $admin->company_id, 'Supervisor');
        $client = $this->clientForCompany((int) $admin->company_id);
        $loan = $this->loanForClient((int) $admin->company_id, (int) $client->id, 'PRE-DELETE-002');

        $this->actingAs($supervisor)
            ->delete(route('loans.destroy', $loan))
            ->assertForbidden();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'deleted_at' => null,
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

    private function userWithRole(int $companyId, string $role): User
    {
        $user = User::query()->create([
            'company_id' => $companyId,
            'name' => $role.' Test',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);
        $user->assignRole(Role::query()->where('name', $role)->firstOrFail());

        return $user;
    }

    private function clientForCompany(int $companyId): Client
    {
        return Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente Préstamo',
            'status' => 'active',
            'risk_level' => 'low',
        ]);
    }

    private function namedClientForCompany(int $companyId, string $fullName): Client
    {
        return Client::query()->create([
            'company_id' => $companyId,
            'full_name' => $fullName,
            'status' => 'active',
            'risk_level' => 'low',
        ]);
    }

    private function loanForClient(int $companyId, int $clientId, string $loanNumber, string $status = 'active'): Loan
    {
        return Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $clientId,
            'loan_number' => $loanNumber,
            'principal_amount' => 1000,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 1,
            'installment_amount' => 1100,
            'total_interest' => 100,
            'total_amount' => 1100,
            'remaining_balance' => $status === 'paid' ? 0 : 1000,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-06-01',
            'status' => $status,
        ]);
    }
}
