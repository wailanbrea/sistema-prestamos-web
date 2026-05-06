<?php

declare(strict_types=1);

namespace App\Services\Cash;

use App\Models\CashMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CashMovementReportService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return CashMovement::query()
            ->with('createdBy:id,name')
            ->forCompany($companyId)
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['direction'] ?? null, fn (Builder $query, string $direction) => $query->where('direction', $direction))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('movement_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('movement_date', '<=', $date))
            ->latest('movement_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{total_in:float,total_out:float,balance:float}
     */
    public function totalsForCompany(int $companyId, array $filters = []): array
    {
        $baseQuery = CashMovement::query()
            ->forCompany($companyId)
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['direction'] ?? null, fn (Builder $query, string $direction) => $query->where('direction', $direction))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('movement_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('movement_date', '<=', $date));

        $totalIn = (clone $baseQuery)->where('direction', 'in')->sum('amount');
        $totalOut = (clone $baseQuery)->where('direction', 'out')->sum('amount');

        return [
            'total_in' => (float) $totalIn,
            'total_out' => (float) $totalOut,
            'balance' => round((float) $totalIn - (float) $totalOut, 2),
        ];
    }
}
