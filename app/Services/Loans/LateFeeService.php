<?php

declare(strict_types=1);

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanInstallment;
use Carbon\CarbonImmutable;

class LateFeeService
{
    public function refreshInstallment(Loan $loan, LoanInstallment $installment, ?CarbonImmutable $today = null): LoanInstallment
    {
        $today ??= CarbonImmutable::today();
        $dueDate = CarbonImmutable::parse($installment->due_date);
        $daysLate = $dueDate->lessThan($today) && ! in_array($installment->status, ['paid', 'cancelled'], true)
            ? (int) max(0, $dueDate->diffInDays($today))
            : 0;

        if ($installment->late_fee_waived_at !== null) {
            $installment->forceFill([
                'days_late' => $daysLate,
                'late_fee' => (float) $installment->paid_late_fee,
                'status' => $this->resolveStatus($installment, $daysLate),
            ])->save();

            return $installment;
        }

        $lateFee = match ($loan->late_fee_type) {
            'fixed' => $daysLate > 0 ? (float) $loan->late_fee_value : 0.0,
            'daily_percentage' => round(((float) $installment->installment_amount * ((float) $loan->late_fee_value / 100)) * $daysLate, 2),
            'daily_fixed' => round((float) $loan->late_fee_value * $daysLate, 2),
            default => 0.0,
        };

        $installment->forceFill([
            'days_late' => $daysLate,
            'late_fee' => $lateFee,
            'status' => $this->resolveStatus($installment, $daysLate),
        ])->save();

        return $installment;
    }

    private function resolveStatus(LoanInstallment $installment, int $daysLate): string
    {
        if (in_array($installment->status, ['paid', 'cancelled'], true)) {
            return $installment->status;
        }

        if ($daysLate > 0) {
            return 'late';
        }

        return (float) $installment->total_paid > 0 ? 'partial' : 'pending';
    }
}
