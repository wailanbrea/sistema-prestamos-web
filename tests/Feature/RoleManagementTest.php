<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_custom_company_role(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('roles.store'), [
                'name' => 'Auxiliar de cobros',
                'permissions' => ['dashboard.view', 'clients.view'],
            ])
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', [
            'name' => 'Auxiliar de cobros',
            'company_id' => $user->company_id,
            'guard_name' => 'web',
        ]);

        $role = Role::query()->where('name', 'Auxiliar de cobros')->firstOrFail();
        $this->assertEqualsCanonicalizing(['dashboard.view', 'clients.view'], $role->permissions->pluck('name')->all());
    }

    public function test_admin_can_duplicate_and_delete_custom_role(): void
    {
        $user = $this->adminUser();
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->company_id);
        $role = Role::create(['name' => 'Base', 'guard_name' => 'web', 'company_id' => $user->company_id]);
        $role->syncPermissions(['dashboard.view']);

        $this->actingAs($user)
            ->post(route('roles.duplicate', $role))
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'Base (copia)', 'company_id' => $user->company_id]);

        $this->actingAs($user)
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_role_cannot_be_deleted(): void
    {
        $user = $this->adminUser();
        $admin = Role::query()->where('name', 'Administrador')->firstOrFail();

        $this->actingAs($user)
            ->delete(route('roles.destroy', $admin))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['id' => $admin->id]);
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $user = $this->adminUser();
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->company_id);
        $role = Role::create(['name' => 'Ocupado', 'guard_name' => 'web', 'company_id' => $user->company_id]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->delete(route('roles.destroy', $role))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create(['name' => 'Empresa Test', 'status' => 'active']);
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
}
