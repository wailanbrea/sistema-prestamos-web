<?php

declare(strict_types=1);

namespace App\Services\Loans;

use App\Models\Loan;
use Carbon\CarbonImmutable;

class InstallmentGeneratorService
{
    public function createForLoan(Loan $loan, array $calculation, bool $excludeSundays = false): void
    {
        $dueDate = CarbonImmutable::parse($loan->first_payment_date);

        foreach ($calculation['installments'] as $installment) {
            $loan->installments()->create([
                'installment_number' => $installment['number'],
                'due_date' => $dueDate->toDateString(),
                'principal_amount' => $installment['principal'],
                'interest_amount' => $installment['interest'],
                'installment_amount' => $installment['amount'],
            ]);

            $dueDate = $this->nextDueDate($dueDate, $loan->payment_frequency, $excludeSundays);
        }
    }

    private function nextDueDate(CarbonImmutable $date, string $frequency, bool $excludeSundays): CarbonImmutable
    {
        $nextDate = match ($frequency) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'biweekly' => $date->addWeeks(2),
            'monthly' => $date->addMonthNoOverflow(),
            default => $date->addMonthNoOverflow(),
        };

        while ($excludeSundays && $frequency === 'daily' && $nextDate->isSunday()) {
            $nextDate = $nextDate->addDay();
        }

        return $nextDate;
    }
}
