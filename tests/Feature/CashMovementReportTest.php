<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CashMovementReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_manual_capital_injection(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post('/caja', [
                'type' => 'capital_injection',
                'amount' => 50000,
                'movement_date' => '2026-05-06',
                'description' => 'Capital inicial para operaciones.',
            ])
            ->assertRedirect('/caja');

        $this->assertDatabaseHas('cash_movements', [
            'company_id' => $user->company_id,
            'type' => 'capital_injection',
            'direction' => 'in',
            'amount' => 50000,
            'movement_date' => '2026-05-06 00:00:00',
            'created_by' => $user->id,
        ]);
    }

    public function test_adjustment_requires_direction(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post('/caja', [
                'type' => 'adjustment',
                'amount' => 1000,
                'movement_date' => '2026-05-06',
                'description' => 'Ajuste operativo sin dirección.',
            ])
            ->assertSessionHasErrors('direction');
    }

    public function test_admin_can_view_cash_movements_for_own_company(): void
    {
        $user = $this->adminUser();

        CashMovement::query()->create([
            'company_id' => $user->company_id,
            'type' => 'payment_received',
            'amount' => 1500,
            'direction' => 'in',
            'description' => 'Cobro',
            'movement_date' => '2026-05-06',
        ]);
        CashMovement::query()->create([
            'company_id' => $user->company_id,
            'type' => 'expense',
            'amount' => 500,
            'direction' => 'out',
            'description' => 'Gasto',
            'movement_date' => '2026-05-06',
        ]);
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        CashMovement::query()->create([
            'company_id' => $otherCompany->id,
            'type' => 'payment_received',
            'amount' => 9999,
            'direction' => 'in',
            'description' => 'Movimiento ajeno',
            'movement_date' => '2026-05-06',
        ]);

        $this->actingAs($user)
            ->get('/caja')
            ->assertOk()
            ->assertSee('RD$ 1,500.00')
            ->assertSee('RD$ 500.00')
            ->assertSee('RD$ 1,000.00')
            ->assertDontSee('Movimiento ajeno');
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
