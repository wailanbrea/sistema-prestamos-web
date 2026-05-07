<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Zone;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WebSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_main_authenticated_pages(): void
    {
        $user = $this->adminUser();
        $this->seedMinimalDomainData((int) $user->company_id);

        $routes = [
            '/dashboard',
            '/clientes',
            '/clientes/crear',
            '/cotizaciones',
            '/cotizaciones/crear',
            '/prestamos',
            '/prestamos/crear',
            '/cobros',
            '/cobros/crear',
            '/cobradores',
            '/cobradores/crear',
            '/rutas',
            '/rutas/mapa',
            '/rutas/crear',
            '/gastos',
            '/gastos/crear',
            '/caja',
            '/caja/crear',
            '/documentos',
            '/reportes',
            '/configuracion',
            '/usuarios/crear',
        ];

        foreach ($routes as $uri) {
            $this->actingAs($user)
                ->get($uri)
                ->assertOk();
        }
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create([
            'name' => 'Empresa Smoke Test',
            'status' => 'active',
        ]);

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
            'name' => 'Admin Smoke',
            'email' => 'admin-smoke@example.com',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }

    private function seedMinimalDomainData(int $companyId): void
    {
        Client::query()->create([
            'company_id' => $companyId,
            'code' => 'CLI-SMOKE',
            'full_name' => 'Cliente Smoke',
            'identification' => '001-0000000-1',
            'phone' => '809-555-0100',
            'address' => 'Santo Domingo',
            'latitude' => 18.4861,
            'longitude' => -69.9312,
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        Collector::query()->create([
            'company_id' => $companyId,
            'name' => 'Cobrador Smoke',
            'phone' => '809-555-0200',
            'commission_type' => 'none',
            'commission_value' => 0,
            'status' => 'active',
        ]);

        Zone::query()->create([
            'company_id' => $companyId,
            'name' => 'Zona Smoke',
        ]);

        ExpenseCategory::query()->create([
            'company_id' => $companyId,
            'name' => 'Gastos Operativos',
            'status' => 'active',
        ]);
    }
}
