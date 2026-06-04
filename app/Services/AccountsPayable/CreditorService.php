<?php

declare(strict_types=1);

namespace App\Services\AccountsPayable;

use App\Models\Creditor;
use Illuminate\Database\Eloquent\Collection;

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
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data): Creditor
    {
        return Creditor::query()->create($data + ['company_id' => $companyId]);
    }
}
