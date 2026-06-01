<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\ExpenseCategory;
use App\Models\Loan;
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
            '/roles',
            '/roles/crear',
        ];

        foreach ($routes as $uri) {
            $this->actingAs($user)
                ->get($uri)
                ->assertOk();
        }

        // Préstamo con cuotas para validar la pantalla de edición.
        $loan = $this->seedLoan((int) $user->company_id);
        $this->actingAs($user)->get(route('loans.edit', $loan))->assertOk();
    }

    public function test_operation_alerts_respect_user_permissions(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-alerts@example.com');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('operation-alerts-menu', false)
            ->assertSee(route('routes.map'), false)
            ->assertSee(route('payments.index'), false);

        $collector = $this->userWithRole('Cobrador', 'collector-alerts@example.com');

        $this->actingAs($collector)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('routes.map'), false)
            ->assertSee(route('payments.index'), false);

        $dashboardOnly = $this->userWithPermissions('dashboard-only-alerts@example.com', ['dashboard.view']);

        $this->actingAs($dashboardOnly)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('routes.map'), false)
            ->assertDontSee(route('payments.index'), false)
            ->assertSee('No tienes alertas disponibles para tu rol.');
    }

    private function adminUser(): User
    {
        return $this->userWithRole('Administrador', 'admin-smoke@example.com', 'Admin Smoke');
    }

    private function userWithRole(string $role, string $email, string $name = 'Usuario Smoke'): User
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
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(string $email, array $permissions): User
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
            'name' => 'Usuario Permisos Smoke',
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function seedLoan(int $companyId): Loan
    {
        $client = Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente Préstamo Smoke',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        $loan = Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'loan_number' => 'PRE-SMOKE-0001',
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

        $loan->installments()->create([
            'installment_number' => 1,
            'due_date' => '2026-06-01',
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'status' => 'pending',
        ]);

        return $loan;
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
