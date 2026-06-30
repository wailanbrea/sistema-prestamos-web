<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiV2AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_browse_global_clients_and_loans(): void
    {
        [$admin, $company] = $this->adminUser();
        [$client, $activeLoan] = $this->seedPortfolio($company->id);
        $token = $this->loginToken($admin);

        $this->withToken($token)
            ->getJson('/api/v2/admin/clients')
            ->assertOk()
            ->assertJsonPath('data.0.full_name', $client->full_name)
            ->assertJsonPath('meta.total', 1);

        $this->withToken($token)
            ->getJson("/api/v2/admin/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.summary.active_loans', 1)
            ->assertJsonPath('data.loans.0.id', $activeLoan->id);

        $this->withToken($token)
            ->getJson('/api/v2/admin/loans')
            ->assertOk()
            ->assertJsonPath('meta.total', 2); // activo + pendiente

        $this->withToken($token)
            ->getJson("/api/v2/admin/loans/{$activeLoan->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $activeLoan->id)
            ->assertJsonPath('data.summary.installments_pending', 1);
    }

    public function test_admin_can_list_and_approve_pending_loans(): void
    {
        [$admin, $company] = $this->adminUser();
        [, , $pendingLoan] = $this->seedPortfolio($company->id);
        $token = $this->loginToken($admin);

        $this->withToken($token)
            ->getJson('/api/v2/admin/approvals')
            ->assertOk()
            ->assertJsonPath('data.0.id', $pendingLoan->id)
            ->assertJsonPath('data.0.status', 'pending');

        $this->withToken($token)
            ->postJson("/api/v2/admin/loans/{$pendingLoan->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.id', $pendingLoan->id)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('loans', ['id' => $pendingLoan->id, 'status' => 'active']);

        // Aprobar de nuevo debe fallar (ya no está pendiente).
        $this->withToken($token)
            ->postJson("/api/v2/admin/loans/{$pendingLoan->id}/approve")
            ->assertStatus(422);
    }

    public function test_admin_can_reject_pending_loan(): void
    {
        [$admin, $company] = $this->adminUser();
        [, , $pendingLoan] = $this->seedPortfolio($company->id);
        $token = $this->loginToken($admin);

        $this->withToken($token)
            ->postJson("/api/v2/admin/loans/{$pendingLoan->id}/reject", ['reason' => 'Sin garantía'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('loans', ['id' => $pendingLoan->id, 'status' => 'cancelled']);
    }

    public function test_admin_can_read_reports(): void
    {
        [$admin, $company] = $this->adminUser();
        $this->seedPortfolio($company->id);
        $token = $this->loginToken($admin);

        $this->withToken($token)
            ->getJson('/api/v2/admin/reports/summary?date_from=2026-05-01&date_to=2026-05-31')
            ->assertOk()
            ->assertJsonStructure(['data' => ['totals' => ['capital_invested', 'capital_on_street', 'roi'], 'clients']]);

        $this->withToken($token)
            ->getJson('/api/v2/admin/reports/collectors?date_from=2026-05-01&date_to=2026-05-31')
            ->assertOk()
            ->assertJsonStructure(['data' => ['rows', 'totals']]);
    }

    public function test_admin_can_register_payment_for_company_loan(): void
    {
        [$admin, $company] = $this->adminUser();
        [, $activeLoan] = $this->seedPortfolio($company->id);
        $token = $this->loginToken($admin);
        $uuid = (string) Str::uuid();

        $payload = [
            'loan_id' => $activeLoan->id,
            'payment_date' => '2026-05-15',
            'amount' => 1100,
            'payment_method' => 'cash',
            'mobile_uuid' => $uuid,
        ];

        $response = $this->withToken($token)
            ->postJson('/api/v2/admin/payments', $payload)
            ->assertCreated()
            ->assertJsonPath('data.amount', 1100)
            ->assertJsonPath('data.status', 'valid')
            ->assertJsonStructure(['data' => ['receipt_url', 'whatsapp_url']]);

        // Reenviar el mismo mobile_uuid devuelve el pago existente (idempotencia).
        $this->withToken($token)
            ->postJson('/api/v2/admin/payments', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $response->json('data.id'));

        $this->assertSame(1, Payment::query()->where('loan_id', $activeLoan->id)->count());
    }

    public function test_admin_can_create_client(): void
    {
        [$admin] = $this->adminUser();
        $token = $this->loginToken($admin);

        $this->withToken($token)
            ->postJson('/api/v2/admin/clients', [
                'full_name' => 'Cliente Móvil Nuevo',
                'identification' => '001-2222222-2',
                'phone' => '809-555-0101',
                'address' => 'Av. Principal #1, Santo Domingo',
                'monthly_income' => 35000,
                'status' => 'active',
                'risk_level' => 'low',
            ])
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'Cliente Móvil Nuevo')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('clients', ['full_name' => 'Cliente Móvil Nuevo']);

        // La dirección es obligatoria (mismo contrato que la web).
        $this->withToken($token)
            ->postJson('/api/v2/admin/clients', [
                'full_name' => 'Sin Dirección',
                'status' => 'active',
                'risk_level' => 'low',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address']);
    }

    public function test_admin_can_create_and_browse_quotes(): void
    {
        [$admin, $company] = $this->adminUser();
        [$client] = $this->seedPortfolio($company->id);
        $token = $this->loginToken($admin);

        $created = $this->withToken($token)
            ->postJson('/api/v2/admin/quotes', [
                'client_id' => $client->id,
                'amount' => 10000,
                'interest_rate' => 10,
                'interest_type' => 'fixed',
                'payment_frequency' => 'weekly',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('data.client.id', $client->id)
            ->assertJsonStructure(['data' => ['installment_amount', 'total_interest', 'total_amount', 'installments']]);

        $quoteId = $created->json('data.id');

        $this->withToken($token)
            ->getJson('/api/v2/admin/quotes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $quoteId);

        $this->withToken($token)
            ->getJson("/api/v2/admin/quotes/{$quoteId}")
            ->assertOk()
            ->assertJsonPath('data.id', $quoteId)
            ->assertJsonStructure(['data' => ['installments']]);

        $this->withToken($token)
            ->deleteJson("/api/v2/admin/quotes/{$quoteId}")
            ->assertOk();

        $this->assertDatabaseMissing('loan_quotes', ['id' => $quoteId]);
    }

    public function test_collector_is_forbidden_from_admin_endpoints(): void
    {
        [$admin, $company] = $this->adminUser();
        [, $activeLoan] = $this->seedPortfolio($company->id);
        $collectorToken = $this->loginToken($this->collectorUser($company));

        foreach ([
            '/api/v2/admin/clients',
            '/api/v2/admin/loans',
            '/api/v2/admin/approvals',
            '/api/v2/admin/reports/summary',
        ] as $endpoint) {
            $this->withToken($collectorToken)->getJson($endpoint)->assertForbidden();
        }

        $this->withToken($collectorToken)
            ->postJson("/api/v2/admin/loans/{$activeLoan->id}/approve")
            ->assertForbidden();

        // El cobrador tiene payments.create, pero el cobro back-office exige
        // además collectors.manage: no puede cobrar fuera de su cartera por aquí.
        $this->withToken($collectorToken)
            ->postJson('/api/v2/admin/payments', [
                'loan_id' => $activeLoan->id,
                'payment_date' => '2026-05-15',
                'amount' => 1100,
                'payment_method' => 'cash',
            ])
            ->assertForbidden();
    }

    public function test_login_payload_marks_is_collector_flag(): void
    {
        [$admin, $company] = $this->adminUser();

        // El administrador tiene payments.create pero NO está vinculado a un Collector:
        // is_collector debe ser false para que la app no lo trate como cobrador de campo.
        $this->postJson('/api/v2/auth/login', ['email' => $admin->email, 'password' => 'Password123!'])
            ->assertOk()
            ->assertJsonPath('data.user.is_collector', false);

        $collector = $this->collectorUser($company);

        $this->postJson('/api/v2/auth/login', ['email' => $collector->email, 'password' => 'Password123!'])
            ->assertOk()
            ->assertJsonPath('data.user.is_collector', true);
    }

    public function test_admin_endpoints_are_isolated_by_company(): void
    {
        [$admin] = $this->adminUser();
        $other = Company::query()->create(['name' => 'Empresa Ajena', 'status' => 'active']);
        [$foreignClient, $foreignLoan] = $this->seedPortfolio($other->id);
        $token = $this->loginToken($admin);

        $this->withToken($token)->getJson('/api/v2/admin/clients')->assertOk()->assertJsonPath('meta.total', 0);
        $this->withToken($token)->getJson("/api/v2/admin/clients/{$foreignClient->id}")->assertNotFound();
        $this->withToken($token)->getJson("/api/v2/admin/loans/{$foreignLoan->id}")->assertNotFound();
    }

    public function test_admin_can_waive_installment_late_fee(): void
    {
        [$admin, $company] = $this->adminUser();
        [, $loan] = $this->seedPortfolio($company->id);
        $installment = $loan->installments()->orderBy('installment_number')->firstOrFail();
        $installment->forceFill([
            'late_fee' => 75,
            'status' => 'late',
            'due_date' => now()->subDays(3)->toDateString(),
        ])->save();
        $loan->forceFill(['status' => 'late'])->save();
        $token = $this->loginToken($admin);

        $this->withToken($token)
            ->deleteJson("/api/v2/admin/loans/{$loan->id}/installments/{$installment->id}/late-fee", [
                'reason' => 'Prueba de condonacion',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $installment->id)
            ->assertJsonPath('data.pending_late_fee', 0)
            ->assertJsonPath('data.late_fee', 0);

        $this->assertDatabaseHas('loan_installments', [
            'id' => $installment->id,
            'late_fee' => 0,
            'late_fee_waived_reason' => 'Prueba de condonacion',
        ]);
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function adminUser(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create(['name' => 'Empresa Admin API', 'status' => 'active']);
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Admin API',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return [$user, $company];
    }

    private function collectorUser(Company $company): User
    {
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Cobrador API',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Cobrador');

        Collector::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'name' => 'Cobrador API',
            'commission_type' => 'none',
            'commission_value' => 0,
            'status' => 'active',
        ]);

        return $user;
    }

    /**
     * Crea un cliente con un préstamo activo (con cuota y pago) y uno pendiente.
     *
     * @return array{0: Client, 1: Loan, 2: Loan}
     */
    private function seedPortfolio(int $companyId): array
    {
        $client = Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente Admin API',
            'identification' => '001-1111111-1',
            'phone' => '809-555-0199',
            'address' => 'Santo Domingo',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        $activeLoan = Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'loan_number' => 'PRE-ADM-'.fake()->unique()->numerify('####'),
            'principal_amount' => 2000,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 2,
            'installment_amount' => 1100,
            'total_interest' => 200,
            'total_amount' => 2200,
            'paid_principal' => 1000,
            'paid_interest' => 100,
            'remaining_balance' => 1000,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-05-15',
            'status' => 'active',
        ]);

        LoanInstallment::query()->create([
            'loan_id' => $activeLoan->id,
            'installment_number' => 1,
            'due_date' => '2026-05-15',
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'status' => 'pending',
        ]);

        $pendingLoan = Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'loan_number' => 'PRE-ADM-'.fake()->unique()->numerify('####'),
            'principal_amount' => 1500,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 1,
            'installment_amount' => 1650,
            'total_interest' => 150,
            'total_amount' => 1650,
            'remaining_balance' => 1500,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'start_date' => '2026-05-10',
            'first_payment_date' => '2026-06-10',
            'status' => 'pending',
        ]);

        return [$client, $activeLoan, $pendingLoan];
    }

    private function loginToken(User $user): string
    {
        return (string) $this->postJson('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk()->json('data.access_token');
    }
}
