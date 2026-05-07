<?php

declare(strict_types=1);

namespace App\Services\Routes;

use App\Models\Client;
use App\Models\Collector;
use App\Models\CollectorLocationPoint;
use App\Models\CollectorRouteSession;
use App\Models\CompanySetting;
use App\Models\Route as LendingRoute;
use App\Models\RouteVisitEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RouteTrackingService
{
    private const DEFAULT_VISIT_RADIUS_METERS = 75;

    public function startSession(Collector $collector, int $routeId): CollectorRouteSession
    {
        $route = LendingRoute::query()
            ->forCompany((int) $collector->company_id)
            ->whereKey($routeId)
            ->where('collector_id', $collector->id)
            ->where('status', 'active')
            ->firstOrFail();

        return DB::transaction(function () use ($collector, $route): CollectorRouteSession {
            $active = CollectorRouteSession::query()
                ->where('collector_id', $collector->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($active && (int) $active->route_id === (int) $route->id) {
                return $active->fresh(['route.clients', 'visitEvents.client']) ?? $active;
            }

            if ($active) {
                $active->update([
                    'status' => 'completed',
                    'ended_at' => now(),
                ]);
            }

            return CollectorRouteSession::query()->create([
                'company_id' => $collector->company_id,
                'route_id' => $route->id,
                'collector_id' => $collector->id,
                'status' => 'active',
                'started_at' => now(),
            ])->fresh(['route.clients', 'visitEvents.client']);
        });
    }

    public function activeSessionForCollector(Collector $collector): ?CollectorRouteSession
    {
        return CollectorRouteSession::query()
            ->forCompany((int) $collector->company_id)
            ->where('collector_id', $collector->id)
            ->where('status', 'active')
            ->with(['route.clients', 'visitEvents.client'])
            ->latest('started_at')
            ->first();
    }

    public function recordLocation(
        CollectorRouteSession $session,
        float $latitude,
        float $longitude,
        ?int $accuracyMeters,
        ?int $batteryLevel,
        ?CarbonImmutable $recordedAt = null,
    ): CollectorRouteSession {
        if ($session->status !== 'active') {
            throw new InvalidArgumentException('La sesion de ruta no esta activa.');
        }

        $recordedAt ??= CarbonImmutable::now();

        return DB::transaction(function () use ($session, $latitude, $longitude, $accuracyMeters, $batteryLevel, $recordedAt): CollectorRouteSession {
            CollectorLocationPoint::query()->create([
                'collector_route_session_id' => $session->id,
                'collector_id' => $session->collector_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy_meters' => $accuracyMeters,
                'battery_level' => $batteryLevel,
                'recorded_at' => $recordedAt,
            ]);

            $session->update([
                'last_latitude' => $latitude,
                'last_longitude' => $longitude,
                'last_location_at' => $recordedAt,
            ]);

            $this->markNearbyStops($session, $latitude, $longitude, $recordedAt);

            return $session->fresh(['route.clients', 'visitEvents.client']) ?? $session;
        });
    }

    public function finishSession(CollectorRouteSession $session): CollectorRouteSession
    {
        if ($session->status === 'active') {
            $session->update([
                'status' => 'completed',
                'ended_at' => now(),
            ]);
        }

        return $session->fresh(['route.clients', 'visitEvents.client']) ?? $session;
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionPayload(CollectorRouteSession $session): array
    {
        $session->loadMissing(['route.clients', 'visitEvents.client', 'collector']);
        $visits = $session->visitEvents->keyBy('client_id');

        return [
            'id' => $session->id,
            'status' => $session->status,
            'started_at' => $session->started_at?->toIso8601String(),
            'ended_at' => $session->ended_at?->toIso8601String(),
            'last_location_at' => $session->last_location_at?->toIso8601String(),
            'last_latitude' => $session->last_latitude === null ? null : (float) $session->last_latitude,
            'last_longitude' => $session->last_longitude === null ? null : (float) $session->last_longitude,
            'collector' => [
                'id' => $session->collector?->id,
                'name' => $session->collector?->name,
            ],
            'route' => [
                'id' => $session->route?->id,
                'name' => $session->route?->name,
            ],
            'stops' => $session->route?->clients
                ->sortBy(fn (Client $client): int => (int) $client->pivot->order_number)
                ->map(function (Client $client) use ($visits): array {
                    /** @var RouteVisitEvent|null $visit */
                    $visit = $visits->get($client->id);

                    return [
                        'client_id' => $client->id,
                        'client_name' => $client->full_name,
                        'address' => $client->address,
                        'latitude' => $client->latitude === null ? null : (float) $client->latitude,
                        'longitude' => $client->longitude === null ? null : (float) $client->longitude,
                        'expected_order' => (int) $client->pivot->order_number,
                        'visited' => $visit !== null,
                        'visited_order' => $visit?->visited_order,
                        'visited_at' => $visit?->visited_at?->toIso8601String(),
                        'visit_status' => $visit?->status,
                        'distance_meters' => $visit?->distance_meters,
                    ];
                })
                ->values()
                ->all() ?? [],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function activeSessionsForCompany(int $companyId): array
    {
        return CollectorRouteSession::query()
            ->forCompany($companyId)
            ->where('status', 'active')
            ->with(['collector', 'route.clients', 'visitEvents.client'])
            ->latest('last_location_at')
            ->get()
            ->map(fn (CollectorRouteSession $session): array => $this->sessionPayload($session))
            ->values()
            ->all();
    }

    private function markNearbyStops(CollectorRouteSession $session, float $latitude, float $longitude, CarbonImmutable $visitedAt): void
    {
        /** @var Collection<int,Client> $clients */
        $clients = $session->route()
            ->with(['clients' => fn ($query) => $query->orderBy('route_clients.order_number')])
            ->firstOrFail()
            ->clients;

        $existingClientIds = RouteVisitEvent::query()
            ->where('collector_route_session_id', $session->id)
            ->pluck('client_id')
            ->all();

        $visitedOrder = RouteVisitEvent::query()
            ->where('collector_route_session_id', $session->id)
            ->count() + 1;

        foreach ($clients as $client) {
            if (in_array($client->id, $existingClientIds, true) || $client->latitude === null || $client->longitude === null) {
                continue;
            }

            $distance = $this->distanceMeters($latitude, $longitude, (float) $client->latitude, (float) $client->longitude);
            if ($distance > $this->visitRadiusMeters((int) $session->company_id)) {
                continue;
            }

            $expectedOrder = (int) $client->pivot->order_number;

            RouteVisitEvent::query()->create([
                'collector_route_session_id' => $session->id,
                'route_id' => $session->route_id,
                'client_id' => $client->id,
                'expected_order' => $expectedOrder,
                'visited_order' => $visitedOrder,
                'status' => $visitedOrder === $expectedOrder ? 'visited' : 'visited_out_of_order',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance_meters' => (int) round($distance),
                'visited_at' => $visitedAt,
            ]);

            $visitedOrder++;
        }
    }

    private function distanceMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusMeters = 6371000;
        $latDelta = deg2rad($toLatitude - $fromLatitude);
        $lngDelta = deg2rad($toLongitude - $fromLongitude);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function visitRadiusMeters(int $companyId): int
    {
        return (int) (CompanySetting::query()
            ->where('company_id', $companyId)
            ->value('route_visit_radius_meters') ?? self::DEFAULT_VISIT_RADIUS_METERS);
    }
}
