<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Route as LendingRoute;
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
            ->assertJsonPath('data.pending_installments', 1)
            ->assertJsonPath('data.commissions.generated_total', 0)
            ->assertJsonPath('data.commissions.pending_total', 0)
            ->assertJsonPath('data.commissions.paid_total', 0);

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

    public function test_collector_can_read_map_clients_and_routes(): void
    {
        [$user, $collector, $loan] = $this->collectorWithLoan();
        $route = LendingRoute::query()->create([
            'company_id' => $collector->company_id,
            'collector_id' => $collector->id,
            'name' => 'Ruta API',
            'status' => 'active',
        ]);
        $route->clients()->sync([$loan->client_id => ['order_number' => 1]]);
        $token = $this->loginToken($user);

        $this->withToken($token)
            ->getJson('/api/v2/collector/map-clients')
            ->assertOk()
            ->assertJsonPath('data.0.id', $loan->client_id)
            ->assertJsonPath('data.0.latitude', 18.4861)
            ->assertJsonPath('data.0.longitude', -69.9312)
            ->assertJsonPath('data.0.summary.remaining_balance', 1000)
            ->assertJsonPath('data.0.routes.0.name', 'Ruta API');

        $this->withToken($token)
            ->getJson('/api/v2/collector/routes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $route->id)
            ->assertJsonPath('data.0.clients.0.id', $loan->client_id)
            ->assertJsonPath('data.0.clients.0.order_number', 1)
            ->assertJsonPath('data.0.clients.0.summary.total_paid', 0);
    }

    public function test_collector_can_track_route_session_and_mark_visited_stops(): void
    {
        [$user, $collector, $loan] = $this->collectorWithLoan();
        $route = LendingRoute::query()->create([
            'company_id' => $collector->company_id,
            'collector_id' => $collector->id,
            'name' => 'Ruta Tracking API',
            'status' => 'active',
        ]);
        $route->clients()->sync([$loan->client_id => ['order_number' => 1]]);
        $token = $this->loginToken($user);

        $sessionId = $this->withToken($token)
            ->postJson('/api/v2/collector/route-sessions', [
                'route_id' => $route->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.route.id', $route->id)
            ->assertJsonPath('data.status', 'active')
            ->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v2/collector/route-sessions/{$sessionId}/locations", [
                'latitude' => 18.48612,
                'longitude' => -69.93120,
                'accuracy_meters' => 10,
                'battery_level' => 87,
                'recorded_at' => '2026-05-07T08:00:00-04:00',
            ])
            ->assertOk()
            ->assertJsonPath('data.stops.0.visited', true)
            ->assertJsonPath('data.stops.0.visit_status', 'visited');

        $this->assertDatabaseHas('collector_location_points', [
            'collector_route_session_id' => $sessionId,
            'collector_id' => $collector->id,
            'accuracy_meters' => 10,
            'battery_level' => 87,
        ]);
        $this->assertDatabaseHas('route_visit_events', [
            'collector_route_session_id' => $sessionId,
            'client_id' => $loan->client_id,
            'expected_order' => 1,
            'visited_order' => 1,
            'status' => 'visited',
        ]);

        $this->withToken($token)
            ->getJson('/api/v2/collector/route-sessions/active')
            ->assertOk()
            ->assertJsonPath('data.id', $sessionId)
            ->assertJsonPath('data.stops.0.visited', true);
    }

    public function test_route_tracking_respects_company_visit_radius(): void
    {
        [$user, $collector, $loan] = $this->collectorWithLoan();
        CompanySetting::query()->updateOrCreate([
            'company_id' => $collector->company_id,
        ], [
            'route_visit_radius_meters' => 20,
        ]);
        $route = LendingRoute::query()->create([
            'company_id' => $collector->company_id,
            'collector_id' => $collector->id,
            'name' => 'Ruta Radio API',
            'status' => 'active',
        ]);
        $route->clients()->sync([$loan->client_id => ['order_number' => 1]]);
        $token = $this->loginToken($user);

        $sessionId = $this->withToken($token)
            ->postJson('/api/v2/collector/route-sessions', [
                'route_id' => $route->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v2/collector/route-sessions/{$sessionId}/locations", [
                'latitude' => 18.48645,
                'longitude' => -69.93120,
                'recorded_at' => '2026-05-07T08:05:00-04:00',
            ])
            ->assertOk()
            ->assertJsonPath('data.stops.0.visited', false);

        $this->assertDatabaseMissing('route_visit_events', [
            'collector_route_session_id' => $sessionId,
            'client_id' => $loan->client_id,
        ]);
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
            ->assertJsonPath('data.new_balance', 0)
            ->assertJsonPath('data.commission.commission_type', 'percentage')
            ->assertJsonPath('data.commission.base_amount', 1100)
            ->assertJsonPath('data.commission.commission_amount', 55)
            ->assertJsonPath('data.commission.status', 'pending');

        $this->assertDatabaseHas('payments', [
            'loan_id' => $loan->id,
            'collector_id' => $collector->id,
            'amount' => 1100,
            'status' => 'valid',
        ]);
    }

    public function test_collector_payment_registration_is_idempotent_by_mobile_uuid(): void
    {
        [$user, $collector, $loan] = $this->collectorWithLoan();
        $token = $this->loginToken($user);
        $mobileUuid = '0dd9e8f0-4901-4724-8d58-fc5be02f0034';
        $payload = [
            'loan_id' => $loan->id,
            'payment_date' => '2026-05-06',
            'amount' => 1100,
            'payment_method' => 'cash',
            'mobile_uuid' => $mobileUuid,
        ];

        $paymentId = $this->withToken($token)
            ->postJson('/api/v2/collector/payments', $payload)
            ->assertCreated()
            ->assertJsonPath('data.mobile_uuid', $mobileUuid)
            ->json('data.id');

        $this->withToken($token)
            ->postJson('/api/v2/collector/payments', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $paymentId)
            ->assertJsonPath('data.collector.id', $collector->id)
            ->assertJsonPath('data.mobile_uuid', $mobileUuid);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'loan_id' => $loan->id,
            'collector_id' => $collector->id,
            'mobile_uuid' => $mobileUuid,
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
            ->assertJsonPath('data.0.commission.commission_amount', 55)
            ->assertJsonPath('meta.total', 1);

        $this->withToken($token)
            ->getJson("/api/v2/collector/payments/{$paymentId}")
            ->assertOk()
            ->assertJsonPath('data.id', $paymentId)
            ->assertJsonPath('data.details.0.installment_number', 1)
            ->assertJsonPath('data.details.0.amount_paid', 1100)
            ->assertJsonPath('data.commission.base_amount', 1100)
            ->assertJsonPath('data.commission.commission_amount', 55);

        $this->withToken($token)
            ->getJson('/api/v2/collector/summary')
            ->assertOk()
            ->assertJsonPath('data.commissions.generated_total', 55)
            ->assertJsonPath('data.commissions.pending_total', 55)
            ->assertJsonPath('data.commissions.paid_total', 0);
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
            'latitude' => 18.4861,
            'longitude' => -69.9312,
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
