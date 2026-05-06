<?php

declare(strict_types=1);

namespace App\Services\Cash;

use App\Models\CashMovement;
use Illuminate\Database\Eloquent\Model;

class CashMovementService
{
    public function create(
        int $companyId,
        string $type,
        float $amount,
        string $direction,
        ?Model $reference = null,
        ?string $description = null,
        ?int $createdBy = null,
        ?string $movementDate = null,
    ): CashMovement {
        return CashMovement::query()->create([
            'company_id' => $companyId,
            'type' => $type,
            'amount' => $amount,
            'direction' => $direction,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'description' => $description,
            'movement_date' => $movementDate ?? now()->toDateString(),
            'created_by' => $createdBy,
        ]);
    }
}
