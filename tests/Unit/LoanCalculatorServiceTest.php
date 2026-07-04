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
}
