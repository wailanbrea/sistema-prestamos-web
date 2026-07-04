<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_company_settings(): void
    {
        $user = $this->adminUser();
        CompanySetting::query()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->put('/configuracion', [
                'name' => 'Prestamista Actualizado',
                'plan' => 'full',
                'rnc' => '123456789',
                'phone' => '809-555-0000',
                'email' => 'info@example.com',
                'address' => 'Santo Domingo',
                'currency' => 'RD$',
                'default_loan_currency' => 'RD$',
                'default_account_payable_currency' => 'RD$',
                'enabled_loan_calculation_methods' => ['flat_interest', 'fixed_installment'],
                'enabled_payment_allocation_modes' => ['auto', 'principal_and_interest'],
                'default_interest_rate' => 12.5,
                'default_late_fee_type' => 'daily_fixed',
                'default_late_fee_value' => 50,
                'receipt_prefix' => 'REC',
                'loan_prefix' => 'PRE',
                'quote_prefix' => 'COT',
                'allow_partial_payments' => 1,
                'allow_payment_cancellation' => 0,
                'require_approval_for_loans' => 1,
                'exclude_sundays_for_daily_loans' => 1,
                'route_visit_radius_meters' => 90,
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('companies', [
            'id' => $user->company_id,
            'name' => 'Prestamista Actualizado',
            'rnc' => '123456789',
        ]);
        $this->assertDatabaseHas('company_settings', [
            'company_id' => $user->company_id,
            'default_late_fee_type' => 'daily_fixed',
            'default_late_fee_value' => 50,
            'allow_payment_cancellation' => 0,
            'exclude_sundays_for_daily_loans' => 1,
            'route_visit_radius_meters' => 90,
        ]);
    }

    public function test_non_owner_admin_cannot_change_plan(): void
    {
        $user = $this->adminUser();
        $user->company()->update(['plan' => 'prestamista']);
        CompanySetting::query()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->put('/configuracion', $this->settingsPayload(['plan' => 'full']))
            ->assertRedirect(route('settings.index'));

        // El plan se preserva: un admin normal no puede cambiar la licencia.
        $this->assertDatabaseHas('companies', [
            'id' => $user->company_id,
            'plan' => 'prestamista',
        ]);
    }

    public function test_system_owner_can_change_plan(): void
    {
        $user = $this->adminUser('wailandkey@gmail.com', owner: true);
        $user->company()->update(['plan' => 'prestamista']);
        CompanySetting::query()->create(['company_id' => $user->company_id]);

        $this->assertTrue($user->isSystemOwner());

        $this->actingAs($user)
            ->put('/configuracion', $this->settingsPayload(['plan' => 'full']))
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('companies', [
            'id' => $user->company_id,
            'plan' => 'full',
        ]);
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post('/usuarios', [
                'name' => 'Supervisor Test',
                'email' => 'supervisor@example.com',
                'phone' => '809-555-2222',
                'password' => 'Password1234',
                'password_confirmation' => 'Password1234',
                'status' => 'active',
                'role' => 'Supervisor',
            ])
            ->assertRedirect(route('settings.index'));

        $created = User::query()->where('email', 'supervisor@example.com')->firstOrFail();

        $this->assertSame($user->company_id, $created->company_id);
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->company_id);
        $this->assertTrue($created->hasRole('Supervisor'));
    }

    public function test_admin_can_update_role_screen_permissions(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->where('name', 'Supervisor')->firstOrFail();

        $this->actingAs($user)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertSee('Roles y permisos');

        $this->actingAs($user)
            ->put(route('roles.update', $role), [
                'permissions' => ['dashboard.view', 'clients.view'],
            ])
            ->assertRedirect(route('roles.index'));

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('dashboard.view'));
        $this->assertTrue($role->hasPermissionTo('clients.view'));
        $this->assertFalse($role->hasPermissionTo('loans.create'));
    }

    public function test_admin_cannot_block_own_user(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->from(route('users.edit', $user))
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => 'blocked',
                'role' => 'Administrador',
            ])
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_cannot_edit_user_from_other_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignUser = User::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Usuario Ajeno',
            'email' => 'ajeno@example.com',
            'password' => Hash::make('Password1234'),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('users.edit', $foreignUser))
            ->assertNotFound();
    }

    private function adminUser(?string $email = null, bool $owner = false): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create([
            'name' => 'Empresa Test',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Admin Test',
            'email' => $email ?? fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        if ($owner) {
            // is_system_owner no es fillable a propósito: se marca explícitamente.
            $user->forceFill(['is_system_owner' => true])->save();
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Prestamista Test',
            'rnc' => '123456789',
            'phone' => '809-555-0000',
            'email' => 'info@example.com',
            'address' => 'Santo Domingo',
            'currency' => 'RD$',
            'default_loan_currency' => 'RD$',
            'default_account_payable_currency' => 'RD$',
            'enabled_loan_calculation_methods' => ['flat_interest', 'fixed_installment'],
            'enabled_payment_allocation_modes' => ['auto', 'principal_and_interest'],
            'default_interest_rate' => 12.5,
            'default_late_fee_type' => 'daily_fixed',
            'default_late_fee_value' => 50,
            'receipt_prefix' => 'REC',
            'loan_prefix' => 'PRE',
            'quote_prefix' => 'COT',
            'allow_partial_payments' => 1,
            'allow_payment_cancellation' => 0,
            'require_approval_for_loans' => 1,
            'exclude_sundays_for_daily_loans' => 1,
            'route_visit_radius_meters' => 90,
        ], $overrides);
    }
}
