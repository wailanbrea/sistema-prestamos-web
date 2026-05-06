<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function build(int $companyId, array $filters = []): array
    {
        $dateFrom = Carbon::parse($filters['date_from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $dateTo = Carbon::parse($filters['date_to'] ?? now()->toDateString())->endOfDay();

        $payments = Payment::query()
            ->forCompany($companyId)
            ->where('status', 'valid')
            ->whereBetween('payment_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $expenses = Expense::query()
            ->forCompany($companyId)
            ->whereBetween('expense_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $activeLoans = Loan::query()
            ->forCompany($companyId)
            ->whereIn('status', ['active', 'late']);

        $totalPayments = (float) (clone $payments)->sum('amount');
        $interestCollected = (float) (clone $payments)->sum('interest_paid');
        $lateFeesCollected = (float) (clone $payments)->sum('late_fee_paid');
        $principalCollected = (float) (clone $payments)->sum('principal_paid');
        $totalExpenses = (float) (clone $expenses)->sum('amount');
        $activePrincipal = (float) (clone $activeLoans)->sum('remaining_balance');
        $disbursedInPeriod = (float) Loan::query()
            ->forCompany($companyId)
            ->whereBetween('start_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->sum('principal_amount');

        return [
            'period' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'summary' => [
                'total_payments' => $totalPayments,
                'principal_collected' => $principalCollected,
                'interest_collected' => $interestCollected,
                'late_fees_collected' => $lateFeesCollected,
                'total_expenses' => $totalExpenses,
                'gross_profit' => round($interestCollected + $lateFeesCollected, 2),
                'net_profit' => round($interestCollected + $lateFeesCollected - $totalExpenses, 2),
                'active_principal' => $activePrincipal,
                'disbursed_in_period' => $disbursedInPeriod,
                'active_loans' => (clone $activeLoans)->count(),
                'late_installments' => $this->lateInstallmentsQuery($companyId)->count(),
            ],
            'late_installments' => $this->lateInstallments($companyId),
            'by_collector' => $this->collectorPerformance($companyId, $dateFrom, $dateTo),
            'by_client' => $this->clientBalances($companyId),
        ];
    }

    /**
     * @return Collection<int, LoanInstallment>
     */
    private function lateInstallments(int $companyId): Collection
    {
        return $this->lateInstallmentsQuery($companyId)
            ->with(['loan:id,company_id,client_id,collector_id,loan_number', 'loan.client:id,full_name,phone', 'loan.collector:id,name'])
            ->orderBy('due_date')
            ->limit(100)
            ->get();
    }

    private function lateInstallmentsQuery(int $companyId)
    {
        return LoanInstallment::query()
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereHas('loan', fn ($query) => $query->forCompany($companyId)->whereIn('status', ['active', 'late']));
    }

    /**
     * @return Collection<int, object>
     */
    private function collectorPerformance(int $companyId, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        return Payment::query()
            ->select([
                'collectors.id',
                'collectors.name',
                DB::raw('COUNT(payments.id) as payments_count'),
                DB::raw('SUM(payments.amount) as total_collected'),
                DB::raw('SUM(payments.principal_paid) as principal_collected'),
                DB::raw('SUM(payments.interest_paid) as interest_collected'),
                DB::raw('SUM(payments.late_fee_paid) as late_fee_collected'),
            ])
            ->join('collectors', 'collectors.id', '=', 'payments.collector_id')
            ->where('payments.company_id', $companyId)
            ->where('payments.status', 'valid')
            ->whereBetween('payments.payment_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->groupBy('collectors.id', 'collectors.name')
            ->orderByDesc('total_collected')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function clientBalances(int $companyId): Collection
    {
        return Loan::query()
            ->select([
                'clients.id',
                'clients.full_name',
                'clients.phone',
                DB::raw('COUNT(loans.id) as loans_count'),
                DB::raw('SUM(loans.remaining_balance) as remaining_balance'),
                DB::raw('SUM(loans.paid_interest + loans.paid_late_fee) as profit_collected'),
            ])
            ->join('clients', 'clients.id', '=', 'loans.client_id')
            ->where('loans.company_id', $companyId)
            ->whereIn('loans.status', ['active', 'late'])
            ->groupBy('clients.id', 'clients.full_name', 'clients.phone')
            ->orderByDesc('remaining_balance')
            ->limit(100)
            ->get();
    }
}
