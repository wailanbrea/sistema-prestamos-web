<?php

declare(strict_types=1);

namespace App\Services\Routes;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Payment;
use App\Models\Route as LendingRoute;
use Illuminate\Database\Eloquent\Collection;

class RouteMapService
{
    /**
     * @return array{
     *     collectors:Collection<int,Collector>,
     *     routes:Collection<int,LendingRoute>,
     *     clients:array<int,array<string,mixed>>,
     *     totals:array<string,float|int>
     * }
     */
    public function dataForCompany(int $companyId, array $filters = []): array
    {
        $collectorId = isset($filters['collector_id']) && $filters['collector_id'] !== ''
            ? (int) $filters['collector_id']
            : null;
        $routeId = isset($filters['route_id']) && $filters['route_id'] !== ''
            ? (int) $filters['route_id']
            : null;

        $routes = LendingRoute::query()
            ->forCompany($companyId)
            ->with(['collector:id,name', 'zone:id,name'])
            ->withCount('clients')
            ->when($collectorId, fn ($query) => $query->where('collector_id', $collectorId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $clients = Client::query()
            ->forCompany($companyId)
            ->with([
                'routes' => fn ($query) => $query
                    ->when($collectorId, fn ($routeQuery) => $routeQuery->where('collector_id', $collectorId))
                    ->when($routeId, fn ($routeQuery) => $routeQuery->where('routes.id', $routeId))
                    ->orderBy('route_clients.order_number')
                    ->select('routes.id', 'routes.name', 'routes.collector_id'),
            ])
            ->whereHas('routes', function ($query) use ($collectorId, $routeId): void {
                $query->where('status', 'active')
                    ->when($collectorId, fn ($routeQuery) => $routeQuery->where('collector_id', $collectorId))
                    ->when($routeId, fn ($routeQuery) => $routeQuery->where('routes.id', $routeId));
            })
            ->withSum(['loans as active_balance' => fn ($query) => $query->whereIn('status', ['active', 'late'])], 'remaining_balance')
            ->withCount(['loans as late_loans_count' => fn ($query) => $query->where('status', 'late')])
            ->orderBy('full_name')
            ->get();

        $paidByClient = Payment::query()
            ->forCompany($companyId)
            ->where('status', 'valid')
            ->whereIn('client_id', $clients->pluck('id'))
            ->selectRaw('client_id, sum(amount) as total_paid')
            ->groupBy('client_id')
            ->pluck('total_paid', 'client_id');

        $clientRows = $clients
            ->map(function (Client $client) use ($paidByClient): array {
                $balance = (float) ($client->active_balance ?? 0);
                $totalPaid = (float) ($paidByClient[$client->id] ?? 0);

                return [
                    'id' => $client->id,
                    'full_name' => $client->full_name,
                    'phone' => $client->phone,
                    'address' => $client->address,
                    'latitude' => $client->latitude === null ? null : (float) $client->latitude,
                    'longitude' => $client->longitude === null ? null : (float) $client->longitude,
                    'location_reference' => $client->location_reference,
                    'status' => $client->status,
                    'risk_level' => $client->risk_level,
                    'remaining_balance' => $balance,
                    'total_paid' => $totalPaid,
                    'late_loans_count' => (int) $client->late_loans_count,
                    'routes' => $client->routes
                        ->map(fn (LendingRoute $route): array => [
                            'id' => $route->id,
                            'name' => $route->name,
                            'order_number' => (int) $route->pivot->order_number,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $mapped = array_values(array_filter(
            $clientRows,
            fn (array $client): bool => $client['latitude'] !== null && $client['longitude'] !== null,
        ));

        return [
            'collectors' => Collector::query()
                ->forCompany($companyId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'routes' => $routes,
            'clients' => $clientRows,
            'totals' => [
                'clients' => count($clientRows),
                'mapped_clients' => count($mapped),
                'missing_coordinates' => count($clientRows) - count($mapped),
                'remaining_balance' => array_sum(array_column($clientRows, 'remaining_balance')),
                'total_paid' => array_sum(array_column($clientRows, 'total_paid')),
            ],
        ];
    }
}
