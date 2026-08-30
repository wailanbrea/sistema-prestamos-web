<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\CashMovement;
use App\Models\Client;
use App\Models\Collector;
use App\Models\Expense;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * @return array<string, float|int|string>
     */
    public function summary(int $companyId): array
    {
        $today = now()->toDateString();

        $capitalPrestado = (float) Loan::query()
            ->forCompany($companyId)
            ->whereIn('status', ['active', 'late'])
            ->sum('remaining_balance');

        $cobrosHoy = (float) Payment::query()
            ->forCompany($companyId)
            ->where('status', 'valid')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $interesesGenerados = (float) Payment::query()
            ->forCompany($companyId)
            ->where('status', 'valid')
            ->sum('interest_paid');

        $gastosMes = (float) Expense::query()
            ->forCompany($companyId)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Capital disponible = saldo de caja (todo lo que entró menos lo que salió):
        // inyecciones de capital + cobros recibidos − desembolsos − gastos −
        // comisiones − retiros. Se calcula automáticamente desde los movimientos.
        $cashIn = (float) CashMovement::query()->forCompany($companyId)->where('direction', 'in')->sum('amount');
        $cashOut = (float) CashMovement::query()->forCompany($companyId)->where('direction', 'out')->sum('amount');
        $capitalDisponible = round($cashIn - $cashOut, 2);

        return [
            'capital_invertido' => $capitalPrestado,
            'capital_prestado' => $capitalPrestado,
            'capital_disponible' => $capitalDisponible,
            'cobros_hoy' => $cobrosHoy,
            'intereses_generados' => $interesesGenerados,
            'ganancia_neta' => round($interesesGenerados - $gastosMes, 2),
            'gastos_mes' => $gastosMes,
            'clientes_atrasados' => Client::query()->forCompany($companyId)->where('status', 'moroso')->count(),
            'clientes_sin_coordenadas' => Client::query()
                ->forCompany($companyId)
                ->where(function ($query): void {
                    $query->whereNull('latitude')->orWhereNull('longitude');
                })
                ->count(),
            'prestamos_activos' => Loan::query()->forCompany($companyId)->whereIn('status', ['active', 'late'])->count(),
            'prestamos_saldados' => Loan::query()->forCompany($companyId)->where('status', 'paid')->count(),
            'prestamos_mora' => $this->overdueLoanQuery($companyId)->count(),
            'cobradores_activos' => Collector::query()->forCompany($companyId)->where('status', 'active')->count(),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    public function todaySummary(int $companyId): array
    {
        $today = now()->toDateString();
        $start = now()->startOfDay()->subDays(6);
        $previousStart = $start->copy()->subDays(7);
        $previousEnd = $start->copy()->subDay();

        $scheduled = LoanInstallment::query()
            ->whereHas('loan', fn ($query) => $query->forCompany($companyId))
            ->whereDate('due_date', $today)
            ->whereNotIn('status', ['paid', 'cancelled']);
        $scheduledAmount = (float) $scheduled->get()->sum(fn (LoanInstallment $installment): float => max(0, (float) $installment->installment_amount - (float) $installment->total_paid));

        $paymentsToday = Payment::query()
            ->forCompany($companyId)
            ->where('status', 'valid')
            ->whereDate('payment_date', $today)
            ->get(['id', 'amount']);
        $collectedToday = (float) $paymentsToday->sum('amount');

        $currentWeek = (float) Payment::query()->forCompany($companyId)->where('status', 'valid')->whereBetween('payment_date', [$start->toDateString(), $today])->sum('amount');
        $previousWeek = (float) Payment::query()->forCompany($companyId)->where('status', 'valid')->whereBetween('payment_date', [$previousStart->toDateString(), $previousEnd->toDateString()])->sum('amount');

        return [
            'scheduled_count' => $scheduled->count(),
            'scheduled_amount' => $scheduledAmount,
            'collected_amount' => $collectedToday,
            'payments_count' => $paymentsToday->count(),
            'pending_amount' => max(0, $scheduledAmount - $collectedToday),
            'additional_amount' => max(0, round($collectedToday) - round($scheduledAmount)),
            'goal_progress' => $scheduledAmount > 0 ? round(($collectedToday / $scheduledAmount) * 100, 1) : 0.0,
            'collected_week' => $currentWeek,
            'week_change' => $previousWeek > 0 ? round((($currentWeek - $previousWeek) / $previousWeek) * 100, 1) : ($currentWeek > 0 ? 100.0 : 0.0),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    public function overdueAging(int $companyId): array
    {
        $buckets = ['1-7 días' => 0, '8-30 días' => 0, 'Más de 30 días' => 0];
        LoanInstallment::query()
            ->whereHas('loan', fn ($query) => $query->forCompany($companyId)->whereIn('status', ['active', 'late']))
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->get(['due_date'])
            ->each(function (LoanInstallment $installment) use (&$buckets): void {
                $days = $installment->due_date->diffInDays(now()->startOfDay());
                $key = $days <= 7 ? '1-7 días' : ($days <= 30 ? '8-30 días' : 'Más de 30 días');
                $buckets[$key]++;
            });

        return ['labels' => array_keys($buckets), 'values' => array_values($buckets)];
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    public function collectorPerformance(int $companyId): array
    {
        $rows = Payment::query()
            ->forCompany($companyId)
            ->where('status', 'valid')
            ->whereDate('payment_date', '>=', now()->subDays(30)->toDateString())
            ->with('collector:id,name')
            ->get()
            ->groupBy(fn (Payment $payment): string => $payment->collector?->name ?? 'Sin cobrador')
            ->map(fn (Collection $payments): float => round((float) $payments->sum('amount'), 2))
            ->sortDesc()
            ->take(6);

        return ['labels' => $rows->keys()->values()->all(), 'values' => $rows->values()->all()];
    }

    /**
     * @return Collection<int, LoanInstallment>
     */
    public function upcomingDue(int $companyId, int $limit = 6): Collection
    {
        return LoanInstallment::query()
            ->whereHas('loan', fn ($query) => $query->forCompany($companyId)->whereIn('status', ['active', 'late']))
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->with('loan.client')
            ->orderBy('due_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Daily collected totals for the last N days (oldest first).
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    public function collectionsTrend(int $companyId, int $days = 14): array
    {
        $start = now()->startOfDay()->subDays($days - 1);

        /** @var Collection<string, float> $totals */
        $totals = Payment::query()
            ->forCompany($companyId)
            ->where('status', 'valid')
            ->whereDate('payment_date', '>=', $start->toDateString())
            ->get(['payment_date', 'amount'])
            ->groupBy(fn (Payment $payment): string => $payment->payment_date->toDateString())
            ->map(fn (Collection $group): float => (float) $group->sum('amount'));

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->locale('es')->isoFormat('DD MMM');
            $values[] = round((float) ($totals[$date->toDateString()] ?? 0), 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Loan portfolio distribution by status for the donut chart.
     *
     * @return array<string, int>
     */
    public function loanStatusDistribution(int $companyId): array
    {
        $paid = (int) Loan::query()
            ->forCompany($companyId)
            ->where('status', 'paid')
            ->count();

        $late = (int) $this->overdueLoanQuery($companyId)->count();

        $active = (int) Loan::query()
            ->forCompany($companyId)
            ->whereIn('status', ['active', 'late'])
            ->whereDoesntHave('installments', fn ($query) => $this->scopeOverdueInstallments($query))
            ->count();

        return [
            'active' => $active,
            'late' => $late,
            'paid' => $paid,
        ];
    }

    /**
     * Most recent loans with client and collector eager loaded.
     *
     * @return Collection<int, Loan>
     */
    public function recentLoans(int $companyId, int $limit = 6): Collection
    {
        return Loan::query()
            ->forCompany($companyId)
            ->with(['client:id,full_name', 'collector:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Most recent valid payments with client and collector eager loaded.
     *
     * @return Collection<int, Payment>
     */
    public function recentPayments(int $companyId, int $limit = 6): Collection
    {
        return Payment::query()
            ->forCompany($companyId)
            ->where('status', 'valid')
            ->with(['client:id,full_name', 'collector:id,name', 'loan:id,loan_number'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    private function overdueLoanQuery(int $companyId)
    {
        return Loan::query()
            ->forCompany($companyId)
            ->whereIn('status', ['active', 'late'])
            ->whereHas('installments', fn ($query) => $this->scopeOverdueInstallments($query));
    }

    private function scopeOverdueInstallments($query)
    {
        return $query
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->whereDate('due_date', '<', now()->toDateString());
    }
}
