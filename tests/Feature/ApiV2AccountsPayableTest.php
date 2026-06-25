<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\Company;
use App\Models\Creditor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiV2AccountsPayableTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_accounts_payable_and_register_payment(): void
    {
        [$admin, $company] = $this->userWithRole('Administrador');
        $token = $this->loginToken($admin);

        $creditorResponse = $this->withToken($token)
            ->postJson('/api/v2/admin/accounts-payable/creditors', [
                'name' => 'Banco Comercial',
                'document' => '101010101',
                'phone' => '809-555-0101',
                'email' => 'banco@example.com',
                'address' => 'Santo Domingo',
                'notes' => 'Línea de crédito',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Banco Comercial');

        $creditorId = (int) $creditorResponse->json('data.id');

        $accountResponse = $this->withToken($token)
            ->postJson('/api/v2/admin/accounts-payable', [
                'creditor_id' => $creditorId,
                'currency' => 'RD$',
                'principal_amount' => 10000,
                'interest_rate' => 10,
                'interest_type' => 'fixed',
                'payment_frequency' => 'monthly',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 10,
                'late_fee_type' => 'none',
                'late_fee_value' => 0,
                'disbursement_date' => '2026-06-01',
                'first_payment_date' => '2026-07-01',
                'notes' => 'Préstamo bancario',
            ])
            ->assertCreated()
            ->assertJsonPath('data.creditor.id', $creditorId)
            ->assertJsonPath('data.installment_amount', 1100)
            ->assertJsonCount(10, 'data.installments');

        $accountId = (int) $accountResponse->json('data.id');

        $this->withToken($token)
            ->getJson('/api/v2/admin/accounts-payable')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $accountId);

        $this->withToken($token)
            ->getJson("/api/v2/admin/accounts-payable/{$accountId}")
            ->assertOk()
            ->assertJsonPath('data.reference', $accountResponse->json('data.reference'))
            ->assertJsonPath('data.remaining_balance', 10000);

        $this->withToken($token)
            ->postJson("/api/v2/admin/accounts-payable/{$accountId}/payments", [
                'payment_date' => '2026-07-01',
                'amount' => 1100,
                'payment_method' => 'transfer',
                'notes' => 'Primera cuota',
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount', 1100)
            ->assertJsonPath('data.principal_paid', 1000)
            ->assertJsonPath('data.interest_paid', 100)
            ->assertJsonPath('data.new_balance', 9000);

        $this->assertDatabaseHas('accounts_payable', [
            'id' => $accountId,
            'company_id' => $company->id,
            'remaining_balance' => 9000,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'company_id' => $company->id,
            'type' => 'accounts_payable_payment',
            'direction' => 'out',
            'amount' => 1100,
        ]);
    }

    public function test_feature_is_discoverable_and_respects_personal_menu_visibility(): void
    {
        [$admin] = $this->userWithRole('Administrador');

        $this->postJson('/api/v2/auth/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.features.accounts_payable', true);

        $admin->forceFill(['visible_menus' => ['dashboard']])->save();
        $token = $this->loginToken($admin);

        $this->withToken($token)
            ->getJson('/api/v2/admin/accounts-payable')
            ->assertForbidden();

        $this->withToken($token)
            ->getJson('/api/v2/me')
            ->assertOk()
            ->assertJsonPath('data.features.accounts_payable', false);
    }

    public function test_collector_cannot_access_accounts_payable(): void
    {
        [$collector] = $this->userWithRole('Cobrador');
        $token = $this->loginToken($collector);

        $this->withToken($token)
            ->getJson('/api/v2/admin/accounts-payable')
            ->assertForbidden();
    }

    public function test_accounts_are_isolated_by_company(): void
    {
        [$admin] = $this->userWithRole('Administrador');
        $foreignCompany = Company::query()->create([
            'name' => 'Empresa CXP Ajena',
            'status' => 'active',
            'plan' => 'full',
        ]);
        $foreignCreditor = Creditor::query()->create([
            'company_id' => $foreignCompany->id,
            'name' => 'Acreedor Ajeno',
            'status' => 'active',
        ]);
        $foreignAccount = AccountPayable::query()->create([
            'company_id' => $foreignCompany->id,
            'creditor_id' => $foreignCreditor->id,
            'reference' => 'CXP-AJENA-001',
            'currency' => 'US$',
            'principal_amount' => 5000,
            'interest_rate' => 5,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 5,
            'installment_amount' => 1050,
            'total_interest' => 250,
            'total_amount' => 5250,
            'remaining_balance' => 5000,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'disbursement_date' => '2026-06-01',
            'first_payment_date' => '2026-07-01',
            'status' => 'active',
        ]);
        $token = $this->loginToken($admin);

        $this->withToken($token)
            ->getJson("/api/v2/admin/accounts-payable/{$foreignAccount->id}")
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function userWithRole(string $role): array
    {
        $this->seed(RolePermissionSeeder::class);
        $company = Company::query()->create([
            'name' => 'Empresa CXP API '.uniqid(),
            'status' => 'active',
            'plan' => 'full',
        ]);
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => $role.' CXP',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole($role);

        return [$user, $company];
    }

    private function loginToken(User $user): string
    {
        return (string) $this->postJson('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk()->json('data.access_token');
    }
}
