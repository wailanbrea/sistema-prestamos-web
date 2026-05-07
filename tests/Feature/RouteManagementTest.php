<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\Route as LendingRoute;
use App\Models\User;
use App\Models\Zone;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RouteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_zone_and_route_with_clients(): void
    {
        $user = $this->adminUser();
        $zone = Zone::query()->create(['company_id' => $user->company_id, 'name' => 'Zona Norte']);
        $collector = Collector::query()->create([
            'company_id' => $user->company_id,
            'name' => 'Cobrador Ruta',
            'commission_type' => 'none',
            'commission_value' => 0,
            'status' => 'active',
        ]);
        $clientA = $this->clientForCompany((int) $user->company_id, 'Cliente A');
        $clientB = $this->clientForCompany((int) $user->company_id, 'Cliente B');

        $this->actingAs($user)
            ->post('/rutas', [
                'zone_id' => $zone->id,
                'collector_id' => $collector->id,
                'name' => 'Ruta Matutina',
                'description' => 'Ruta de prueba',
                'status' => 'active',
                'client_ids' => [$clientA->id, $clientB->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('routes', [
            'company_id' => $user->company_id,
            'zone_id' => $zone->id,
            'collector_id' => $collector->id,
            'name' => 'Ruta Matutina',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('route_clients', [
            'client_id' => $clientA->id,
            'order_number' => 1,
        ]);
        $this->assertDatabaseHas('route_clients', [
            'client_id' => $clientB->id,
            'order_number' => 2,
        ]);
    }

    public function test_route_cannot_attach_client_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignClient = $this->clientForCompany((int) $otherCompany->id, 'Cliente Ajeno');

        $this->actingAs($user)
            ->post('/rutas', [
                'name' => 'Ruta Inválida',
                'status' => 'active',
                'client_ids' => [$foreignClient->id],
            ])
            ->assertSessionHasErrors('client_ids.0');
    }

    public function test_route_cannot_use_collector_or_zone_from_another_company(): void
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
        $foreignZone = Zone::query()->create(['company_id' => $otherCompany->id, 'name' => 'Zona Ajena']);

        $this->actingAs($user)
            ->post('/rutas', [
                'zone_id' => $foreignZone->id,
                'collector_id' => $foreignCollector->id,
                'name' => 'Ruta Inválida',
                'status' => 'active',
            ])
            ->assertSessionHasErrors(['zone_id', 'collector_id']);
    }

    public function test_user_cannot_view_route_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignRoute = LendingRoute::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Ruta Ajena',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('routes.show', $foreignRoute))
            ->assertNotFound();
    }

    public function test_admin_can_view_collector_tracking_page(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->get(route('routes.tracking'))
            ->assertOk()
            ->assertSee('Seguimiento de cobradores');
    }

    public function test_zone_name_is_unique_per_company(): void
    {
        $user = $this->adminUser();

        Zone::query()->create(['company_id' => $user->company_id, 'name' => 'Centro']);

        $this->actingAs($user)
            ->post('/rutas/zonas', [
                'name' => 'Centro',
            ])
            ->assertSessionHasErrors('name');
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

    private function clientForCompany(int $companyId, string $name): Client
    {
        return Client::query()->create([
            'company_id' => $companyId,
            'full_name' => $name,
            'status' => 'active',
            'risk_level' => 'low',
        ]);
    }
}
