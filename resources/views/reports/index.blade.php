@extends('layouts.app')

@section('title', 'Informes - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Informes</h1>
        <p class="text-muted mb-0">Reportes financieros, operativos y de cartera. Filtra, imprime y exporta a PDF o Excel.</p>
    </section>

    <section class="row g-3 g-md-4">
        @foreach ($reports as $type => $report)
            <div class="col-12 col-sm-6 col-xl-4">
                <a href="{{ route($report['route']) }}" class="text-decoration-none">
                    <div class="card content-card h-100 report-card">
                        <div class="card-body d-flex align-items-start gap-3">
                            <span class="report-card-icon">
                                <i class="fa-solid {{ $report['icon'] }}"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold text-dark">{{ $report['title'] }}</div>
                                <div class="text-muted small">{{ $report['description'] }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach

        {{-- Dashboard financiero clásico (se conserva) --}}
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('reports.financial') }}" class="text-decoration-none">
                <div class="card content-card h-100 report-card">
                    <div class="card-body d-flex align-items-start gap-3">
                        <span class="report-card-icon"><i class="fa-solid fa-gauge-high"></i></span>
                        <div class="min-w-0">
                            <div class="fw-semibold text-dark">Financiero (clásico)</div>
                            <div class="text-muted small">Vista resumida de atrasos, cartera y rendimiento por cobrador.</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .report-card {
            transition: transform .12s ease, box-shadow .12s ease;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(17, 24, 39, .08);
        }
        .report-card-icon {
            flex: 0 0 auto;
            width: 46px;
            height: 46px;
            display: inline-grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(94, 114, 228, .12);
            color: var(--app-primary);
            font-size: 1.15rem;
        }
        .min-w-0 { min-width: 0; }
    </style>
@endpush
