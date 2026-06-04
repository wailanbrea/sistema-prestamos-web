<?php

declare(strict_types=1);

namespace App\Services\Reports\Export;

/**
 * Normaliza la salida heterogénea del ReportService a una estructura tabular
 * genérica { summary, columns, rows, totals } que consumen por igual el
 * exportador PDF y el exportador Excel. Así existe una sola definición de
 * columnas por reporte y no hay que mantener 10 plantillas de exportación.
 */
class ReportPresenter
{
    /**
     * @param array<string, mixed> $data
     * @return array{
     *     summary: array<int, array{label:string, value:mixed, money:bool}>,
     *     columns: array<int, array{label:string, key:string, money:bool}>,
     *     rows: array<int, array<string, mixed>>,
     *     totals: array<string, mixed>|null
     * }
     */
    public function present(string $type, array $data): array
    {
        return match ($type) {
            'resumen-semanal' => $this->weekly($data),
            'semanal-consolidado' => $this->consolidated($data),
            'resumen-anual' => $this->annual($data),
            'prestamos-entregados' => $this->disbursed($data),
            'elegibles-renovar' => $this->renewal($data),
            'activos-atraso' => $this->activeOverdue($data),
            'inactivos-atraso' => $this->inactiveOverdue($data),
            'gastos' => $this->expenses($data),
            'ganancias' => $this->profit($data),
            'resumen-financiero' => $this->financial($data),
            default => $this->empty(),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function weekly(array $data): array
    {
        return [
            'summary' => [
                $this->item('Balance neto', $data['totals']['net_balance'] ?? 0),
            ],
            'columns' => [
                $this->col('Día', 'label', false),
                $this->col('Capital', 'capital'),
                $this->col('Rédito', 'interest'),
                $this->col('Mora', 'late_fee'),
                $this->col('Entregas', 'disbursed'),
                $this->col('Cant.', 'disbursed_count', false),
                $this->col('Gastos', 'expenses'),
                $this->col('Total colectado', 'collected'),
            ],
            'rows' => $data['rows'] ?? [],
            'totals' => $data['totals'] ?? null,
        ];
    }

    private function consolidated(array $data): array
    {
        return [
            'summary' => [
                $this->item('Gastos (empresa)', $data['totals']['expenses'] ?? 0),
                $this->item('Balance neto', $data['totals']['net_balance'] ?? 0),
            ],
            'columns' => [
                $this->col('Cobrador', 'collector', false),
                $this->col('Capital', 'capital'),
                $this->col('Rédito', 'interest'),
                $this->col('Mora', 'late_fee'),
                $this->col('Entregas', 'disbursed'),
                $this->col('Total colectado', 'collected'),
                $this->col('Activas', 'active_accounts', false),
                $this->col('Atrasadas', 'overdue_accounts', false),
            ],
            'rows' => $data['rows'] ?? [],
            'totals' => $data['totals'] ?? null,
        ];
    }

    private function annual(array $data): array
    {
        return [
            'summary' => [
                $this->item('Balance neto anual', $data['totals']['net_balance'] ?? 0),
            ],
            'columns' => [
                $this->col('Mes', 'label', false),
                $this->col('Capital', 'capital'),
                $this->col('Rédito', 'interest'),
                $this->col('Mora', 'late_fee'),
                $this->col('Entregas', 'disbursed'),
                $this->col('Cant.', 'disbursed_count', false),
                $this->col('Gastos', 'expenses'),
                $this->col('Total', 'collected'),
                $this->col('Balance', 'net_balance'),
            ],
            'rows' => $data['rows'] ?? [],
            'totals' => $data['totals'] ?? null,
        ];
    }

    private function disbursed(array $data): array
    {
        return [
            'summary' => [
                $this->item('Préstamos', $data['totals']['count'] ?? 0, false),
                $this->item('Monto total', $data['totals']['amount'] ?? 0),
            ],
            'columns' => [
                $this->col('Cliente', 'client', false),
                $this->col('Código', 'code', false),
                $this->col('Teléfono', 'phone', false),
                $this->col('Fecha', 'date', false),
                $this->col('Monto', 'amount'),
                $this->col('Cobrador', 'collector', false),
                $this->col('Ruta', 'route', false),
                $this->col('Estado', 'status', false),
            ],
            'rows' => $data['rows'] ?? [],
            'totals' => null,
        ];
    }

    private function renewal(array $data): array
    {
        return [
            'summary' => [$this->item('Clientes elegibles', $data['totals']['count'] ?? 0, false)],
            'columns' => [
                $this->col('Cliente', 'client', false),
                $this->col('Teléfono', 'phone', false),
                $this->col('Préstamo', 'loan_number', false),
                $this->col('Monto original', 'original_amount'),
                $this->col('Saldo', 'remaining'),
                $this->col('% pagado', 'paid_ratio', false),
                $this->col('Días rest.', 'days_remaining', false),
                $this->col('Cobrador', 'collector', false),
                $this->col('Recomendación', 'recommendation', false),
            ],
            'rows' => $data['rows'] ?? [],
            'totals' => null,
        ];
    }

    private function activeOverdue(array $data): array
    {
        $buckets = $data['meta']['buckets'] ?? [];

        return [
            'summary' => [
                $this->item('1-7 días', $buckets['1-7'] ?? 0, false),
                $this->item('8-15 días', $buckets['8-15'] ?? 0, false),
                $this->item('16-30 días', $buckets['16-30'] ?? 0, false),
                $this->item('+30 días', $buckets['30+'] ?? 0, false),
                $this->item('Total pendiente', $data['totals']['total'] ?? 0),
            ],
            'columns' => [
                $this->col('Cliente', 'client', false),
                $this->col('Teléfono', 'phone', false),
                $this->col('Préstamo', 'loan_number', false),
                $this->col('Cuotas atr.', 'overdue_installments', false),
                $this->col('Días', 'days_late', false),
                $this->col('Nivel', 'bucket', false),
                $this->col('Capital', 'principal'),
                $this->col('Interés', 'interest'),
                $this->col('Mora', 'late_fee'),
                $this->col('Total', 'total'),
                $this->col('Cobrador', 'collector', false),
            ],
            'rows' => $data['rows'] ?? [],
            'totals' => $data['totals'] ?? null,
        ];
    }

    private function inactiveOverdue(array $data): array
    {
        return [
            'summary' => [
                $this->item('Clientes', $data['totals']['count'] ?? 0, false),
                $this->item('Total pendiente', $data['totals']['total'] ?? 0),
            ],
            'columns' => [
                $this->col('Cliente', 'client', false),
                $this->col('Teléfono', 'phone', false),
                $this->col('Último pago', 'last_payment', false),
                $this->col('Capital', 'principal'),
                $this->col('Interés', 'interest'),
                $this->col('Mora', 'late_fee'),
                $this->col('Total', 'total'),
                $this->col('Días sin pagar', 'days_since_payment', false),
                $this->col('Cobrador', 'collector', false),
            ],
            'rows' => $data['rows'] ?? [],
            'totals' => $data['totals'] ?? null,
        ];
    }

    private function expenses(array $data): array
    {
        return [
            'summary' => [$this->item('Total gastos', $data['totals']['amount'] ?? 0)],
            'columns' => [
                $this->col('Fecha', 'date', false),
                $this->col('Categoría', 'category', false),
                $this->col('Descripción', 'description', false),
                $this->col('Usuario', 'user', false),
                $this->col('Monto', 'amount'),
            ],
            'rows' => $data['rows'] ?? [],
            'totals' => ['amount' => $data['totals']['amount'] ?? 0],
        ];
    }

    private function profit(array $data): array
    {
        $t = $data['totals'] ?? [];

        return [
            'summary' => [
                $this->item('Interés cobrado', $t['interest'] ?? 0),
                $this->item('Mora cobrada', $t['late_fee'] ?? 0),
                $this->item('Gastos', $t['expenses'] ?? 0),
                $this->item('Comisiones', $t['commissions'] ?? 0),
                $this->item('Ganancia bruta', $t['gross_profit'] ?? 0),
                $this->item('Ganancia neta', $t['net_profit'] ?? 0),
            ],
            'columns' => [],
            'rows' => [],
            'totals' => null,
        ];
    }

    private function financial(array $data): array
    {
        $t = $data['totals'] ?? [];
        $c = $data['clients'] ?? [];

        return [
            'summary' => [
                $this->item('Capital invertido', $t['capital_invested'] ?? 0),
                $this->item('Capital en calle', $t['capital_on_street'] ?? 0),
                $this->item('Capital recuperado', $t['capital_recovered'] ?? 0),
                $this->item('Interés ganado', $t['interest_earned'] ?? 0),
                $this->item('Mora ganada', $t['late_fee_earned'] ?? 0),
                $this->item('Gastos', $t['expenses'] ?? 0),
                $this->item('Entregas nuevas', $t['new_disbursed'] ?? 0),
                $this->item('Balance neto', $t['net_balance'] ?? 0),
                $this->item('ROI %', $t['roi'] ?? 0, false),
                $this->item('Rentabilidad mensual', $t['monthly_return'] ?? 0),
                $this->item('Clientes activos', $c['active'] ?? 0, false),
                $this->item('Clientes inactivos', $c['inactive'] ?? 0, false),
                $this->item('Clientes atrasados', $c['overdue'] ?? 0, false),
            ],
            'columns' => [],
            'rows' => [],
            'totals' => null,
        ];
    }

    private function empty(): array
    {
        return ['summary' => [], 'columns' => [], 'rows' => [], 'totals' => null];
    }

    /**
     * @return array{label:string, value:mixed, money:bool}
     */
    private function item(string $label, mixed $value, bool $money = true): array
    {
        return ['label' => $label, 'value' => $value, 'money' => $money];
    }

    /**
     * @return array{label:string, key:string, money:bool}
     */
    private function col(string $label, string $key, bool $money = true): array
    {
        return ['label' => $label, 'key' => $key, 'money' => $money];
    }
}
