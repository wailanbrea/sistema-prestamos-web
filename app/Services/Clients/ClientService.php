<?php

declare(strict_types=1);

namespace App\Services\Clients;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ClientService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return Client::query()
            ->forCompany($companyId)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('full_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('identification', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['risk_level'] ?? null, fn (Builder $query, string $riskLevel) => $query->where('risk_level', $riskLevel))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data): Client
    {
        $data['company_id'] = $companyId;
        $data['monthly_income'] = $data['monthly_income'] ?? 0;

        return Client::query()->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Client $client, array $data): Client
    {
        $data['monthly_income'] = $data['monthly_income'] ?? 0;
        $client->update($data);

        return $client->refresh();
    }

    public function findForCompany(int $companyId, int $clientId): Client
    {
        return Client::query()
            ->forCompany($companyId)
            ->whereKey($clientId)
            ->firstOrFail();
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }
}
