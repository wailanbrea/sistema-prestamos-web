<?php

declare(strict_types=1);

namespace App\Services\AccountsPayable;

use App\Models\Creditor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class CreditorService
{
    public function listForCompany(int $companyId): Collection
    {
        return Creditor::query()
            ->forCompany($companyId)
            ->withCount('accountsPayable')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return Creditor::query()
            ->forCompany($companyId)
            ->withCount('accountsPayable')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data): Creditor
    {
        return Creditor::query()->create($data + ['company_id' => $companyId]);
    }

    public function findForCompany(int $companyId, int $creditorId): Creditor
    {
        return Creditor::query()
            ->forCompany($companyId)
            ->withCount('accountsPayable')
            ->whereKey($creditorId)
            ->firstOrFail();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Creditor $creditor, array $data): Creditor
    {
        $creditor->update($data);

        return $creditor->refresh();
    }

    public function delete(Creditor $creditor): void
    {
        if ($creditor->accountsPayable()->exists()) {
            throw new InvalidArgumentException('No puedes eliminar un acreedor que ya tiene cuentas por pagar registradas.');
        }

        $creditor->delete();
    }
}
