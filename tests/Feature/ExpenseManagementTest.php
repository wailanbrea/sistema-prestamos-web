<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_expense_and_cash_movement(): void
    {
        $user = $this->adminUser();
        $category = ExpenseCategory::query()->create([
            'company_id' => $user->company_id,
            'name' => 'Combustible',
        ]);

        $this->actingAs($user)
            ->post('/gastos', [
                'category_id' => $category->id,
                'description' => 'Gasolina de cobradores',
                'amount' => 2500,
                'expense_date' => '2026-05-06',
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'company_id' => $user->company_id,
            'category_id' => $category->id,
            'description' => 'Gasolina de cobradores',
            'amount' => 2500,
            'created_by' => $user->id,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'company_id' => $user->company_id,
            'type' => 'expense',
            'direction' => 'out',
            'amount' => 2500,
        ]);
    }

    public function test_expense_cannot_use_category_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignCategory = ExpenseCategory::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Categoría Ajena',
        ]);

        $this->actingAs($user)
            ->post('/gastos', [
                'category_id' => $foreignCategory->id,
                'description' => 'Gasto inválido',
                'amount' => 100,
                'expense_date' => '2026-05-06',
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('category_id');
    }

    public function test_user_cannot_view_expense_from_another_company(): void
    {
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $foreignExpense = Expense::query()->create([
            'company_id' => $otherCompany->id,
            'description' => 'Gasto ajeno',
            'amount' => 100,
            'expense_date' => '2026-05-06',
            'payment_method' => 'cash',
        ]);

        $this->actingAs($user)
            ->get(route('expenses.show', $foreignExpense))
            ->assertNotFound();
    }

    public function test_category_name_is_unique_per_company(): void
    {
        $user = $this->adminUser();

        ExpenseCategory::query()->create([
            'company_id' => $user->company_id,
            'name' => 'Operación',
        ]);

        $this->actingAs($user)
            ->post('/gastos/categorias', [
                'name' => 'Operación',
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
}
