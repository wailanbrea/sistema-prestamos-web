<?php

declare(strict_types=1);

namespace App\Services\Collectors;

use App\Models\Collector;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class CollectorService
{
    /**
     * @param array<string, mixed> $filters
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
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data): Collector
    {
        return DB::transaction(function () use ($companyId, $data): Collector {
            $data = $this->normalizeCommission($data);
            $data['company_id'] = $companyId;
            $data['user_id'] = $this->resolveUserIdForCreate($companyId, $data);

            return Collector::query()->create($data);
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Collector $collector, array $data): Collector
    {
        $collector->update($this->normalizeCommission($data));

        return $collector->refresh();
    }

    public function findForCompany(int $companyId, int $collectorId): Collector
    {
        return Collector::query()
            ->with([
                'user:id,name,email',
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
     * @param array<string, mixed> $data
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
     * @param array<string, mixed> $data
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
