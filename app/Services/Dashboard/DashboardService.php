<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Expense;
use App\Models\Loan;
use App\Models\Payment;

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
}
