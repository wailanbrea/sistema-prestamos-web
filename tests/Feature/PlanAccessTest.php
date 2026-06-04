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

class PlanAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_prestamista_plan_only_exposes_loan_menus(): void
    {
        $user = $this->adminUser('prestamista');

        // Permitido: flujo de préstamos.
        $this->actingAs($user)->get('/clientes')->assertOk();
        $this->actingAs($user)->get('/prestamos')->assertOk();
        $this->actingAs($user)->get('/cobros')->assertOk();
        $this->actingAs($user)->get('/cotizaciones')->assertOk();

        // Permitido: reportes/informes también en el plan básico.
        $this->actingAs($user)->get('/reportes')->assertOk();

        // Bloqueado: operación / análisis.
        $this->actingAs($user)->get('/cobradores')->assertForbidden();
        $this->actingAs($user)->get('/rutas')->assertForbidden();
        $this->actingAs($user)->get('/gastos')->assertForbidden();
        $this->actingAs($user)->get('/caja')->assertForbidden();

        // Configuración, usuarios y roles SÍ se gestionan en el plan básico.
        $this->actingAs($user)->get('/configuracion')->assertOk();
        $this->actingAs($user)->get('/roles')->assertOk();
        $this->actingAs($user)->get('/usuarios/crear')->assertOk();

        // Pero NO se ve nada de cobradores: ni el menú ni la métrica del dashboard.
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('loans.index'), false)
            ->assertSee(route('roles.index'), false)
            ->assertSee(route('reports.index'), false)
            ->assertDontSee(route('collectors.index'), false)
            ->assertDontSee('Cobradores activos');
    }

    public function test_full_plan_exposes_everything_including_users(): void
    {
        $user = $this->adminUser('full');

        $this->actingAs($user)->get('/cobradores')->assertOk();
        $this->actingAs($user)->get('/rutas')->assertOk();
        $this->actingAs($user)->get('/reportes')->assertOk();
        $this->actingAs($user)->get('/roles')->assertOk();
        $this->actingAs($user)->get('/usuarios/crear')->assertOk();
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('collectors.index'), false)
            ->assertSee(route('roles.index'), false)
            ->assertSee('Cobradores activos');
    }

    private function adminUser(string $plan): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create(['name' => 'Empresa Test', 'status' => 'active', 'plan' => $plan]);
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
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }
}
