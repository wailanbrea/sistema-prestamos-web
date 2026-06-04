@extends('layouts.app')

@section('title', $title.' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <a href="{{ route('reports.index') }}" class="text-decoration-none small text-muted no-print">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver a Informes
        </a>
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mt-2">
            <div>
                <h1 class="h3 fw-bold mb-1">
                    <i class="fa-solid {{ $config['icon'] }} text-primary me-2"></i>{{ $title }}
                </h1>
                <p class="text-muted mb-0">{{ $config['description'] }}</p>
            </div>
            @include('reports.partials.export-actions', ['type' => $type, 'filters' => $filters])
        </div>
    </section>

    {{-- Encabezado de empresa/período visible al imprimir y en pantalla --}}
    <div class="report-print-header d-none">
        <strong>{{ auth()->user()->company?->name }}</strong> · {{ $title }} · {{ $data['period']['label'] ?? '' }}
    </div>

    @include('reports.partials.filters', ['filters' => $filters, 'options' => $options, 'type' => $type, 'data' => $data])

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h6 text-uppercase text-muted mb-0">Período: {{ $data['period']['label'] ?? '—' }}</h2>
    </div>

    @include('reports.partials.summary-cards', ['items' => $table['summary']])

    @if (! empty($table['columns']))
        <section class="card content-card mb-4">
            <div class="card-body">
                @include('reports.partials.data-table', [
                    'columns' => $table['columns'],
                    'rows' => $table['rows'],
                    'totals' => $table['totals'],
                ])
            </div>
        </section>
    @endif

    {{-- Extra para Gastos: desglose por categoría y por usuario --}}
    @if ($type === 'gastos' && ! empty($data['meta']))
        <section class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card content-card h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted mb-3">Gastos por categoría</h2>
                        @foreach ($data['meta']['by_category'] as $name => $amount)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>{{ $name }}</span>
                                <span class="fw-semibold">@include('reports.partials.money', ['amount' => $amount])</span>
                            </div>
                        @endforeach
                        @if ($data['meta']['by_category']->isEmpty())
                            <p class="text-muted mb-0">Sin gastos en el período.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card content-card h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted mb-3">Gastos por usuario</h2>
                        @foreach ($data['meta']['by_user'] as $name => $amount)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>{{ $name }}</span>
                                <span class="fw-semibold">@include('reports.partials.money', ['amount' => $amount])</span>
                            </div>
                        @endforeach
                        @if ($data['meta']['by_user']->isEmpty())
                            <p class="text-muted mb-0">Sin gastos en el período.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection

@push('styles')
    <style>
        /* Tabla apilada en móvil: cada celda muestra su etiqueta. */
        @media (max-width: 575.98px) {
            .report-table thead {
                display: none;
            }
            .report-table, .report-table tbody, .report-table tr, .report-table td {
                display: block;
                width: 100%;
            }
            .report-table tr {
                border: 1px solid var(--app-border);
                border-radius: 10px;
                margin-bottom: 12px;
                padding: 6px 12px;
                background: #fff;
            }
            .report-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                text-align: right !important;
                border: 0;
                border-bottom: 1px solid var(--app-border);
                padding: 8px 0;
            }
            .report-table td:last-child {
                border-bottom: 0;
            }
            .report-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--app-muted);
                text-align: left;
            }
            .report-table tfoot tr {
                background: #f8f9fb;
            }
        }

        /* Impresión: solo el contenido del reporte. */
        @media print {
            .sidebar, .topbar, .sidebar-backdrop, .no-print {
                display: none !important;
            }
            .app-shell {
                display: block !important;
            }
            .main {
                padding: 0 !important;
            }
            .report-print-header {
                display: block !important;
                margin-bottom: 12px;
                font-size: 14px;
            }
            .content-card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
            a[href]::after {
                content: '';
            }
        }
    </style>
@endpush
