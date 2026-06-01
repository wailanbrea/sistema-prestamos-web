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
use Tests\TestCase;

class MenuVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_null_visible_menus_sees_everything(): void
    {
        $user = $this->adminUser(null);

        $this->actingAs($user)->get('/prestamos')->assertOk();
        $this->actingAs($user)->get('/clientes')->assertOk();
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('loans.index'), false);
    }

    public function test_restricted_user_is_blocked_from_hidden_sections(): void
    {
        // Solo "Clientes" visible.
        $user = $this->adminUser(['clients.index']);

        // Permitido (visible).
        $this->actingAs($user)->get('/clientes')->assertOk();

        // Bloqueado (no visible) => 403.
        $this->actingAs($user)->get('/prestamos')->assertForbidden();
        $this->actingAs($user)->get('/cobros')->assertForbidden();

        // Oculto del menú lateral.
        $this->actingAs($user)
            ->get('/clientes')
            ->assertDontSee(route('loans.index'), false)
            ->assertSee(route('clients.index'), false);
    }

    public function test_management_routes_stay_accessible_as_safeguard(): void
    {
        // Aunque oculte Configuración y Roles, sigue pudiendo entrar (tiene los permisos).
        $user = $this->adminUser(['clients.index']);

        $this->actingAs($user)->get('/configuracion')->assertOk();
        $this->actingAs($user)->get('/roles')->assertOk();
        $this->actingAs($user)->get('/usuarios/crear')->assertOk();
    }

    /**
     * @param  array<int, string>|null  $visibleMenus
     */
    private function adminUser(?array $visibleMenus): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create(['name' => 'Empresa Test', 'status' => 'active']);
        CompanySetting::query()->create([
            'company_id' => $company->id,
            'currency' => 'RD$',
            'default_interest_rate' => 10,
            'default_late_fee_type' => 'none',
            'default_late_fee_value' => 0,
            'receipt_prefix' => 'REC',
            'loan_prefix' => 'PRE',
            'quote_prefix' => 'COT',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Admin Test',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'visible_menus' => $visibleMenus,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }
}
