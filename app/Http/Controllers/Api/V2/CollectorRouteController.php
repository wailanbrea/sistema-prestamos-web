<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Api\V2\Concerns\InteractsWithCollectorPortfolio;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Collector;
use App\Models\CollectorCommission;
use App\Models\CollectorRouteSession;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\Route as LendingRoute;
use App\Services\Routes\RouteTrackingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CollectorRouteController extends Controller
{
    use BuildsApiPayloads;
    use InteractsWithCollectorPortfolio;

    public function __construct(
        private readonly RouteTrackingService $routeTrackingService,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        return response()->json([
            'data' => [
                'collector' => $this->collectorPayload($collector),
                'assigned_clients' => $this->assignedClientQuery($collector)->count(),
                'active_loans' => $this->assignedLoanQuery($collector)->whereIn('status', ['active', 'late'])->count(),
                'late_loans' => $this->assignedLoanQuery($collector)->where('status', 'late')->count(),
                'pending_installments' => $this->assignedInstallmentQuery($collector)->count(),
                'collected_today' => (float) Payment::query()
                    ->forCompany((int) $collector->company_id)
                    ->where('collector_id', $collector->id)
                    ->where('status', 'valid')
                    ->whereDate('payment_date', now()->toDateString())
                    ->sum('amount'),
                'commissions' => [
                    'generated_total' => (float) CollectorCommission::query()
                        ->forCompany((int) $collector->company_id)
                        ->where('collector_id', $collector->id)
                        ->sum('commission_amount'),
                    'pending_total' => (float) CollectorCommission::query()
                        ->forCompany((int) $collector->company_id)
                        ->where('collector_id', $collector->id)
                        ->where('status', 'pending')
                        ->sum('commission_amount'),
                    'paid_total' => (float) CollectorCommission::query()
                        ->forCompany((int) $collector->company_id)
                        ->where('collector_id', $collector->id)
                        ->where('status', 'paid')
                        ->sum('commission_amount'),
                ],
            ],
        ]);
    }

    public function clients(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $clients = $this->assignedClientQuery($collector)
            ->orderBy('full_name')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $clients->through(fn (Client $client): array => $this->clientPayload($client))->items(),
            'meta' => $this->paginationMeta($clients),
        ]);
    }

    public function mapClients(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $clients = $this->assignedClientQuery($collector)
            ->with(['routes' => fn ($query) => $query
                ->where('collector_id', $collector->id)
                ->orderBy('route_clients.order_number')
                ->select('routes.id', 'routes.name')])
            ->whereNotNull('address')
            ->orderBy('full_name')
            ->get();

        return response()->json([
            'data' => $clients
                ->map(fn (Client $client): array => [
                    ...$this->clientPayload($client),
                    'summary' => $this->clientFinancialSummary($collector, (int) $client->id),
                    'routes' => $client->routes
                        ->map(fn (LendingRoute $route): array => [
                            'id' => $route->id,
                            'name' => $route->name,
                            'order_number' => (int) $route->pivot->order_number,
                        ])
                        ->values(),
                ])
                ->values(),
        ]);
    }

    public function routes(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $routes = LendingRoute::query()
            ->forCompany((int) $collector->company_id)
            ->where('collector_id', $collector->id)
            ->where('status', 'active')
            ->with(['zone:id,name', 'clients' => fn ($query) => $query
                ->orderBy('route_clients.order_number')
                ->select('clients.id', 'clients.code', 'clients.full_name', 'clients.phone', 'clients.address', 'clients.latitude', 'clients.longitude', 'clients.status', 'clients.risk_level')])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $routes
                ->map(fn (LendingRoute $route): array => [
                    'id' => $route->id,
                    'name' => $route->name,
                    'description' => $route->description,
                    'zone' => $route->zone ? [
                        'id' => $route->zone->id,
                        'name' => $route->zone->name,
                    ] : null,
                    'clients_count' => $route->clients->count(),
                    'clients' => $route->clients
                        ->map(fn (Client $client): array => [
                            ...$this->clientPayload($client),
                            'order_number' => (int) $client->pivot->order_number,
                            'summary' => $this->clientFinancialSummary($collector, (int) $client->id),
                        ])
                        ->values(),
                ])
                ->values(),
        ]);
    }

    public function activeRouteSession(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $session = $this->routeTrackingService->activeSessionForCollector($collector);

        return response()->json([
            'data' => $session ? $this->routeTrackingService->sessionPayload($session) : null,
        ]);
    }

    public function startRouteSession(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $validated = $request->validate([
            'route_id' => [
                'required',
                'integer',
                Rule::exists('routes', 'id')
                    ->where('company_id', $collector->company_id)
                    ->where('collector_id', $collector->id)
                    ->where('status', 'active'),
            ],
        ]);

        $session = $this->routeTrackingService->startSession($collector, (int) $validated['route_id']);

        return response()->json([
            'data' => $this->routeTrackingService->sessionPayload($session),
        ], 201);
    }

    public function recordRouteLocation(Request $request, int $session): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $sessionModel = $this->routeSessionForCollector($collector, $session);
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        try {
            $updated = $this->routeTrackingService->recordLocation(
                session: $sessionModel,
                latitude: (float) $validated['latitude'],
                longitude: (float) $validated['longitude'],
                accuracyMeters: isset($validated['accuracy_meters']) ? (int) $validated['accuracy_meters'] : null,
                batteryLevel: isset($validated['battery_level']) ? (int) $validated['battery_level'] : null,
                recordedAt: isset($validated['recorded_at']) ? CarbonImmutable::parse($validated['recorded_at']) : null,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => $this->routeTrackingService->sessionPayload($updated),
        ]);
    }

    public function finishRouteSession(Request $request, int $session): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $sessionModel = $this->routeSessionForCollector($collector, $session);

        return response()->json([
            'data' => $this->routeTrackingService->sessionPayload($this->routeTrackingService->finishSession($sessionModel)),
        ]);
    }

    public function client(Request $request, int $client): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $clientModel = $this->assignedClientQuery($collector)
            ->with([
                'references:id,client_id,name,phone,relationship,address',
                'routes:id,name',
            ])
            ->whereKey($client)
            ->firstOrFail();

        $loans = $this->assignedLoanQuery($collector)
            ->with('client:id,code,full_name,identification,phone,address,status,risk_level')
            ->where('client_id', $clientModel->id)
            ->orderByRaw("case when status in ('active', 'late') then 0 else 1 end")
            ->orderByDesc('id')
            ->get();

        $installments = $this->assignedInstallmentQuery($collector)
            ->with('loan.client:id,code,full_name,identification,phone,address,status,risk_level')
            ->whereHas('loan', fn (Builder $query): Builder => $query->where('client_id', $clientModel->id))
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $payments = $this->assignedPaymentQuery($collector)
            ->with(['loan.client', 'collector', 'collectorCommission'])
            ->where('client_id', $clientModel->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                ...$this->clientDetailPayload($clientModel),
                'summary' => $this->clientFinancialSummary($collector, (int) $clientModel->id),
                'loans' => $loans->map(fn (Loan $loan): array => $this->loanPayload($loan))->values(),
                'pending_installments' => $installments->map(fn (LoanInstallment $installment): array => $this->installmentPayload($installment))->values(),
                'recent_payments' => $payments->map(fn (Payment $payment): array => $this->collectorPaymentPayload($payment))->values(),
            ],
        ]);
    }

    private function routeSessionForCollector(Collector $collector, int $sessionId): CollectorRouteSession
    {
        return CollectorRouteSession::query()
            ->forCompany((int) $collector->company_id)
            ->where('collector_id', $collector->id)
            ->whereKey($sessionId)
            ->with(['route.clients', 'visitEvents.client'])
            ->firstOrFail();
    }
}
