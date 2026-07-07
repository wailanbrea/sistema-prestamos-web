<?php

declare(strict_types=1);

namespace App\Services\Collectors;

use App\Models\Collector;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class CollectorService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return Collector::query()
            ->with('user:id,name,email')
            ->forCompany($companyId)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['commission_type'] ?? null, fn (Builder $query, string $type) => $query->where('commission_type', $type))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, array $data): Collector
    {
        return DB::transaction(function () use ($companyId, $data): Collector {
            $data = $this->normalizeCommission($data);
            $loanIds = $this->loanIds($data);
            $data['company_id'] = $companyId;
            $data['user_id'] = $this->resolveUserIdForCreate($companyId, $data);

            $collector = Collector::query()->create($this->collectorAttributes($data));
            $this->syncAssignedLoans($collector, $loanIds);

            return $collector->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Collector $collector, array $data): Collector
    {
        DB::transaction(function () use ($collector, $data): void {
            $data = $this->normalizeCommission($data);
            $data['company_id'] = (int) $collector->company_id;

            $collector->update($this->collectorAttributes($data));
            $collector->refresh();
            $this->updateLinkedUser($collector, $data);
            $this->syncAssignedLoans($collector, $this->loanIds($data));
        });

        return $collector->refresh();
    }

    public function findForCompany(int $companyId, int $collectorId): Collector
    {
        return Collector::query()
            ->with([
                'user:id,name,email',
                'loans' => fn ($query) => $query
                    ->with('client:id,full_name')
                    ->whereIn('status', ['active', 'late'])
                    ->orderBy('loan_number'),
                'commissions' => fn ($query) => $query
                    ->with(['payment:id,receipt_number,payment_date,client_id,amount', 'payment.client:id,full_name'])
                    ->latest('id')
                    ->limit(50),
            ])
            ->forCompany($companyId)
            ->whereKey($collectorId)
            ->firstOrFail();
    }

    public function delete(Collector $collector): void
    {
        if ($collector->loans()->exists() || $collector->payments()->exists() || $collector->commissions()->exists()) {
            throw ValidationException::withMessages([
                'collector' => 'No se puede eliminar un cobrador con préstamos, cobros o comisiones vinculadas. Debe inactivarse.',
            ]);
        }

        $collector->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCommission(array $data): array
    {
        $data['user_id'] = $data['user_id'] ?? null;
        $data['access_mode'] = $data['access_mode'] ?? 'none';
        $data['commission_base'] = $data['commission_base'] ?? 'payment_total';
        $data['commission_value'] = $data['commission_type'] === 'none'
            ? 0
            : (float) ($data['commission_value'] ?? 0);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function collectorAttributes(array $data): array
    {
        return [
            'company_id' => $data['company_id'],
            'user_id' => $data['user_id'] ?? null,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'commission_type' => $data['commission_type'],
            'commission_value' => $data['commission_value'],
            'commission_base' => $data['commission_base'],
            'status' => $data['status'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    private function loanIds(array $data): array
    {
        return collect($data['loan_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $loanIds
     */
    private function syncAssignedLoans(Collector $collector, array $loanIds): void
    {
        Loan::query()
            ->forCompany((int) $collector->company_id)
            ->where('collector_id', $collector->id)
            ->when($loanIds !== [], fn ($query) => $query->whereNotIn('id', $loanIds))
            ->update(['collector_id' => null]);

        if ($loanIds === []) {
            return;
        }

        Loan::query()
            ->forCompany((int) $collector->company_id)
            ->whereIn('status', ['active', 'late'])
            ->whereIn('id', $loanIds)
            ->update(['collector_id' => $collector->id]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateLinkedUser(Collector $collector, array $data): void
    {
        if (! $collector->user_id) {
            return;
        }

        $attributes = [];

        if (filled($data['user_name'] ?? null)) {
            $attributes['name'] = $data['user_name'];
        }

        if (filled($data['user_email'] ?? null)) {
            $attributes['email'] = $data['user_email'];
        }

        if (filled($data['user_password'] ?? null)) {
            $attributes['password'] = $data['user_password'];
        }

        if ($attributes === []) {
            return;
        }

        $user = User::query()
            ->where('company_id', $collector->company_id)
            ->whereKey($collector->user_id)
            ->first();

        $user?->update($attributes);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveUserIdForCreate(int $companyId, array $data): ?int
    {
        if (($data['access_mode'] ?? 'none') === 'existing') {
            return isset($data['user_id']) ? (int) $data['user_id'] : null;
        }

        if (($data['access_mode'] ?? 'none') !== 'new') {
            return null;
        }

        $user = User::query()->create([
            'company_id' => $companyId,
            'name' => $data['user_name'] ?: $data['name'],
            'email' => $data['user_email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['user_password'],
            'status' => $data['status'] ?? 'active',
            'visible_menus' => null,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);
        $user->syncRoles(['Cobrador']);

        return (int) $user->id;
    }
}
