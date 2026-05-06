<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\CollectorCommission;
use App\Models\Company;
use App\Models\Loan;
use App\Models\Payment;
use Database\Seeders\DemoLoanPortfolioSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLoanPortfolioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_portfolio_has_coherent_financial_calculations(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoLoanPortfolioSeeder::class);

        $company = Company::query()
            ->where('email', 'admin@sistemaprestamista.local')
            ->firstOrFail();

        $collector = Collector::query()
            ->where('company_id', $company->id)
            ->where('name', 'Carlos Cobrador')
            ->firstOrFail();

        $monthlyLoan = Loan::query()
            ->with('installments')
            ->where('company_id', $company->id)
            ->whereHas('client', fn ($query) => $query->where('full_name', 'María Rodríguez'))
            ->firstOrFail();

        $this->assertSame(120000.00, (float) $monthlyLoan->principal_amount);
        $this->assertSame(14400.00, (float) $monthlyLoan->total_interest);
        $this->assertSame(134400.00, (float) $monthlyLoan->total_amount);
        $this->assertSame(22400.00, (float) $monthlyLoan->installment_amount);
        $this->assertSame(6, $monthlyLoan->installments->count());

        $weeklyLoan = Loan::query()
            ->with('installments')
            ->where('company_id', $company->id)
            ->whereHas('client', fn ($query) => $query->where('full_name', 'José Martínez'))
            ->firstOrFail();

        $this->assertSame(25000.00, (float) $weeklyLoan->principal_amount);
        $this->assertSame(10000.00, (float) $weeklyLoan->total_interest);
        $this->assertSame(35000.00, (float) $weeklyLoan->total_amount);
        $this->assertSame(4375.00, (float) $weeklyLoan->installment_amount);
        $this->assertSame(3125.00, (float) $weeklyLoan->paid_principal);
        $this->assertSame(1250.00, (float) $weeklyLoan->paid_interest);
        $this->assertSame(21875.00, (float) $weeklyLoan->remaining_balance);

        $this->assertDatabaseHas('payments', [
            'company_id' => $company->id,
            'loan_id' => $weeklyLoan->id,
            'collector_id' => $collector->id,
            'amount' => 4375,
            'principal_paid' => 3125,
            'interest_paid' => 1250,
            'status' => 'valid',
        ]);

        $this->assertDatabaseHas('collector_commissions', [
            'company_id' => $company->id,
            'collector_id' => $collector->id,
            'base_amount' => 4375,
            'commission_amount' => 218.75,
            'status' => 'pending',
        ]);

        $dailyLoan = Loan::query()
            ->with('installments')
            ->where('company_id', $company->id)
            ->whereHas('client', fn ($query) => $query->where('full_name', 'Ana Pérez'))
            ->firstOrFail();

        $firstDailyInstallment = $dailyLoan->installments->sortBy('installment_number')->firstOrFail();

        $this->assertSame('late', $dailyLoan->status);
        $this->assertSame(15000.00, (float) $dailyLoan->principal_amount);
        $this->assertSame(1500.00, (float) $dailyLoan->total_interest);
        $this->assertSame(16500.00, (float) $dailyLoan->total_amount);
        $this->assertSame(1650.00, (float) $dailyLoan->installment_amount);
        $this->assertSame(15, (int) $firstDailyInstallment->days_late);
        $this->assertSame(1125.00, (float) $firstDailyInstallment->late_fee);

        $this->assertSame('moroso', Client::query()
            ->where('company_id', $company->id)
            ->where('full_name', 'Ana Pérez')
            ->value('status'));

        $this->assertSame(3, Loan::query()
            ->where('company_id', $company->id)
            ->where('notes', 'like', '%DEMO_PORTFOLIO_V1%')
            ->count());
        $this->assertSame(1, Payment::query()->where('company_id', $company->id)->count());
        $this->assertSame(1, CollectorCommission::query()->where('company_id', $company->id)->count());
    }
}
