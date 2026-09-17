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
use Tests\TestCase;

class DemoAccountRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_user_can_view_pages(): void
    {
        $demoUser = $this->createDemoUser();

        $this->actingAs($demoUser)
            ->get('/dashboard')
            ->assertOk();

        $this->actingAs($demoUser)
            ->get('/clientes')
            ->assertOk();
    }

    public function test_demo_user_cannot_create_client(): void
    {
        $demoUser = $this->createDemoUser();

        $response = $this->actingAs($demoUser)
            ->from('/clientes/crear')
            ->post('/clientes', [
                'code' => 'CLI-DEMO-TEST',
                'full_name' => 'Cliente Demo Intento',
                'identification' => '001-9999999-1',
                'phone' => '809-555-9999',
                'address' => 'Calle Falsa 123',
                'status' => 'active',
                'risk_level' => 'low',
            ]);

        $response->assertRedirect('/clientes/crear');
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('clients', [
            'code' => 'CLI-DEMO-TEST',
        ]);
    }

    public function test_demo_user_cannot_update_client(): void
    {
        $demoUser = $this->createDemoUser();
        $client = $this->createClient($demoUser->company_id);

        $response = $this->actingAs($demoUser)
            ->from("/clientes/{$client->id}/editar")
            ->put("/clientes/{$client->id}", [
                'full_name' => 'Nombre Modificado',
                'phone' => '809-555-8888',
                'address' => 'Direccion Modificada',
                'status' => 'active',
                'risk_level' => 'low',
            ]);

        $response->assertRedirect("/clientes/{$client->id}/editar");
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'full_name' => 'Cliente Original',
        ]);
    }

    public function test_demo_user_cannot_delete_client(): void
    {
        $demoUser = $this->createDemoUser();
        $client = $this->createClient($demoUser->company_id);

        $response = $this->actingAs($demoUser)
            ->from('/clientes')
            ->delete("/clientes/{$client->id}");

        $response->assertRedirect('/clientes');
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'deleted_at' => null,
        ]);
    }

    public function test_demo_user_blocked_on_json_request_with_403(): void
    {
        $demoUser = $this->createDemoUser();

        $response = $this->actingAs($demoUser)
            ->postJson('/clientes', [
                'code' => 'CLI-JSON',
                'full_name' => 'Cliente JSON',
            ]);

        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
    }

    public function test_demo_user_can_logout(): void
    {
        $demoUser = $this->createDemoUser();

        $this->actingAs($demoUser)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_regular_user_is_not_blocked_from_creating_client(): void
    {
        $regularUser = $this->createRegularUser();

        $response = $this->actingAs($regularUser)
            ->post('/clientes', [
                'code' => 'CLI-OK-001',
                'full_name' => 'Cliente Permitido',
                'identification' => '001-1111111-1',
                'phone' => '809-555-1111',
                'address' => 'Calle Correcta 456',
                'status' => 'active',
                'risk_level' => 'low',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', [
            'company_id' => $regularUser->company_id,
            'code' => 'CLI-OK-001',
        ]);
    }

    private function createDemoUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create([
            'name' => 'Empresa Demo Test',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Usuario Demo Test',
            'email' => 'demo-test@example.com',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'is_demo' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }

    private function createRegularUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create([
            'name' => 'Empresa Normal Test',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Usuario Normal Test',
            'email' => 'normal-test@example.com',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'is_demo' => false,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }

    private function createClient(int $companyId): Client
    {
        return Client::query()->create([
            'company_id' => $companyId,
            'code' => 'CLI-TEST-001',
            'full_name' => 'Cliente Original',
            'identification' => '001-2222222-2',
            'phone' => '809-555-2222',
            'address' => 'Calle Principal 789',
            'status' => 'active',
            'risk_level' => 'low',
        ]);
    }
}
