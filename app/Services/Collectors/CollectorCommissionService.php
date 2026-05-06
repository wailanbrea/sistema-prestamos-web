<?php

declare(strict_types=1);

namespace App\Services\Collectors;

use App\Models\Collector;
use App\Models\CollectorCommission;
use App\Models\Payment;

class CollectorCommissionService
{
    public function createForPayment(Payment $payment): ?CollectorCommission
    {
        if (! $payment->collector_id) {
            return null;
        }

        $collector = Collector::query()->findOrFail($payment->collector_id);

        if ($collector->commission_type === 'none' || (float) $collector->commission_value <= 0) {
            return null;
        }

        $baseAmount = (float) $payment->amount;
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
}
