<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Collector;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Services\Dashboard\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService): View
    {
        $companyId = (int) $request->user()->company_id;

        if (! $request->user()->can('dashboard.view')) {
            abort_unless($request->user()->can('collector.access'), 403);

            $collector = Collector::query()
                ->forCompany($companyId)
                ->where('user_id', $request->user()->id)
                ->where('status', 'active')
                ->firstOrFail();
            $loanQuery = Loan::query()
                ->forCompany($companyId)
                ->where('collector_id', $collector->id)
                ->whereIn('status', ['active', 'late']);

            return view('dashboard-collector', [
                'collector' => $collector,
                'loans' => (clone $loanQuery)
                    ->with('client:id,full_name,phone')
                    ->orderByRaw("case when status = 'late' then 0 else 1 end")
                    ->orderBy('loan_number')
                    ->get(),
                'metrics' => [
                    'active_loans' => (clone $loanQuery)->count(),
                    'late_loans' => (clone $loanQuery)->where('status', 'late')->count(),
                    'remaining_balance' => (float) (clone $loanQuery)->sum('remaining_balance'),
                    'pending_installments' => LoanInstallment::query()
                        ->whereIn('status', ['pending', 'partial', 'late'])
                        ->whereHas('loan', fn ($query) => $query
                            ->forCompany($companyId)
                            ->where('collector_id', $collector->id))
                        ->count(),
                ],
                'recentPayments' => Payment::query()
                    ->forCompany($companyId)
                    ->where('collector_id', $collector->id)
                    ->where('status', 'valid')
                    ->whereHas('loan', fn ($query) => $query->where('collector_id', $collector->id))
                    ->with(['client:id,full_name', 'loan:id,loan_number,collector_id'])
                    ->orderByDesc('payment_date')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get(),
            ]);
        }

        return view('dashboard', [
            'metrics'          => $dashboardService->summary($companyId),
            'todaySummary'     => $dashboardService->todaySummary($companyId),
            'collectionsTrend' => $dashboardService->collectionsTrend($companyId, 7),
            'loanDistribution' => $dashboardService->loanStatusDistribution($companyId),
            'overdueAging'     => $dashboardService->overdueAging($companyId),
            'collectorPerformance' => $dashboardService->collectorPerformance($companyId),
            'upcomingDue'      => $dashboardService->upcomingDue($companyId),
            'recentPayments'   => $dashboardService->recentPayments($companyId),
            'recentLoans'      => $dashboardService->recentLoans($companyId),
        ]);
    }
}
