<?php

declare(strict_types=1);

namespace App\Services\Collectors;

use App\Models\Collector;
use App\Models\CollectorCommission;
use App\Models\Payment;
use App\Services\Cash\CashMovementService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CollectorCommissionService
{
    public function __construct(
        private readonly CashMovementService $cashMovementService,
    ) {
    }

    public function createForPayment(Payment $payment): ?CollectorCommission
    {
        if (! $payment->collector_id) {
            return null;
        }

        $collector = Collector::query()->findOrFail($payment->collector_id);

        if ($collector->commission_type === 'none' || (float) $collector->commission_value <= 0) {
            return null;
        }

        $baseAmount = $collector->commission_base === 'principal_only'
            ? (float) $payment->principal_paid
            : (float) $payment->amount;
        $commissionAmount = $collector->commission_type === 'percentage'
            ? round($baseAmount * ((float) $collector->commission_value / 100), 2)
            : (float) $collector->commission_value;

        return CollectorCommission::query()->create([
            'company_id' => $payment->company_id,
            'collector_id' => $collector->id,
            'payment_id' => $payment->id,
            'commission_type' => $collector->commission_type,
            'commission_value' => $collector->commission_value,
            'base_amount' => $baseAmount,
            'commission_amount' => $commissionAmount,
        ]);
    }

    /**
     * @return array{total_generated: float, total_pending: float, total_paid: float, total_cancelled: float, total_collected: float}
     */
    public function summaryForCollector(int $companyId, int $collectorId): array
    {
        $baseQuery = CollectorCommission::query()
            ->where('company_id', $companyId)
            ->where('collector_id', $collectorId);

        return [
            'total_generated' => round((float) (clone $baseQuery)->sum('commission_amount'), 2),
            'total_pending' => round((float) (clone $baseQuery)->where('status', 'pending')->sum('commission_amount'), 2),
            'total_paid' => round((float) (clone $baseQuery)->where('status', 'paid')->sum('commission_amount'), 2),
            'total_cancelled' => round((float) (clone $baseQuery)->where('status', 'cancelled')->sum('commission_amount'), 2),
            'total_collected' => round((float) (clone $baseQuery)->sum('base_amount'), 2),
        ];
    }

    public function pay(int $companyId, int $collectorId, int $commissionId, int $paidBy): CollectorCommission
    {
        return DB::transaction(function () use ($companyId, $collectorId, $commissionId, $paidBy): CollectorCommission {
            $commission = CollectorCommission::query()
                ->with(['collector:id,name', 'payment:id,receipt_number'])
                ->where('company_id', $companyId)
                ->where('collector_id', $collectorId)
                ->whereKey($commissionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($commission->status !== 'pending') {
                throw new InvalidArgumentException('Solo se pueden pagar comisiones pendientes.');
            }

            $commission->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
            ])->save();

            $collectorName = $commission->collector?->name ?: 'Cobrador';
            $receipt = $commission->payment?->receipt_number ?: ('pago #'.$commission->payment_id);

            $this->cashMovementService->create(
                companyId: $companyId,
                type: 'collector_commission',
                amount: (float) $commission->commission_amount,
                direction: 'out',
                reference: $commission,
                description: "Pago comision a {$collectorName} por {$receipt}",
                createdBy: $paidBy,
            );

            return $commission->fresh(['collector', 'payment']) ?? $commission;
        });
    }
}
