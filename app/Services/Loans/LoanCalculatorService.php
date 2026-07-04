<?php

declare(strict_types=1);

namespace App\Services\Loans;

use InvalidArgumentException;

class LoanCalculatorService
{
    /**
     * @return array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>}
     */
    public function calculate(float $principal, float $annualRate, int $termQuantity, string $method): array
    {
        if ($principal <= 0 || $annualRate < 0 || $termQuantity <= 0) {
            throw new InvalidArgumentException('Monto, tasa y plazo deben ser valores financieros válidos.');
        }

        return match ($method) {
            'flat_interest' => $this->flatInterest($principal, $annualRate, $termQuantity),
            'fixed_installment' => $this->fixedInstallment($principal, $annualRate, $termQuantity),
            'capital_plus_interest' => $this->capitalPlusInterest($principal, $annualRate, $termQuantity),
            'interest_only' => $this->interestOnly($principal, $annualRate, $termQuantity),
            'german_amortization' => $this->germanAmortization($principal, $annualRate, $termQuantity),
            'french_amortization' => $this->frenchAmortization($principal, $annualRate, $termQuantity),
            default => throw new InvalidArgumentException("Método de cálculo no soportado: {$method}."),
        };
    }

    /**
     * @return array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>}
     */
    private function flatInterest(float $principal, float $annualRate, int $termQuantity): array
    {
        $totalInterest = $this->money($principal * ($annualRate / 100));
        $totalAmount = $this->money($principal + $totalInterest);
        $installmentAmount = $this->money($totalAmount / $termQuantity);
        $principalPerInstallment = $this->money($principal / $termQuantity);
        $interestPerInstallment = $this->money($totalInterest / $termQuantity);

        return $this->result($installmentAmount, $totalInterest, $totalAmount, $termQuantity, $principalPerInstallment, $interestPerInstallment);
    }

    /**
     * @return array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>}
     */
    private function fixedInstallment(float $principal, float $annualRate, int $termQuantity): array
    {
        return $this->flatInterest($principal, $annualRate, $termQuantity);
    }

    /**
     * @return array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>}
     */
    private function capitalPlusInterest(float $principal, float $annualRate, int $termQuantity): array
    {
        $principalPerInstallment = $this->money($principal / $termQuantity);
        $interestPerInstallment = $this->money($principal * ($annualRate / 100));
        $installmentAmount = $this->money($principalPerInstallment + $interestPerInstallment);
        $totalInterest = $this->money($interestPerInstallment * $termQuantity);

        return $this->result($installmentAmount, $totalInterest, $this->money($principal + $totalInterest), $termQuantity, $principalPerInstallment, $interestPerInstallment);
    }

    /**
     * @return array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>}
     */
    private function interestOnly(float $principal, float $annualRate, int $termQuantity): array
    {
        $interestPerInstallment = $this->money($principal * ($annualRate / 100));
        $installments = [];

        for ($number = 1; $number <= $termQuantity; $number++) {
            $principalAmount = $number === $termQuantity ? $principal : 0.0;
            $installments[] = [
                'number' => $number,
                'principal' => $this->money($principalAmount),
                'interest' => $interestPerInstallment,
                'amount' => $this->money($principalAmount + $interestPerInstallment),
            ];
        }

        $totalInterest = $this->money($interestPerInstallment * $termQuantity);

        return [
            'installment_amount' => $installments[0]['amount'],
            'total_interest' => $totalInterest,
            'total_amount' => $this->money($principal + $totalInterest),
            'installments' => $installments,
        ];
    }

    /**
     * @return array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>}
     */
    private function germanAmortization(float $principal, float $annualRate, int $termQuantity): array
    {
        $rate = $annualRate / 100;
        $principalPerInstallment = $this->money($principal / $termQuantity);
        $balance = $principal;
        $installments = [];
        $totalInterest = 0.0;

        for ($number = 1; $number <= $termQuantity; $number++) {
            $principalAmount = $number === $termQuantity ? $this->money($balance) : $principalPerInstallment;
            $interest = $this->money($balance * $rate);
            $amount = $this->money($principalAmount + $interest);
            $balance = $this->money($balance - $principalAmount);
            $totalInterest = $this->money($totalInterest + $interest);

            $installments[] = [
                'number' => $number,
                'principal' => $principalAmount,
                'interest' => $interest,
                'amount' => $amount,
            ];
        }

        return [
            'installment_amount' => $installments[0]['amount'],
            'total_interest' => $totalInterest,
            'total_amount' => $this->money($principal + $totalInterest),
            'installments' => $installments,
        ];
    }

    /**
     * @return array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>}
     */
    private function frenchAmortization(float $principal, float $annualRate, int $termQuantity): array
    {
        $rate = $annualRate / 100;
        $installmentAmount = $rate === 0.0
            ? $this->money($principal / $termQuantity)
            : $this->money($principal * ($rate / (1 - ((1 + $rate) ** (-$termQuantity)))));

        $balance = $principal;
        $installments = [];
        $totalInterest = 0.0;

        for ($number = 1; $number <= $termQuantity; $number++) {
            $interest = $this->money($balance * $rate);
            $principalAmount = $number === $termQuantity ? $this->money($balance) : $this->money($installmentAmount - $interest);
            $amount = $this->money($principalAmount + $interest);
            $balance = $this->money($balance - $principalAmount);
            $totalInterest = $this->money($totalInterest + $interest);

            $installments[] = [
                'number' => $number,
                'principal' => $principalAmount,
                'interest' => $interest,
                'amount' => $amount,
            ];
        }

        return [
            'installment_amount' => $installmentAmount,
            'total_interest' => $totalInterest,
            'total_amount' => $this->money($principal + $totalInterest),
            'installments' => $installments,
        ];
    }

    /**
     * @return array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>}
     */
    private function result(float $installmentAmount, float $totalInterest, float $totalAmount, int $termQuantity, float $principalPerInstallment, float $interestPerInstallment): array
    {
        $installments = [];

        for ($number = 1; $number <= $termQuantity; $number++) {
            $installments[] = [
                'number' => $number,
                'principal' => $principalPerInstallment,
                'interest' => $interestPerInstallment,
                'amount' => $installmentAmount,
            ];
        }

        return compact('installmentAmount') + [
            'installment_amount' => $installmentAmount,
            'total_interest' => $totalInterest,
            'total_amount' => $totalAmount,
            'installments' => $installments,
        ];
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }
}
