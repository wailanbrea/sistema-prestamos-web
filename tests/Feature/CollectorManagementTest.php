<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CollectorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_collector_with_percentage_commission(): void
    {
        $user = $this->adminUser();
        $collectorUser = $this->userForCompany((int) $user->company_id);

        $this->actingAs($user)
            ->post('/cobradores', [
                'user_id' => $collectorUser->id,
                'name' => 'Carlos Cobrador',
                'phone' => '809-555-7000',
                'commission_type' => 'percentage',
                'commission_value' => 8.5,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collectors', [
            'company_id' => $user->company_id,
            'user_id' => $collectorUser->id,
            'name' => 'Carlos Cobrador',
            'commission_type' => 'percentage',
            'commission_value' => 8.5,
            'status' => 'active',
        ]);
    }

    public function test_collector_cannot_be_linked_to_user_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignUser = $this->userForCompany((int) $otherCompany->id);

        $this->actingAs($user)
            ->post('/cobradores', [
                'user_id' => $foreignUser->id,
                'name' => 'Cobrador Ajeno',
                'commission_type' => 'none',
                'commission_value' => 0,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_admin_users_are_not_available_as_collector_linked_users(): void
    {
        $user = $this->adminUser();
        $collectorUser = $this->userForCompany((int) $user->company_id);

        $this->actingAs($user)
            ->get(route('collectors.create'))
            ->assertOk()
            ->assertDontSee('<option value="'.$user->id.'"', false)
            ->assertSee('<option value="'.$collectorUser->id.'"', false)
            ->assertSee($collectorUser->email);
    }

    public function test_collector_cannot_be_linked_to_admin_user(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post('/cobradores', [
                'access_mode' => 'existing',
                'user_id' => $user->id,
                'name' => 'Cobrador Admin Invalido',
                'commission_type' => 'none',
                'commission_value' => 0,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_none_commission_is_normalized_to_zero(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post('/cobradores', [
                'name' => 'Sin Comisión',
                'commission_type' => 'none',
                'commission_value' => 99,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collectors', [
            'company_id' => $user->company_id,
            'name' => 'Sin Comisión',
            'commission_type' => 'none',
            'commission_value' => 0,
        ]);
    }

    public function test_collector_form_includes_loan_assignment_search(): void
    {
        $user = $this->adminUser();
        $loan = $this->loanForCompany((int) $user->company_id, 'PRE-BUSCAR-001');

        $this->actingAs($user)
            ->get(route('collectors.create'))
            ->assertOk()
            ->assertSee('Buscar prestamo')
            ->assertSee('data-collector-loan-search', false)
            ->assertSee($loan->loan_number);
    }

    public function test_edit_collector_form_shows_linked_user_access_fields(): void
    {
        $user = $this->adminUser();
        $collectorUser = $this->userForCompany((int) $user->company_id);
        $collector = Collector::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $collectorUser->id,
            'name' => 'Cobrador Con Acceso',
            'commission_type' => 'none',
            'commission_value' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('collectors.edit', $collector))
            ->assertOk()
            ->assertSee('Usuario actual: '.$collectorUser->name)
            ->assertSee($collectorUser->email)
            ->assertSee('Nueva clave temporal')
            ->assertDontSee('Password123!');
    }

    public function test_admin_can_update_collector_linked_user_credentials(): void
    {
        $user = $this->adminUser();
        $collectorUser = $this->userForCompany((int) $user->company_id);
        $collector = Collector::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $collectorUser->id,
            'name' => 'Cobrador Credenciales',
            'commission_type' => 'none',
            'commission_value' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->put(route('collectors.update', $collector), [
                'user_id' => $collectorUser->id,
                'user_name' => 'Cobrador App Actualizado',
                'user_email' => 'cobrador.app.actualizado@example.com',
                'user_password' => 'NuevaClave123!',
                'name' => 'Cobrador Credenciales',
                'commission_type' => 'none',
                'commission_value' => 0,
                'commission_base' => 'payment_total',
                'status' => 'active',
                'loan_ids' => [],
            ])
            ->assertRedirect(route('collectors.show', $collector))
            ->assertSessionHas('collector_credentials.password', 'NuevaClave123!');

        $collectorUser->refresh();

        $this->assertSame('Cobrador App Actualizado', $collectorUser->name);
        $this->assertSame('cobrador.app.actualizado@example.com', $collectorUser->email);
        $this->assertTrue(Hash::check('NuevaClave123!', $collectorUser->password));
        $this->assertNotSame('NuevaClave123!', $collectorUser->password);
    }

    public function test_admin_can_assign_visible_loans_when_creating_collector(): void
    {
        $user = $this->adminUser();
        $loan = $this->loanForCompany((int) $user->company_id, 'PRE-COL-001');
        $otherLoan = $this->loanForCompany((int) $user->company_id, 'PRE-COL-002');

        $this->actingAs($user)
            ->post('/cobradores', [
                'name' => 'Cobrador Cartera',
                'commission_type' => 'none',
                'commission_value' => 0,
                'status' => 'active',
                'loan_ids' => [$loan->id],
            ])
            ->assertRedirect();

        $collector = Collector::query()->where('name', 'Cobrador Cartera')->firstOrFail();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'collector_id' => $collector->id,
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $otherLoan->id,
            'collector_id' => null,
        ]);
    }

    public function test_admin_can_sync_visible_loans_when_updating_collector(): void
    {
        $user = $this->adminUser();
        $collector = Collector::query()->create([
            'company_id' => $user->company_id,
            'name' => 'Cobrador Existente',
            'commission_type' => 'none',
            'commission_value' => 0,
            'status' => 'active',
        ]);
        $keptLoan = $this->loanForCompany((int) $user->company_id, 'PRE-COL-003', (int) $collector->id);
        $removedLoan = $this->loanForCompany((int) $user->company_id, 'PRE-COL-004', (int) $collector->id);
        $addedLoan = $this->loanForCompany((int) $user->company_id, 'PRE-COL-005');

        $this->actingAs($user)
            ->put(route('collectors.update', $collector), [
                'name' => 'Cobrador Existente',
                'commission_type' => 'none',
                'commission_value' => 0,
                'commission_base' => 'payment_total',
                'status' => 'active',
                'loan_ids' => [$keptLoan->id, $addedLoan->id],
            ])
            ->assertRedirect(route('collectors.show', $collector));

        $this->assertDatabaseHas('loans', ['id' => $keptLoan->id, 'collector_id' => $collector->id]);
        $this->assertDatabaseHas('loans', ['id' => $addedLoan->id, 'collector_id' => $collector->id]);
        $this->assertDatabaseHas('loans', ['id' => $removedLoan->id, 'collector_id' => null]);
    }

    public function test_user_cannot_view_collector_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignCollector = Collector::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Cobrador Ajeno',
            'commission_type' => 'none',
            'commission_value' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('collectors.show', $foreignCollector))
            ->assertNotFound();
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

    private function userForCompany(int $companyId): User
    {
        return User::query()->create([
            'company_id' => $companyId,
            'name' => 'Usuario Cobrador',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
    }

    private function loanForCompany(int $companyId, string $loanNumber, ?int $collectorId = null): Loan
    {
        $client = Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente '.$loanNumber,
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        return Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'collector_id' => $collectorId,
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
            'remaining_balance' => 1000,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-06-01',
            'status' => 'active',
        ]);
    }
}
