<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Expense;
use App\Models\Loan;
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

        return [
            'capital_invertido' => $capitalPrestado,
            'capital_prestado' => $capitalPrestado,
            'capital_disponible' => 0.0,
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
            'prestamos_activos' => Loan::query()->forCompany($companyId)->where('status', 'active')->count(),
            'prestamos_saldados' => Loan::query()->forCompany($companyId)->where('status', 'paid')->count(),
            'prestamos_mora' => Loan::query()->forCompany($companyId)->where('status', 'late')->count(),
            'cobradores_activos' => Collector::query()->forCompany($companyId)->where('status', 'active')->count(),
        ];
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
            $labels[] = $date->isoFormat('DD MMM');
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
        /** @var Collection<string, int> $counts */
        $counts = Loan::query()
            ->forCompany($companyId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'active' => (int) ($counts['active'] ?? 0),
            'late' => (int) ($counts['late'] ?? 0),
            'paid' => (int) ($counts['paid'] ?? 0),
        ];
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
            ->with(['client:id,full_name', 'collector:id,name'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
