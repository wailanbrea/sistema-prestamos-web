<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Loans\LoanCalculatorService;
use PHPUnit\Framework\TestCase;

class LoanCalculatorServiceTest extends TestCase
{
    public function test_german_amortization_uses_fixed_principal_and_decreasing_interest(): void
    {
        $result = (new LoanCalculatorService())->calculate(
            principal: 3000,
            annualRate: 10,
            termQuantity: 3,
            method: 'german_amortization',
        );

        $this->assertSame(1300.0, $result['installment_amount']);
        $this->assertSame(600.0, $result['total_interest']);
        $this->assertSame(3600.0, $result['total_amount']);

        $this->assertSame([
            ['number' => 1, 'principal' => 1000.0, 'interest' => 300.0, 'amount' => 1300.0],
            ['number' => 2, 'principal' => 1000.0, 'interest' => 200.0, 'amount' => 1200.0],
            ['number' => 3, 'principal' => 1000.0, 'interest' => 100.0, 'amount' => 1100.0],
        ], $result['installments']);
    }

    public function test_personalized_amortization_uses_equal_capital_and_fixed_interest(): void
    {
        $result = (new LoanCalculatorService())->calculate(
            principal: 10000.0,
            annualRate: 11.0, // calculated from 1100 interest / 10000 principal * 100
            termQuantity: 10,
            method: 'personalized',
        );

        $this->assertSame(2100.0, $result['installment_amount']);
        $this->assertSame(11000.0, $result['total_interest']);
        $this->assertSame(21000.0, $result['total_amount']);

        $this->assertCount(10, $result['installments']);
        $first = $result['installments'][0];
        $this->assertSame(1, $first['number']);
        $this->assertSame(1000.0, $first['principal']);
        $this->assertSame(1100.0, $first['interest']);
        $this->assertSame(2100.0, $first['amount']);
    }
}
