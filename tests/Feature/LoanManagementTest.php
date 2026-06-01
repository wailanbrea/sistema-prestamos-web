<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Loan;
use App\Models\LoanQuote;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    private function clientForCompany(int $companyId): Client
    {
        return Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente Préstamo',
            'status' => 'active',
            'risk_level' => 'low',
        ]);
    }
}
