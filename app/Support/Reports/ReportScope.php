<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Models\Collector;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aplica de forma centralizada el aislamiento por empresa, la restricción por
 * rol y los filtros dimensionales (zona/ruta/cobrador/cliente) a las queries de
 * reportes. El filtrado por fecha NO se hace aquí, porque la columna de fecha
 * difiere por modelo (payment_date, start_date, expense_date, due_date); cada
 * método del ReportService aplica su propio rango sobre la columna correcta.
 */
class ReportScope
{
    private readonly int $companyId;

    /** Cobrador al que se restringe al usuario (null = sin restricción). */
    private readonly ?int $restrictedCollectorId;

    public function __construct(
        private readonly User $user,
        private readonly ReportFilters $filters,
    ) {
        $this->companyId = (int) ($user->company_id ?? 0);
        $this->restrictedCollectorId = $this->resolveRoleRestriction();
    }

    public function companyId(): int
    {
        return $this->companyId;
    }

    /** Cobrador efectivo: el del filtro, o el forzado por rol si aplica. */
    public function effectiveCollectorId(): ?int
    {
        return $this->restrictedCollectorId ?? $this->filters->collectorId;
    }

    /**
     * Modelos con collector_id y relación client() directa (loans, payments).
     *
     * @param Builder<\Illuminate\Database\Eloquent\Model> $query
     */
    public function applyDimensions(Builder $query): Builder
    {
        $query->where($query->getModel()->getTable().'.company_id', $this->companyId);

        if ($collectorId = $this->effectiveCollectorId()) {
            $query->where($query->getModel()->getTable().'.collector_id', $collectorId);
        }

        if ($this->filters->clientId) {
            $query->where($query->getModel()->getTable().'.client_id', $this->filters->clientId);
        }

        $this->applyRouteAndZone($query, 'client.routes');

        return $query;
    }

    /**
     * Gastos: solo aislamiento por empresa (la tabla no tiene collector/cliente/ruta).
     *
     * @param Builder<\App\Models\Expense> $query
     */
    public function applyToExpenses(Builder $query): Builder
    {
        return $query->where('expenses.company_id', $this->companyId);
    }

    /**
     * Clientes: filtro directo por ruta/zona (relación routes()) y por cliente.
     *
     * @param Builder<\App\Models\Client> $query
     */
    public function applyToClients(Builder $query): Builder
    {
        $query->where('clients.company_id', $this->companyId);

        if ($this->filters->clientId) {
            $query->where('clients.id', $this->filters->clientId);
        }

        if ($this->filters->clientStatus) {
            $query->where('clients.status', $this->filters->clientStatus);
        }

        $this->applyRouteAndZone($query, 'routes');

        // Cobrador: vía préstamos del cliente (clients no tiene collector_id).
        if ($collectorId = $this->effectiveCollectorId()) {
            $query->whereHas('loans', fn (Builder $q) => $q->where('collector_id', $collectorId));
        }

        return $query;
    }

    /**
     * Aplica el filtro de ruta/zona vía una relación que termina en routes.
     *
     * @param Builder<\Illuminate\Database\Eloquent\Model> $query
     */
    private function applyRouteAndZone(Builder $query, string $routesRelation): void
    {
        if ($this->filters->routeId) {
            $query->whereHas($routesRelation, fn (Builder $q) => $q->where('routes.id', $this->filters->routeId));
        }

        if ($this->filters->zoneId) {
            $query->whereHas($routesRelation, fn (Builder $q) => $q->where('routes.zone_id', $this->filters->zoneId));
        }
    }

    /**
     * Un usuario que no es dueño/admin/supervisor y está vinculado a un cobrador
     * solo puede ver su propia cartera. Se detecta por carecer del permiso
     * `collectors.manage` (que tienen Admin/Supervisor) y tener un collector ligado.
     */
    private function resolveRoleRestriction(): ?int
    {
        if ($this->user->isSystemOwner() || $this->user->can('collectors.manage')) {
            return null;
        }

        return Collector::query()
            ->where('company_id', $this->companyId)
            ->where('user_id', $this->user->id)
            ->value('id');
    }
}
