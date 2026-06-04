{{--
    Tabla genérica y responsive para reportes.
    Espera: $columns (array de {label,key,money}), $rows (array assoc), $totals (assoc|null).
    En móvil (<576px) cada fila se apila como tarjeta usando data-label.
--}}
@php
    /** Badge de color según el valor de columnas especiales. */
    $badge = function (string $key, $value): ?string {
        if ($value === null || $value === '') {
            return null;
        }
        return match (true) {
            $key === 'recommendation' => match ($value) {
                'Renovar' => 'success', 'Revisar' => 'warning', 'No renovar' => 'danger', default => 'secondary',
            },
            $key === 'bucket' => match ($value) {
                '1-7' => 'info', '8-15' => 'warning', '16-30' => 'warning', '30+' => 'danger', default => 'secondary',
            },
            $key === 'status' => match ($value) {
                'Pagado' => 'success', 'Atrasado', 'Legal', 'Castigado' => 'danger', 'Activo' => 'primary', default => 'secondary',
            },
            default => null,
        };
    };
@endphp

<div class="table-responsive report-table-wrap">
    <table class="table align-middle mb-0 report-table">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th class="{{ $column['money'] ? 'text-end' : '' }}">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $column)
                        @php($value = $row[$column['key']] ?? null)
                        @php($badgeColor = $badge($column['key'], $value))
                        <td data-label="{{ $column['label'] }}" class="{{ $column['money'] ? 'text-end' : '' }}">
                            @if ($column['money'])
                                @include('reports.partials.money', ['amount' => (float) $value])
                            @elseif ($badgeColor)
                                <span class="badge text-bg-{{ $badgeColor }}">{{ $value }}</span>
                            @elseif ($column['key'] === 'paid_ratio')
                                {{ number_format((float) $value, 1) }}%
                            @else
                                {{ ($value === null || $value === '') ? '—' : $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($columns), 1) }}" class="text-center text-muted py-4">
                        No hay datos para los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if (! empty($totals) && count($rows) > 0)
            <tfoot>
                <tr class="fw-semibold report-total-row">
                    @foreach ($columns as $i => $column)
                        <td data-label="{{ $column['label'] }}" class="{{ $column['money'] ? 'text-end' : '' }}">
                            @if ($i === 0)
                                TOTAL
                            @elseif (array_key_exists($column['key'], $totals))
                                @if ($column['money'])
                                    @include('reports.partials.money', ['amount' => (float) $totals[$column['key']]])
                                @else
                                    {{ $totals[$column['key']] }}
                                @endif
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>
