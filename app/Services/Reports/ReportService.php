<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Client;
use App\Models\Collector;
use App\Models\CollectorCommission;
use App\Models\Expense;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio de reportes financieros, operativos y de cartera. Cada método recibe
 * un ReportScope (empresa + rol + dimensiones) y los ReportFilters (período), y
 * devuelve un array con la forma { rows, totals, period, meta } lista para la
 * vista y los exportadores. Todas las consultas usan agregados portables
 * (SUM/COUNT, groupBy sobre columnas reales) para funcionar igual en MySQL y en
 * SQLite (los tests corren en SQLite).
 */
class ReportService
{
    /** Estados de préstamo considerados "activos" (con cartera viva). */
    public const ACTIVE_LOAN_STATUSES = ['active', 'late'];

    /** Estados que cuentan como un desembolso real (entrega de dinero). */
    public const DISBURSED_LOAN_STATUSES = ['active', 'late', 'paid', 'refinanced', 'legal', 'written_off'];

    /** Umbral mínimo de % pagado para sugerir renovación. */
    private const RENEWAL_MIN_PAID_RATIO = 0.70;

    /** % de saldo pendiente por debajo del cual el préstamo está "casi liquidado". */
    private const RENEWAL_MAX_REMAINING_RATIO = 0.20;

    // ---------------------------------------------------------------------
    // 1) Resumen semanal de pagos y préstamos (por día)
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getWeeklySummary(ReportScope $scope, ReportFilters $filters): array
    {
        $from = $filters->dateFrom->copy()->startOfDay();
        $to = $filters->dateTo->copy()->endOfDay();

        $payments = $this->paymentTotalsByDay($scope, $from, $to);
        $disbursed = $this->disbursedTotalsByDay($scope, $from, $to);
        $expenses = $this->expenseTotalsByDay($scope, $from, $to);

        $rows = [];
        $totals = $this->emptyTotals();

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $p = $payments[$key] ?? null;
            $d = $disbursed[$key] ?? null;

            $capital = (float) ($p->capital ?? 0);
            $interest = (float) ($p->interest ?? 0);
            $lateFee = (float) ($p->late_fee ?? 0);
            $disbursedAmount = (float) ($d->amount ?? 0);
            $disbursedCount = (int) ($d->cnt ?? 0);
            $expenseAmount = (float) ($expenses[$key] ?? 0);
            $collected = $capital + $interest + $lateFee;

            $rows[] = [
                'date' => $key,
                'label' => $day->locale('es')->translatedFormat('l d/m'),
                'capital' => $capital,
                'interest' => $interest,
                'late_fee' => $lateFee,
                'disbursed' => $disbursedAmount,
                'disbursed_count' => $disbursedCount,
                'expenses' => $expenseAmount,
                'collected' => $collected,
            ];

            $totals['capital'] += $capital;
            $totals['interest'] += $interest;
            $totals['late_fee'] += $lateFee;
            $totals['disbursed'] += $disbursedAmount;
            $totals['disbursed_count'] += $disbursedCount;
            $totals['expenses'] += $expenseAmount;
            $totals['collected'] += $collected;
        }

        $totals['net_balance'] = $totals['collected'] - $totals['disbursed'] - $totals['expenses'];

        return [
            'rows' => $rows,
            'totals' => $totals,
            'period' => $this->period($filters),
        ];
    }

    // ---------------------------------------------------------------------
    // 2) Resumen semanal consolidado por rutas / cobradores
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getConsolidatedWeeklySummary(ReportScope $scope, ReportFilters $filters): array
    {
        $from = $filters->dateFrom->copy()->startOfDay();
        $to = $filters->dateTo->copy()->endOfDay();

        // Cobros agregados por cobrador en el período.
        $collected = $this->basePayments($scope)
            ->selectRaw('payments.collector_id, SUM(payments.principal_paid) as capital, SUM(payments.interest_paid) as interest, SUM(payments.late_fee_paid) as late_fee, COUNT(payments.id) as payments_count')
            ->whereBetween('payments.payment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('payments.collector_id')
            ->get()
            ->keyBy('collector_id');

        // Entregas agregadas por cobrador en el período.
        $disbursed = $this->baseLoans($scope)
            ->selectRaw('loans.collector_id, SUM(loans.principal_amount) as amount, COUNT(loans.id) as cnt')
            ->whereIn('loans.status', self::DISBURSED_LOAN_STATUSES)
            ->whereBetween('loans.start_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('loans.collector_id')
            ->get()
            ->keyBy('collector_id');

        // Conteo de cartera activa por cobrador (independiente del período).
        $activeLoans = $this->baseLoans($scope)
            ->selectRaw('loans.collector_id, COUNT(loans.id) as cnt')
            ->whereIn('loans.status', self::ACTIVE_LOAN_STATUSES)
            ->groupBy('loans.collector_id')
            ->get()
            ->keyBy('collector_id');

        $overdueLoans = $this->baseLoans($scope)
            ->selectRaw('loans.collector_id, COUNT(loans.id) as cnt')
            ->whereIn('loans.status', self::ACTIVE_LOAN_STATUSES)
            ->whereHas('installments', fn (Builder $q) => $this->scopeOverdueInstallments($q))
            ->groupBy('loans.collector_id')
            ->get()
            ->keyBy('collector_id');

        $collectors = Collector::query()
            ->where('company_id', $scope->companyId())
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = [];
        $totals = ['capital' => 0.0, 'interest' => 0.0, 'late_fee' => 0.0, 'disbursed' => 0.0, 'collected' => 0.0, 'active' => 0, 'overdue' => 0];

        foreach ($collectors as $collector) {
            $c = $collected[$collector->id] ?? null;
            $d = $disbursed[$collector->id] ?? null;

            $capital = (float) ($c->capital ?? 0);
            $interest = (float) ($c->interest ?? 0);
            $lateFee = (float) ($c->late_fee ?? 0);
            $collectedTotal = $capital + $interest + $lateFee;
            $disbursedAmount = (float) ($d->amount ?? 0);
            $active = (int) ($activeLoans[$collector->id]->cnt ?? 0);
            $overdue = (int) ($overdueLoans[$collector->id]->cnt ?? 0);

            // No mostrar cobradores sin actividad ni cartera.
            if ($collectedTotal === 0.0 && $disbursedAmount === 0.0 && $active === 0) {
                continue;
            }

            $rows[] = [
                'collector' => $collector->name,
                'capital' => $capital,
                'interest' => $interest,
                'late_fee' => $lateFee,
                'collected' => $collectedTotal,
                'disbursed' => $disbursedAmount,
                'active_accounts' => $active,
                'overdue_accounts' => $overdue,
            ];

            $totals['capital'] += $capital;
            $totals['interest'] += $interest;
            $totals['late_fee'] += $lateFee;
            $totals['collected'] += $collectedTotal;
            $totals['disbursed'] += $disbursedAmount;
            $totals['active'] += $active;
            $totals['overdue'] += $overdue;
        }

        // Los gastos no están ligados a cobrador: total a nivel empresa.
        $totals['expenses'] = (float) $this->scope($scope, Expense::query(), 'expenses')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
        $totals['net_balance'] = $totals['collected'] - $totals['disbursed'] - $totals['expenses'];

        return [
            'rows' => $rows,
            'totals' => $totals,
            'period' => $this->period($filters),
        ];
    }

    // ---------------------------------------------------------------------
    // 3) Resumen anual (por mes)
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getAnnualSummary(ReportScope $scope, ReportFilters $filters): array
    {
        $year = $filters->year ?? (int) $filters->dateFrom->year;
        [$from, $to] = ReportFilters::yearRange($year);

        $payments = $this->paymentTotalsByDay($scope, $from, $to);
        $disbursed = $this->disbursedTotalsByDay($scope, $from, $to);
        $expenses = $this->expenseTotalsByDay($scope, $from, $to);

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = [
                'month' => $m,
                'label' => Carbon::create($year, $m, 1)->locale('es')->translatedFormat('F'),
                'capital' => 0.0, 'interest' => 0.0, 'late_fee' => 0.0,
                'disbursed' => 0.0, 'disbursed_count' => 0, 'expenses' => 0.0,
                'collected' => 0.0, 'net_balance' => 0.0,
            ];
        }

        foreach ($payments as $key => $p) {
            $m = (int) Carbon::parse($key)->month;
            $months[$m]['capital'] += (float) $p->capital;
            $months[$m]['interest'] += (float) $p->interest;
            $months[$m]['late_fee'] += (float) $p->late_fee;
        }
        foreach ($disbursed as $key => $d) {
            $m = (int) Carbon::parse($key)->month;
            $months[$m]['disbursed'] += (float) $d->amount;
            $months[$m]['disbursed_count'] += (int) $d->cnt;
        }
        foreach ($expenses as $key => $amount) {
            $m = (int) Carbon::parse($key)->month;
            $months[$m]['expenses'] += (float) $amount;
        }

        $totals = $this->emptyTotals();
        foreach ($months as $m => $row) {
            $collected = $row['capital'] + $row['interest'] + $row['late_fee'];
            $months[$m]['collected'] = $collected;
            $months[$m]['net_balance'] = $collected - $row['disbursed'] - $row['expenses'];

            $totals['capital'] += $row['capital'];
            $totals['interest'] += $row['interest'];
            $totals['late_fee'] += $row['late_fee'];
            $totals['disbursed'] += $row['disbursed'];
            $totals['disbursed_count'] += $row['disbursed_count'];
            $totals['expenses'] += $row['expenses'];
            $totals['collected'] += $collected;
        }
        $totals['net_balance'] = $totals['collected'] - $totals['disbursed'] - $totals['expenses'];

        return [
            'rows' => array_values($months),
            'totals' => $totals,
            'period' => ['label' => 'Año '.$year, 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString()],
            'meta' => ['year' => $year, 'available_years' => $this->availableYears($scope)],
        ];
    }

    // ---------------------------------------------------------------------
    // 4) Préstamos entregados
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getDisbursedLoansReport(ReportScope $scope, ReportFilters $filters): array
    {
        $from = $filters->dateFrom->copy()->startOfDay();
        $to = $filters->dateTo->copy()->endOfDay();

        $query = $this->baseLoans($scope)
            ->with(['client:id,full_name,code,phone', 'collector:id,name'])
            ->whereIn('loans.status', array_merge(self::DISBURSED_LOAN_STATUSES, ['cancelled']))
            ->whereBetween('loans.start_date', [$from->toDateString(), $to->toDateString()]);

        if ($search = $filters->search) {
            $query->whereHas('client', function (Builder $q) use ($search): void {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $loans = $query->orderByDesc('loans.start_date')->get();

        $rows = $loans->map(fn (Loan $loan): array => [
            'client' => $loan->client?->full_name,
            'code' => $loan->client?->code,
            'phone' => $loan->client?->phone,
            'loan_number' => $loan->loan_number,
            'date' => $loan->start_date?->format('d/m/Y'),
            'amount' => (float) $loan->principal_amount,
            'collector' => $loan->collector?->name ?? 'Sin cobrador',
            'route' => $this->primaryRouteName($loan->client_id),
            'status' => $this->loanStatusLabel($loan->status),
        ])->all();

        return [
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'amount' => (float) $loans->sum('principal_amount'),
            ],
            'period' => $this->period($filters),
        ];
    }

    // ---------------------------------------------------------------------
    // 5) Clientes elegibles para renovar
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getRenewalEligibleClients(ReportScope $scope, ReportFilters $filters): array
    {
        $loans = $this->baseLoans($scope)
            ->with(['client:id,full_name,phone', 'collector:id,name'])
            ->whereIn('loans.status', self::ACTIVE_LOAN_STATUSES)
            ->get();

        $today = now()->startOfDay();
        $rows = [];

        foreach ($loans as $loan) {
            $total = (float) $loan->total_amount;
            $paid = (float) $loan->paid_principal + (float) $loan->paid_interest;
            $remaining = (float) $loan->remaining_balance;
            $paidRatio = $total > 0 ? $paid / $total : 0.0;
            $remainingRatio = $total > 0 ? $remaining / $total : 0.0;

            $daysRemaining = $loan->end_date ? $today->diffInDays($loan->end_date, false) : null;
            $hasCriticalOverdue = $this->overdueInstallmentsCount($loan->id, 15) > 0;

            $eligibleByPaid = $paidRatio >= self::RENEWAL_MIN_PAID_RATIO;
            $eligibleByRemaining = $remainingRatio <= self::RENEWAL_MAX_REMAINING_RATIO;
            $eligibleByEnd = $daysRemaining !== null && $daysRemaining >= 0 && $daysRemaining <= 7;

            if (! ($eligibleByPaid || $eligibleByRemaining || $eligibleByEnd)) {
                continue;
            }

            $recommendation = match (true) {
                $hasCriticalOverdue => 'No renovar',
                $paidRatio >= 0.85 && ! $hasCriticalOverdue => 'Renovar',
                default => 'Revisar',
            };

            $rows[] = [
                'client' => $loan->client?->full_name,
                'phone' => $loan->client?->phone,
                'loan_number' => $loan->loan_number,
                'original_amount' => (float) $loan->principal_amount,
                'remaining' => $remaining,
                'paid_ratio' => round($paidRatio * 100, 1),
                'days_remaining' => $daysRemaining,
                'collector' => $loan->collector?->name ?? 'Sin cobrador',
                'recommendation' => $recommendation,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['paid_ratio'] <=> $a['paid_ratio']);

        return [
            'rows' => $rows,
            'totals' => ['count' => count($rows)],
            'period' => $this->period($filters),
        ];
    }

    // ---------------------------------------------------------------------
    // 6) Clientes inactivos con atrasos
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getInactiveOverdueClients(ReportScope $scope, ReportFilters $filters): array
    {
        // Préstamos con deuda viva pero "inactivos": el préstamo salió de la
        // operación normal (legal/written_off/refinanced) o el cliente está
        // marcado inactivo/bloqueado, y aún tiene saldo pendiente.
        $loans = $this->baseLoans($scope)
            ->with(['client:id,full_name,phone,status', 'collector:id,name'])
            ->where('loans.remaining_balance', '>', 0)
            ->where(function (Builder $q): void {
                $q->whereIn('loans.status', ['legal', 'written_off', 'refinanced'])
                    ->orWhereHas('client', fn (Builder $c) => $c->whereIn('status', ['inactive', 'blocked']));
            })
            ->get();

        $rows = [];
        $totals = ['principal' => 0.0, 'interest' => 0.0, 'late_fee' => 0.0, 'total' => 0.0, 'count' => 0];

        foreach ($loans as $loan) {
            $lastPayment = $this->lastPaymentDate($loan->id);
            $daysSince = $lastPayment ? (int) $lastPayment->diffInDays(now()) : null;

            $principal = (float) $loan->remaining_balance;
            $interest = max((float) $loan->total_interest - (float) $loan->paid_interest, 0);
            $lateFee = $this->pendingLateFee($loan->id);
            $total = $principal + $interest + $lateFee;

            $rows[] = [
                'client' => $loan->client?->full_name,
                'phone' => $loan->client?->phone,
                'last_payment' => $lastPayment?->format('d/m/Y') ?? 'Sin pagos',
                'principal' => $principal,
                'interest' => $interest,
                'late_fee' => $lateFee,
                'total' => $total,
                'days_since_payment' => $daysSince,
                'collector' => $loan->collector?->name ?? 'Sin cobrador',
            ];

            $totals['principal'] += $principal;
            $totals['interest'] += $interest;
            $totals['late_fee'] += $lateFee;
            $totals['total'] += $total;
            $totals['count']++;
        }

        usort($rows, static fn (array $a, array $b): int => ($b['days_since_payment'] ?? 0) <=> ($a['days_since_payment'] ?? 0));

        return [
            'rows' => $rows,
            'totals' => $totals,
            'period' => $this->period($filters),
        ];
    }

    // ---------------------------------------------------------------------
    // 7) Clientes activos con atrasos
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getActiveOverdueClients(ReportScope $scope, ReportFilters $filters): array
    {
        $loans = $this->baseLoans($scope)
            ->with(['client:id,full_name,phone', 'collector:id,name', 'installments'])
            ->whereIn('loans.status', self::ACTIVE_LOAN_STATUSES)
            ->whereHas('installments', fn (Builder $q) => $this->scopeOverdueInstallments($q))
            ->get();

        $today = now()->startOfDay();
        $rows = [];
        $buckets = ['1-7' => 0, '8-15' => 0, '16-30' => 0, '30+' => 0];
        $totals = ['principal' => 0.0, 'interest' => 0.0, 'late_fee' => 0.0, 'total' => 0.0, 'count' => 0];

        foreach ($loans as $loan) {
            $overdue = $loan->installments->filter(fn (LoanInstallment $i): bool => $this->isOverdue($i));
            if ($overdue->isEmpty()) {
                continue;
            }

            $oldestDue = $overdue->min('due_date');
            $daysLate = $oldestDue ? (int) Carbon::parse($oldestDue)->diffInDays($today) : 0;
            $bucket = $this->overdueBucket($daysLate);
            $buckets[$bucket]++;

            $principal = (float) $loan->remaining_balance;
            $interest = max((float) $loan->total_interest - (float) $loan->paid_interest, 0);
            $lateFee = (float) $overdue->sum(fn (LoanInstallment $i): float => max((float) $i->late_fee - (float) $i->paid_late_fee, 0));
            $total = $principal + $interest + $lateFee;

            $rows[] = [
                'client' => $loan->client?->full_name,
                'phone' => $loan->client?->phone,
                'loan_number' => $loan->loan_number,
                'overdue_installments' => $overdue->count(),
                'days_late' => $daysLate,
                'bucket' => $bucket,
                'principal' => $principal,
                'interest' => $interest,
                'late_fee' => $lateFee,
                'total' => $total,
                'collector' => $loan->collector?->name ?? 'Sin cobrador',
                'route' => $this->primaryRouteName($loan->client_id),
            ];

            $totals['principal'] += $principal;
            $totals['interest'] += $interest;
            $totals['late_fee'] += $lateFee;
            $totals['total'] += $total;
            $totals['count']++;
        }

        usort($rows, static fn (array $a, array $b): int => $b['days_late'] <=> $a['days_late']);

        return [
            'rows' => $rows,
            'totals' => $totals,
            'meta' => ['buckets' => $buckets],
            'period' => $this->period($filters),
        ];
    }

    // ---------------------------------------------------------------------
    // 8) Reporte de gastos
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getExpensesReport(ReportScope $scope, ReportFilters $filters): array
    {
        $from = $filters->dateFrom->copy()->startOfDay();
        $to = $filters->dateTo->copy()->endOfDay();

        $expenses = $this->scope($scope, Expense::query(), 'expenses')
            ->with(['category:id,name', 'createdBy:id,name'])
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('expense_date')
            ->get();

        $rows = $expenses->map(fn (Expense $e): array => [
            'date' => $e->expense_date?->format('d/m/Y'),
            'category' => $e->category?->name ?? 'Sin categoría',
            'description' => $e->description,
            'amount' => (float) $e->amount,
            'user' => $e->createdBy?->name ?? 'Sistema',
        ])->all();

        $byCategory = $expenses->groupBy(fn (Expense $e): string => $e->category?->name ?? 'Sin categoría')
            ->map(fn (Collection $group): float => (float) $group->sum('amount'))
            ->sortDesc();

        $byUser = $expenses->groupBy(fn (Expense $e): string => $e->createdBy?->name ?? 'Sistema')
            ->map(fn (Collection $group): float => (float) $group->sum('amount'))
            ->sortDesc();

        return [
            'rows' => $rows,
            'totals' => ['amount' => (float) $expenses->sum('amount'), 'count' => $expenses->count()],
            'meta' => ['by_category' => $byCategory, 'by_user' => $byUser],
            'period' => $this->period($filters),
        ];
    }

    // ---------------------------------------------------------------------
    // 9) Reporte de ganancias
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getProfitReport(ReportScope $scope, ReportFilters $filters): array
    {
        $from = $filters->dateFrom->copy()->startOfDay();
        $to = $filters->dateTo->copy()->endOfDay();

        $payments = $this->basePayments($scope)
            ->whereBetween('payments.payment_date', [$from->toDateString(), $to->toDateString()]);

        $interest = (float) (clone $payments)->sum('interest_paid');
        $lateFee = (float) (clone $payments)->sum('late_fee_paid');

        $expenses = (float) $this->scope($scope, Expense::query(), 'expenses')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $commissions = (float) $this->commissionsQuery($scope)
            ->whereHas('payment', fn (Builder $q) => $q->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])->where('status', 'valid'))
            ->sum('commission_amount');

        $grossProfit = $interest + $lateFee;
        $netProfit = $grossProfit - $expenses - $commissions;

        return [
            'totals' => [
                'interest' => $interest,
                'late_fee' => $lateFee,
                'expenses' => $expenses,
                'commissions' => $commissions,
                'gross_profit' => round($grossProfit, 2),
                'net_profit' => round($netProfit, 2),
            ],
            'period' => $this->period($filters),
        ];
    }

    // ---------------------------------------------------------------------
    // 10) Resumen financiero / inversión
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getFinancialInvestmentSummary(ReportScope $scope, ReportFilters $filters): array
    {
        $from = $filters->dateFrom->copy()->startOfDay();
        $to = $filters->dateTo->copy()->endOfDay();

        $allLoans = $this->baseLoans($scope)->whereIn('loans.status', self::DISBURSED_LOAN_STATUSES);
        $activeLoans = $this->baseLoans($scope)->whereIn('loans.status', self::ACTIVE_LOAN_STATUSES);

        $capitalInvested = (float) (clone $allLoans)->sum('principal_amount');
        $capitalRecovered = (float) (clone $allLoans)->sum('paid_principal');
        $capitalOnStreet = (float) (clone $activeLoans)->sum('remaining_balance');

        $payments = $this->basePayments($scope)
            ->whereBetween('payments.payment_date', [$from->toDateString(), $to->toDateString()]);
        $interestEarned = (float) (clone $payments)->sum('interest_paid');
        $lateFeeEarned = (float) (clone $payments)->sum('late_fee_paid');

        $expenses = (float) $this->scope($scope, Expense::query(), 'expenses')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $newDisbursed = (float) $this->baseLoans($scope)
            ->whereIn('loans.status', self::DISBURSED_LOAN_STATUSES)
            ->whereBetween('loans.start_date', [$from->toDateString(), $to->toDateString()])
            ->sum('principal_amount');

        $grossProfit = $interestEarned + $lateFeeEarned;
        $netBalance = $grossProfit - $expenses;
        $roi = $capitalInvested > 0 ? round(($grossProfit / $capitalInvested) * 100, 2) : 0.0;
        $months = max($filters->dateFrom->diffInMonths($filters->dateTo) ?: 1, 1);
        $monthlyReturn = round($netBalance / $months, 2);

        return [
            'totals' => [
                'capital_invested' => $capitalInvested,
                'capital_on_street' => $capitalOnStreet,
                'capital_recovered' => $capitalRecovered,
                'interest_earned' => $interestEarned,
                'late_fee_earned' => $lateFeeEarned,
                'expenses' => $expenses,
                'new_disbursed' => $newDisbursed,
                'net_balance' => round($netBalance, 2),
                'roi' => $roi,
                'monthly_return' => $monthlyReturn,
            ],
            'clients' => $this->clientCounts($scope),
            'period' => $this->period($filters),
        ];
    }

    // =====================================================================
    // Helpers de consulta base (scoping)
    // =====================================================================

    /**
     * @return Builder<Payment>
     */
    private function basePayments(ReportScope $scope): Builder
    {
        return $scope->applyDimensions(Payment::query()->where('payments.status', 'valid'));
    }

    /**
     * @return Builder<Loan>
     */
    private function baseLoans(ReportScope $scope): Builder
    {
        return $scope->applyDimensions(Loan::query());
    }

    /**
     * Aplica el scope correcto según el modelo (expenses solo por empresa).
     *
     * @param Builder<\Illuminate\Database\Eloquent\Model> $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    private function scope(ReportScope $scope, Builder $query, string $model): Builder
    {
        return $model === 'expenses' ? $scope->applyToExpenses($query) : $scope->applyDimensions($query);
    }

    /**
     * @return Builder<CollectorCommission>
     */
    private function commissionsQuery(ReportScope $scope): Builder
    {
        $query = CollectorCommission::query()
            ->where('collector_commissions.company_id', $scope->companyId())
            ->where('collector_commissions.status', '!=', 'cancelled');

        if ($collectorId = $scope->effectiveCollectorId()) {
            $query->where('collector_commissions.collector_id', $collectorId);
        }

        return $query;
    }

    // =====================================================================
    // Helpers de agregación
    // =====================================================================

    /**
     * @return Collection<string, object>
     */
    private function paymentTotalsByDay(ReportScope $scope, Carbon $from, Carbon $to): Collection
    {
        return $this->basePayments($scope)
            ->selectRaw('payments.payment_date as day, SUM(payments.principal_paid) as capital, SUM(payments.interest_paid) as interest, SUM(payments.late_fee_paid) as late_fee, COUNT(payments.id) as cnt')
            ->whereBetween('payments.payment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('payments.payment_date')
            ->get()
            ->keyBy(fn (object $row): string => Carbon::parse($row->day)->toDateString());
    }

    /**
     * @return Collection<string, object>
     */
    private function disbursedTotalsByDay(ReportScope $scope, Carbon $from, Carbon $to): Collection
    {
        return $this->baseLoans($scope)
            ->selectRaw('loans.start_date as day, SUM(loans.principal_amount) as amount, COUNT(loans.id) as cnt')
            ->whereIn('loans.status', self::DISBURSED_LOAN_STATUSES)
            ->whereBetween('loans.start_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('loans.start_date')
            ->get()
            ->keyBy(fn (object $row): string => Carbon::parse($row->day)->toDateString());
    }

    /**
     * @return Collection<string, float>
     */
    private function expenseTotalsByDay(ReportScope $scope, Carbon $from, Carbon $to): Collection
    {
        return $this->scope($scope, Expense::query(), 'expenses')
            ->selectRaw('expenses.expense_date as day, SUM(expenses.amount) as amount')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('expenses.expense_date')
            ->get()
            ->mapWithKeys(fn (object $row): array => [Carbon::parse($row->day)->toDateString() => (float) $row->amount]);
    }

    /**
     * @return array<string, int>
     */
    private function clientCounts(ReportScope $scope): array
    {
        $active = $scope->applyToClients(Client::query())->where('clients.status', 'active')->count();
        $inactive = $scope->applyToClients(Client::query())->whereIn('clients.status', ['inactive', 'blocked'])->count();
        $overdue = $scope->applyToClients(Client::query())
            ->whereHas('loans', fn (Builder $q) => $q->whereIn('status', self::ACTIVE_LOAN_STATUSES)
                ->whereHas('installments', fn (Builder $i) => $this->scopeOverdueInstallments($i)))
            ->count();

        return ['active' => $active, 'inactive' => $inactive, 'overdue' => $overdue];
    }

    // =====================================================================
    // Helpers de cuotas / atraso
    // =====================================================================

    /**
     * @param Builder<LoanInstallment> $query
     * @return Builder<LoanInstallment>
     */
    private function scopeOverdueInstallments(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'partial', 'late'])
            ->whereDate('due_date', '<', now()->toDateString());
    }

    private function isOverdue(LoanInstallment $installment): bool
    {
        return in_array($installment->status, ['pending', 'partial', 'late'], true)
            && $installment->due_date !== null
            && $installment->due_date->lt(now()->startOfDay());
    }

    private function overdueBucket(int $daysLate): string
    {
        return match (true) {
            $daysLate <= 7 => '1-7',
            $daysLate <= 15 => '8-15',
            $daysLate <= 30 => '16-30',
            default => '30+',
        };
    }

    private function overdueInstallmentsCount(int $loanId, int $minDaysLate = 0): int
    {
        return (int) LoanInstallment::query()
            ->where('loan_id', $loanId)
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->whereDate('due_date', '<', now()->subDays($minDaysLate)->toDateString())
            ->count();
    }

    /** Mora pendiente del préstamo. Se calcula en PHP para ser portable (GREATEST no existe en SQLite). */
    private function pendingLateFee(int $loanId): float
    {
        return (float) LoanInstallment::query()
            ->where('loan_id', $loanId)
            ->get(['late_fee', 'paid_late_fee'])
            ->sum(fn (LoanInstallment $i): float => max((float) $i->late_fee - (float) $i->paid_late_fee, 0));
    }

    private function lastPaymentDate(int $loanId): ?Carbon
    {
        $date = Payment::query()
            ->where('loan_id', $loanId)
            ->where('status', 'valid')
            ->max('payment_date');

        return $date ? Carbon::parse($date) : null;
    }

    // =====================================================================
    // Helpers varios
    // =====================================================================

    private function primaryRouteName(int $clientId): ?string
    {
        static $cache = [];

        if (! array_key_exists($clientId, $cache)) {
            $cache[$clientId] = Client::query()
                ->whereKey($clientId)
                ->with('routes:id,name')
                ->first()?->routes->first()?->name;
        }

        return $cache[$clientId];
    }

    /**
     * @return array<int, int>
     */
    private function availableYears(ReportScope $scope): array
    {
        $min = $this->baseLoans($scope)->min('start_date');
        $start = $min ? (int) Carbon::parse($min)->year : (int) now()->year;
        $end = (int) now()->year;

        return range($end, min($start, $end));
    }

    private function loanStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Activo',
            'late' => 'Atrasado',
            'paid' => 'Pagado',
            'refinanced' => 'Refinanciado',
            'cancelled' => 'Cancelado',
            'legal' => 'Legal',
            'written_off' => 'Castigado',
            'pending' => 'Pendiente',
            default => ucfirst($status),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function period(ReportFilters $filters): array
    {
        return [
            'label' => $filters->periodLabel(),
            'date_from' => $filters->dateFrom->toDateString(),
            'date_to' => $filters->dateTo->toDateString(),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function emptyTotals(): array
    {
        return [
            'capital' => 0.0, 'interest' => 0.0, 'late_fee' => 0.0,
            'disbursed' => 0.0, 'disbursed_count' => 0, 'expenses' => 0.0,
            'collected' => 0.0, 'net_balance' => 0.0,
        ];
    }
}
