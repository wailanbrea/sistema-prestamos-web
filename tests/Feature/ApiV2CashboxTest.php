<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiV2CashboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_caja_can_register_and_list_expenses(): void
    {
        [$user, $company] = $this->userWithRole('Caja/Contabilidad');
        $category = ExpenseCategory::query()->create(['company_id' => $company->id, 'name' => 'Combustible']);
        $token = $this->loginToken($user);

        $this->withToken($token)
            ->postJson('/api/v2/cashbox/expenses', [
                'category_id' => $category->id,
                'description' => 'Gasolina ruta centro',
                'amount' => 450,
                'expense_date' => '2026-06-04',
                'payment_method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount', 450)
            ->assertJsonPath('data.category', 'Combustible');

        // El gasto registra además su movimiento de caja (salida).
        $this->assertDatabaseHas('cash_movements', [
            'company_id' => $company->id,
            'type' => 'expense',
            'direction' => 'out',
            'amount' => 450,
        ]);

        $this->withToken($token)
            ->getJson('/api/v2/cashbox/expenses')
            ->assertOk()
            ->assertJsonPath('data.0.description', 'Gasolina ruta centro');
    }

    public function test_caja_can_read_cash_movements_and_summary(): void
    {
        [$user] = $this->userWithRole('Caja/Contabilidad');
        $token = $this->loginToken($user);

        $this->withToken($token)->getJson('/api/v2/cashbox/movements')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->withToken($token)
            ->getJson('/api/v2/cashbox/summary')
            ->assertOk()
            ->assertJsonStructure(['data' => ['total_in', 'total_out', 'balance']]);
    }

    public function test_collector_is_forbidden_from_cashbox(): void
    {
        [$admin, $company] = $this->userWithRole('Administrador');
        $collector = $this->userWithRoleInCompany('Cobrador', $company);
        $token = $this->loginToken($collector);

        foreach (['/api/v2/cashbox/expenses', '/api/v2/cashbox/movements', '/api/v2/cashbox/summary'] as $endpoint) {
            $this->withToken($token)->getJson($endpoint)->assertForbidden();
        }
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function userWithRole(string $role): array
    {
        $this->seed(RolePermissionSeeder::class);
        $company = Company::query()->create(['name' => 'Empresa Caja API '.uniqid(), 'status' => 'active']);

        return [$this->userWithRoleInCompany($role, $company), $company];
    }

    private function userWithRoleInCompany(string $role, Company $company): User
    {
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => $role.' Demo',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole($role);

        return $user;
    }

    private function loginToken(User $user): string
    {
        return (string) $this->postJson('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk()->json('data.access_token');
    }
}
