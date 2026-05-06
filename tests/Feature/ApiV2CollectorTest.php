<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiV2CollectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_collector_can_read_assigned_mobile_workload(): void
    {
        [$user, $collector, $loan] = $this->collectorWithLoan();
        $token = $this->loginToken($user);

        $this->withToken($token)
            ->getJson('/api/v2/collector/summary')
            ->assertOk()
            ->assertJsonPath('data.collector.id', $collector->id)
            ->assertJsonPath('data.assigned_clients', 1)
            ->assertJsonPath('data.active_loans', 1)
            ->assertJsonPath('data.pending_installments', 1);

        $this->withToken($token)
            ->getJson('/api/v2/collector/clients')
            ->assertOk()
            ->assertJsonPath('data.0.full_name', 'Cliente API Cobrador');

        $this->withToken($token)
            ->getJson('/api/v2/collector/loans')
            ->assertOk()
            ->assertJsonPath('data.0.loan_number', $loan->loan_number);

        $this->withToken($token)
            ->getJson('/api/v2/collector/installments')
            ->assertOk()
            ->assertJsonPath('data.0.loan_number', $loan->loan_number)
            ->assertJsonPath('data.0.installment_number', 1);
    }

    public function test_collector_can_read_client_and_loan_details(): void
    {
        [$user, , $loan] = $this->collectorWithLoan();
        $token = $this->loginToken($user);

        $this->withToken($token)
            ->getJson("/api/v2/collector/clients/{$loan->client_id}")
            ->assertOk()
            ->assertJsonPath('data.id', $loan->client_id)
            ->assertJsonPath('data.summary.active_loans', 1)
            ->assertJsonPath('data.summary.pending_installments', 1)
            ->assertJsonPath('data.loans.0.id', $loan->id)
            ->assertJsonPath('data.pending_installments.0.loan_id', $loan->id);

        $this->withToken($token)
            ->getJson("/api/v2/collector/loans/{$loan->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $loan->id)
            ->assertJsonPath('data.client.id', $loan->client_id)
            ->assertJsonPath('data.installments.0.installment_number', 1)
            ->assertJsonPath('data.summary.installments_pending', 1);
    }

    public function test_collector_can_read_installment_detail(): void
    {
        [$user, , $loan] = $this->collectorWithLoan();
        $token = $this->loginToken($user);
        $installment = $loan->installments()->firstOrFail();

        $this->withToken($token)
            ->getJson("/api/v2/collector/installments/{$installment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $installment->id)
            ->assertJsonPath('data.loan_id', $loan->id)
            ->assertJsonPath('data.client.id', $loan->client_id)
            ->assertJsonPath('data.payments', []);
    }

    public function test_collector_can_register_payment_for_assigned_loan(): void
    {
        [$user, $collector, $loan] = $this->collectorWithLoan();
        $token = $this->loginToken($user);

        $this->withToken($token)
            ->postJson('/api/v2/collector/payments', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'amount' => 1100,
                'payment_method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.loan_id', $loan->id)
            ->assertJsonPath('data.collector.id', $collector->id)
            ->assertJsonPath('data.new_balance', 0);

        $this->assertDatabaseHas('payments', [
            'loan_id' => $loan->id,
            'collector_id' => $collector->id,
            'amount' => 1100,
            'status' => 'valid',
        ]);
    }

    public function test_collector_can_read_payment_history_and_payment_detail(): void
    {
        [$user, $collector, $loan] = $this->collectorWithLoan();
        $token = $this->loginToken($user);

        $paymentId = $this->withToken($token)
            ->postJson('/api/v2/collector/payments', [
                'loan_id' => $loan->id,
                'payment_date' => '2026-05-06',
                'amount' => 1100,
                'payment_method' => 'cash',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->getJson("/api/v2/collector/payments?loan_id={$loan->id}&client_id={$loan->client_id}&date_from=2026-05-01&date_to=2026-05-31")
            ->assertOk()
            ->assertJsonPath('data.0.id', $paymentId)
            ->assertJsonPath('data.0.collector.id', $collector->id)
            ->assertJsonPath('meta.total', 1);

        $this->withToken($token)
            ->getJson("/api/v2/collector/payments/{$paymentId}")
            ->assertOk()
            ->assertJsonPath('data.id', $paymentId)
            ->assertJsonPath('data.details.0.installment_number', 1)
            ->assertJsonPath('data.details.0.amount_paid', 1100);
    }

    public function test_collector_cannot_register_payment_for_other_collector_loan(): void
    {
        [$user] = $this->collectorWithLoan();
        [, , $foreignLoan] = $this->collectorWithLoan('otro-cobrador@example.com');
        $token = $this->loginToken($user);

        $this->withToken($token)
            ->postJson('/api/v2/collector/payments', [
                'loan_id' => $foreignLoan->id,
                'payment_date' => '2026-05-06',
                'amount' => 100,
                'payment_method' => 'cash',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('loan_id');
    }

    public function test_collector_cannot_read_other_collector_details(): void
    {
        [$user] = $this->collectorWithLoan();
        [, , $foreignLoan] = $this->collectorWithLoan('otro-cobrador@example.com');
        $token = $this->loginToken($user);

        $this->withToken($token)
            ->getJson("/api/v2/collector/clients/{$foreignLoan->client_id}")
            ->assertNotFound();

        $this->withToken($token)
            ->getJson("/api/v2/collector/loans/{$foreignLoan->id}")
            ->assertNotFound();

        $foreignInstallment = $foreignLoan->installments()->firstOrFail();

        $this->withToken($token)
            ->getJson("/api/v2/collector/installments/{$foreignInstallment->id}")
            ->assertNotFound();
    }

    private function collectorWithLoan(?string $email = null): array
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->firstOrCreate([
            'name' => 'Empresa API Cobrador',
        ], [
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Cobrador API',
            'email' => $email ?: fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Cobrador');

        $collector = Collector::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'name' => 'Cobrador API',
            'commission_type' => 'percentage',
            'commission_value' => 5,
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'company_id' => $company->id,
            'full_name' => 'Cliente API Cobrador',
            'identification' => '001-0000000-1',
            'phone' => '809-555-0101',
            'address' => 'Santo Domingo',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        $loan = Loan::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'collector_id' => $collector->id,
            'loan_number' => 'PRE-API-'.fake()->unique()->numerify('####'),
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

        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-06-01',
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'status' => 'pending',
        ]);

        return [$user, $collector, $loan];
    }

    private function loginToken(User $user): string
    {
        return (string) $this->postJson('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk()->json('data.access_token');
    }
}
