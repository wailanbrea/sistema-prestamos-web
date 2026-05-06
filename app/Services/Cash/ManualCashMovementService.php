<?php

declare(strict_types=1);

namespace App\Services\Cash;

use App\Models\CashMovement;
use App\Services\Audit\AuditService;

class ManualCashMovementService
{
    public function __construct(
        private readonly CashMovementService $cashMovementService,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data, int $createdBy): CashMovement
    {
        $direction = match ($data['type']) {
            'capital_injection' => 'in',
            'capital_withdrawal' => 'out',
            default => $data['direction'],
        };

        $movement = $this->cashMovementService->create(
            companyId: $companyId,
            type: $data['type'],
            amount: (float) $data['amount'],
            direction: $direction,
            description: $data['description'],
            createdBy: $createdBy,
            movementDate: $data['movement_date'],
        );

        $this->auditService->record(
            action: 'manual_cash_movement_created',
            module: 'cash',
            companyId: $companyId,
            userId: $createdBy,
            auditable: $movement,
            description: "Movimiento manual de caja registrado: {$movement->type}.",
            newValues: $movement->toArray(),
        );

        return $movement;
    }
}
