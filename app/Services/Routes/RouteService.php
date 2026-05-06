<?php

declare(strict_types=1);

namespace App\Services\Routes;

use App\Models\Route as LendingRoute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RouteService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return LendingRoute::query()
            ->with(['zone:id,name', 'collector:id,name'])
            ->withCount('clients')
            ->forCompany($companyId)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['zone_id'] ?? null, fn (Builder $query, string $zoneId) => $query->where('zone_id', $zoneId))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data): LendingRoute
    {
        $clientIds = $data['client_ids'] ?? [];
        unset($data['client_ids']);

        $data['company_id'] = $companyId;
        $route = LendingRoute::query()->create($data);
        $this->syncClients($route, $clientIds);

        return $route->refresh();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(LendingRoute $route, array $data): LendingRoute
    {
        $clientIds = $data['client_ids'] ?? [];
        unset($data['client_ids']);

        $route->update($data);
        $this->syncClients($route, $clientIds);

        return $route->refresh();
    }

    public function findForCompany(int $companyId, int $routeId): LendingRoute
    {
        return LendingRoute::query()
            ->with([
                'zone:id,name',
                'collector:id,name,phone',
                'clients' => fn ($query) => $query->orderBy('route_clients.order_number')->orderBy('clients.full_name'),
            ])
            ->forCompany($companyId)
            ->whereKey($routeId)
            ->firstOrFail();
    }

    public function delete(LendingRoute $route): void
    {
        $route->delete();
    }

    /**
     * @param array<int, int|string> $clientIds
     */
    private function syncClients(LendingRoute $route, array $clientIds): void
    {
        $syncData = [];

        foreach (array_values($clientIds) as $index => $clientId) {
            $syncData[(int) $clientId] = ['order_number' => $index + 1];
        }

        $route->clients()->sync($syncData);
    }
}
