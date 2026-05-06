<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DocumentService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return Document::query()
            ->with(['client:id,full_name', 'loan:id,loan_number', 'createdBy:id,name'])
            ->forCompany($companyId)
            ->when($filters['document_type'] ?? null, fn (Builder $query, string $type) => $query->where('document_type', $type))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhereHas('client', fn (Builder $client) => $client->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('loan', fn (Builder $loan) => $loan->where('loan_number', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function findForCompany(int $companyId, int $documentId): Document
    {
        return Document::query()
            ->with(['client:id,full_name', 'loan:id,loan_number', 'createdBy:id,name'])
            ->forCompany($companyId)
            ->whereKey($documentId)
            ->firstOrFail();
    }
}
