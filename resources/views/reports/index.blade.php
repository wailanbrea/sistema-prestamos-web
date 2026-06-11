@extends('layouts.app')

@section('title', 'Reportes — '.config('app.name'))

@push('styles')
<style>
    .report-card {
        background: var(--app-surface);
        border: 1px solid var(--app-border-light) !important;
        border-radius: 16px !important;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        text-decoration: none;
        color: inherit;
        transition: box-shadow .15s ease, transform .15s ease;
        height: 100%;
    }
    .report-card:hover {
        box-shadow: 0 6px 20px rgba(0,38,83,.1) !important;
        transform: translateY(-2px);
        color: inherit;
    }
    .report-card:active { transform: scale(.98); }
    .report-icon {
        width: 52px; height: 52px; border-radius: 14px;
        display: inline-grid; place-items: center;
        font-size: 1.15rem; flex-shrink: 0;
        transition: transform .15s ease;
    }
    .report-card:hover .report-icon { transform: scale(1.06); }
    .report-footer {
        margin-top: auto;
        padding-top: 14px;
        border-top: 1px solid var(--app-border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .report-meta { font-size:.75rem; color:var(--app-muted); font-weight:500; }
    .report-link { font-size:.75rem; color:var(--app-warning); font-weight:600; display:flex; align-items:center; gap:2px; }
    .export-btn {
        width:32px; height:32px; border-radius:8px; display:inline-grid; place-items:center;
        background:transparent; border:none; color:var(--app-muted); cursor:pointer;
        transition:background .15s ease, color .15s ease; font-size:.85rem;
    }
    .export-btn:hover { background:var(--app-surface-low); color:var(--app-primary); }

    @php
        $iconColors = [
            0 => ['bg' => '#dbeafe', 'clr' => '#1e40af'],
            1 => ['bg' => '#fee2e2', 'clr' => '#991b1b'],
            2 => ['bg' => '#fef9c3', 'clr' => '#854d0e'],
            3 => ['bg' => '#dcfce7', 'clr' => '#166534'],
            4 => ['bg' => '#ede9fe', 'clr' => '#5b21b6'],
            5 => ['bg' => '#fce7f3', 'clr' => '#9d174d'],
            6 => ['bg' => '#e0f2fe', 'clr' => '#075985'],
            7 => ['bg' => '#fff7ed', 'clr' => '#9a3412'],
            8 => ['bg' => '#f0fdf4', 'clr' => '#166534'],
            9 => ['bg' => '#fdf4ff', 'clr' => '#7e22ce'],
        ];
    @endphp
</style>
@endpush

@section('content')

{{-- ── Header ── --}}
<section class="mb-4 anim-fade-up">
    <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
        <div>
            <h1 class="h4 fw-bold mb-1" style="color:var(--app-primary);">Reportes Generales</h1>
            <p class="text-muted mb-0" style="font-size:.88rem;">Reportes financieros, operativos y de cartera. Filtra, imprime y exporta a PDF o Excel.</p>
        </div>
    </div>
</section>

{{-- ── Report cards grid ── --}}
<section class="row g-3 g-md-4">
    @foreach ($reports as $type => $report)
        @php $colors = $iconColors[array_search($type, array_keys($reports)) % count($iconColors)]; @endphp
        <div class="col-12 col-sm-6 col-xl-4 anim-fade-up" style="animation-delay:{{ ($loop->index * 40) }}ms;">
            <a href="{{ route($report['route']) }}" class="report-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="report-icon" style="background:{{ $colors['bg'] }}; color:{{ $colors['clr'] }};">
                        <i class="fa-solid {{ $report['icon'] }}"></i>
                    </span>
                    <div class="d-flex gap-1">
                        @if(!empty($report['pdf_route']))
                            <span class="export-btn" title="Exportar PDF"><i class="fa-solid fa-file-pdf"></i></span>
                        @endif
                        @if(!empty($report['excel_route']))
                            <span class="export-btn" title="Exportar Excel"><i class="fa-solid fa-table"></i></span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="fw-semibold mb-1" style="font-size:.95rem; color:var(--app-text);">{{ $report['title'] }}</div>
                    <div class="text-muted" style="font-size:.82rem; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $report['description'] }}</div>
                </div>
                <div class="report-footer">
                    <span class="report-meta">Ver detalle</span>
                    <span class="report-link">Ver <i class="fa-solid fa-chevron-right" style="font-size:.65rem;"></i></span>
                </div>
            </a>
        </div>
    @endforeach

    {{-- Dashboard financiero clásico --}}
    @php $colorsFinanciero = $iconColors[count($reports) % count($iconColors)]; @endphp
    <div class="col-12 col-sm-6 col-xl-4 anim-fade-up" style="animation-delay:{{ (count($reports) * 40) }}ms;">
        <a href="{{ route('reports.financial') }}" class="report-card">
            <div class="d-flex justify-content-between align-items-start">
                <span class="report-icon" style="background:{{ $colorsFinanciero['bg'] }}; color:{{ $colorsFinanciero['clr'] }};">
                    <i class="fa-solid fa-gauge-high"></i>
                </span>
            </div>
            <div>
                <div class="fw-semibold mb-1" style="font-size:.95rem; color:var(--app-text);">Financiero (clásico)</div>
                <div class="text-muted" style="font-size:.82rem; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">Vista resumida de atrasos, cartera y rendimiento por cobrador.</div>
            </div>
            <div class="report-footer">
                <span class="report-meta">Ver detalle</span>
                <span class="report-link">Ver <i class="fa-solid fa-chevron-right" style="font-size:.65rem;"></i></span>
            </div>
        </a>
    </div>
</section>

@endsection
