<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoanQuoteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_quote_index(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->get('/cotizaciones')
            ->assertOk()
            ->assertSee('Cotizaciones')
            ->assertSee('Pendiente');
    }

    public function test_admin_can_view_quote_create_form(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->get('/cotizaciones/crear')
            ->assertOk()
            ->assertSee('Nueva cotización')
            ->assertSee('Interés fijo');
    }

    public function test_admin_can_create_loan_quote(): void
    {
        $user = $this->adminUser();
        $client = Client::query()->create([
            'company_id' => $user->company_id,
            'full_name' => 'Cliente Cotización',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        $this->actingAs($user)
            ->post('/cotizaciones', [
                'client_id' => $client->id,
                'amount' => 10000,
                'interest_rate' => 10,
                'interest_type' => 'fixed',
                'payment_frequency' => 'monthly',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 10,
                'start_date' => '2026-05-01',
                'first_payment_date' => '2026-06-01',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('loan_quotes', [
            'company_id' => $user->company_id,
            'client_id' => $client->id,
            'amount' => 10000,
            'installment_amount' => 1100,
            'total_interest' => 1000,
            'total_to_pay' => 11000,
        ]);
    }

    public function test_quote_cannot_use_client_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignClient = Client::query()->create([
            'company_id' => $otherCompany->id,
            'full_name' => 'Cliente Ajeno',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        $this->actingAs($user)
            ->post('/cotizaciones', [
                'client_id' => $foreignClient->id,
                'amount' => 10000,
                'interest_rate' => 10,
                'interest_type' => 'fixed',
                'payment_frequency' => 'monthly',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 10,
            ])
            ->assertSessionHasErrors('client_id');
    }

    public function test_user_cannot_view_quote_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);

        $quote = \App\Models\LoanQuote::query()->create([
            'company_id' => $otherCompany->id,
            'amount' => 5000,
            'interest_rate' => 5,
            'interest_type' => 'fixed',
            'payment_frequency' => 'weekly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 5,
            'installment_amount' => 1050,
            'total_interest' => 250,
            'total_to_pay' => 5250,
        ]);

        $this->actingAs($user)
            ->get(route('loan-quotes.show', $quote))
            ->assertNotFound();
    }

    public function test_admin_can_delete_pending_quote(): void
    {
        $user = $this->adminUser();
        $quote = \App\Models\LoanQuote::query()->create([
            'company_id' => $user->company_id,
            'amount' => 5000,
            'interest_rate' => 5,
            'interest_type' => 'fixed',
            'payment_frequency' => 'weekly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 5,
            'installment_amount' => 1050,
            'total_interest' => 250,
            'total_to_pay' => 5250,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->delete(route('loan-quotes.destroy', $quote))
            ->assertRedirect(route('loan-quotes.index'));

        $this->assertDatabaseMissing('loan_quotes', [
            'id' => $quote->id,
        ]);
    }

    public function test_admin_cannot_delete_converted_quote(): void
    {
        $user = $this->adminUser();
        $quote = \App\Models\LoanQuote::query()->create([
            'company_id' => $user->company_id,
            'amount' => 5000,
            'interest_rate' => 5,
            'interest_type' => 'fixed',
            'payment_frequency' => 'weekly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 5,
            'installment_amount' => 1050,
            'total_interest' => 250,
            'total_to_pay' => 5250,
            'status' => 'converted',
        ]);

        $this->actingAs($user)
            ->delete(route('loan-quotes.destroy', $quote))
            ->assertRedirect();

        $this->assertDatabaseHas('loan_quotes', [
            'id' => $quote->id,
            'status' => 'converted',
        ]);
    }

    public function test_quote_delete_action_requires_delete_permission(): void
    {
        $user = $this->quoteUserWithoutDeletePermission();
        $quote = \App\Models\LoanQuote::query()->create([
            'company_id' => $user->company_id,
            'amount' => 5000,
            'interest_rate' => 5,
            'interest_type' => 'fixed',
            'payment_frequency' => 'weekly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 5,
            'installment_amount' => 1050,
            'total_interest' => 250,
            'total_to_pay' => 5250,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('loan-quotes.index'))
            ->assertOk()
            ->assertSee('Sin permiso')
            ->assertDontSee('name="_method" value="DELETE"', false);

        $this->actingAs($user)
            ->delete(route('loan-quotes.destroy', $quote))
            ->assertForbidden();

        $this->assertDatabaseHas('loan_quotes', [
            'id' => $quote->id,
        ]);
    }

    public function test_quote_conversion_requires_convert_permission(): void
    {
        $user = $this->quoteUserWithoutDeletePermission();
        $client = Client::query()->create([
            'company_id' => $user->company_id,
            'full_name' => 'Cliente Convertir',
            'status' => 'active',
            'risk_level' => 'low',
        ]);
        $quote = \App\Models\LoanQuote::query()->create([
            'company_id' => $user->company_id,
            'client_id' => $client->id,
            'amount' => 5000,
            'interest_rate' => 5,
            'interest_type' => 'fixed',
            'payment_frequency' => 'weekly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 5,
            'installment_amount' => 1050,
            'total_interest' => 250,
            'total_to_pay' => 5250,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('loans.create', ['quote_id' => $quote->id]))
            ->assertForbidden();
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
            'email' => 'admin-quote@example.com',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }

    private function quoteUserWithoutDeletePermission(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create([
            'name' => 'Empresa Cotizador',
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'Cotizador limitado',
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(Permission::query()->whereIn('name', ['dashboard.view', 'quotes.manage', 'loans.create'])->pluck('name')->all());

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Cotizador Limitado',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole($role);

        return $user;
    }
}
