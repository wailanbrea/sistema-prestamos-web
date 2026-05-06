<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Collector;
use App\Models\Company;
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
}
