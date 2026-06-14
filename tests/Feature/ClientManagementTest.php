<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientRegistrationLink;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_client_for_own_company(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post('/clientes', [
                'code' => 'CLI-001',
                'full_name' => 'Juan Perez',
                'identification' => '001-0000000-1',
                'phone' => '809-555-0001',
                'email' => 'juan@example.com',
                'address' => 'Av. Abraham Lincoln, Santo Domingo',
                'latitude' => 18.4691000,
                'longitude' => -69.9390000,
                'monthly_income' => 45000,
                'status' => 'active',
                'risk_level' => 'low',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'company_id' => $user->company_id,
            'code' => 'CLI-001',
            'full_name' => 'Juan Perez',
            'address' => 'Av. Abraham Lincoln, Santo Domingo',
            'latitude' => 18.4691000,
            'longitude' => -69.9390000,
        ]);
    }

    public function test_client_address_is_required(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post('/clientes', [
                'full_name' => 'Cliente Sin Direccion',
                'status' => 'active',
                'risk_level' => 'low',
            ])
            ->assertSessionHasErrors('address');
    }

    public function test_client_code_is_unique_inside_company(): void
    {
        $user = $this->adminUser();

        Client::query()->create([
            'company_id' => $user->company_id,
            'code' => 'CLI-001',
            'full_name' => 'Cliente Existente',
            'address' => 'Direccion existente',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        $this->actingAs($user)
            ->post('/clientes', [
                'code' => 'CLI-001',
                'full_name' => 'Cliente Duplicado',
                'address' => 'Direccion duplicada',
                'status' => 'active',
                'risk_level' => 'low',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_user_cannot_view_client_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignClient = Client::query()->create([
            'company_id' => $otherCompany->id,
            'full_name' => 'Cliente Ajeno',
            'address' => 'Direccion ajena',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        $this->actingAs($user)
            ->get(route('clients.show', $foreignClient))
            ->assertNotFound();
    }

    public function test_completed_registration_link_redirects_to_success_instead_of_returning_gone(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa Registro',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'company_id' => $company->id,
            'full_name' => 'Cliente Registrado',
            'address' => 'Direccion registrada',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        $link = ClientRegistrationLink::query()->create([
            'company_id' => $company->id,
            'token' => str_repeat('a', 64),
            'used_at' => now(),
            'used_client_id' => $client->id,
        ]);

        $this->get(route('client-registration.show', $link->token))
            ->assertRedirect(route('client-registration.success', $link->token));
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
            'email' => 'admin-test@example.com',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }
}
