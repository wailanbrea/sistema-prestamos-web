@extends('layouts.app')

@section('title', 'Gastos — '.config('app.name'))

@push('styles')
<style>
    .expense-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px; height: 72px;
        border-bottom: 1px solid var(--app-border-light);
        transition: background .12s ease; cursor: pointer; text-decoration: none; color: inherit;
    }
    .expense-row:last-child { border-bottom: none; }
    .expense-row:hover { background: var(--app-surface-low); color: inherit; }
    .expense-icon {
        width: 40px; height: 40px; border-radius: 99px;
        display: inline-grid; place-items: center; font-size: .85rem; flex-shrink: 0;
    }
    .expense-pill {
        font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
        padding:2px 9px; border-radius:99px; display:inline-flex; align-items:center; gap:4px;
    }
    .expense-pill::before { content:''; width:6px; height:6px; border-radius:99px; background:currentColor; display:block; opacity:.7; }
    .pill-paid    { background:#dcfce7; color:#166534; }
    .pill-pending { background:#fef9c3; color:#854d0e; }

    .metric-summary {
        padding: 18px 20px; border-radius: 14px; position: relative; overflow: hidden;
        border: 1px solid var(--app-border-light);
        background: var(--app-surface);
    }
    .metric-summary .glow {
        position:absolute; width:80px; height:80px; border-radius:99px; filter:blur(24px); opacity:.3;
        top:-16px; right:-16px; pointer-events:none;
    }
    .filter-chip-sm {
        padding:5px 14px; border-radius:99px; font-size:.78rem; font-weight:500;
        border:1px solid var(--app-border); background:var(--app-surface); color:var(--app-muted);
        white-space:nowrap; cursor:pointer; transition:all .15s ease;
    }
    .filter-chip-sm.active { background:var(--app-primary); border-color:var(--app-primary); color:#fff; }
</style>
@endpush

@section('content')

{{-- ── Header ── --}}
<section class="mb-4 anim-fade-up">
    <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
        <div>
            <h1 class="h4 fw-bold mb-1" style="color:var(--app-primary);">Gestión de Gastos</h1>
            <p class="text-muted mb-0" style="font-size:.88rem;">Control y registro de egresos operativos con salida automática de caja.</p>
        </div>
        <a href="{{ route('expenses.create') }}" class="btn btn-sm fw-semibold"
           style="background:var(--app-secondary); color:var(--app-on-secondary); border-radius:10px; border:none; padding:8px 16px;">
            <i class="fa-solid fa-plus me-2"></i> Nuevo gasto
        </a>
    </div>
</section>

{{-- ── Summary metrics ── --}}
<section class="row g-3 mb-4 anim-fade-up" style="animation-delay:60ms;">
    <div class="col-12 col-md-4">
        <div class="metric-summary">
            <div class="glow" style="background:var(--app-error);"></div>
            <div class="d-flex justify-content-between align-items-center mb-2" style="position:relative;">
                <span style="font-size:.78rem; font-weight:500; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Total egresos (filtro)</span>
                <i class="fa-solid fa-trending-down" style="color:var(--app-error); font-size:.9rem;"></i>
            </div>
            <div class="fw-bold" style="font-size:1.5rem; color:var(--app-text); font-variant-numeric:tabular-nums; position:relative;">
                {{ currency() }} {{ number_format($expenses->sum('amount'), 2) }}
            </div>
            <div style="font-size:.75rem; color:var(--app-muted); margin-top:4px; position:relative;">{{ $expenses->count() }} registros en esta página</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="metric-summary">
            <div class="glow" style="background:var(--app-primary);"></div>
            <div class="d-flex justify-content-between align-items-center mb-2" style="position:relative;">
                <span style="font-size:.78rem; font-weight:500; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Mayor categoría</span>
                <i class="fa-solid fa-tag" style="color:var(--app-primary); font-size:.9rem;"></i>
            </div>
            @php
                $topCat = $categories->sortByDesc('expenses_count')->first();
            @endphp
            <div class="fw-bold" style="font-size:1.3rem; color:var(--app-text); position:relative;">
                {{ $topCat?->name ?? '—' }}
            </div>
            <div style="font-size:.75rem; color:var(--app-muted); margin-top:4px; position:relative;">
                {{ $topCat ? number_format($topCat->expenses_count).' gastos registrados' : 'Sin categorías' }}
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="metric-summary">
            <div class="glow" style="background:var(--app-warning);"></div>
            <div class="d-flex justify-content-between align-items-center mb-2" style="position:relative;">
                <span style="font-size:.78rem; font-weight:500; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Categorías activas</span>
                <i class="fa-solid fa-list-ul" style="color:var(--app-warning); font-size:.9rem;"></i>
            </div>
            <div class="fw-bold" style="font-size:1.5rem; color:var(--app-text); font-variant-numeric:tabular-nums; position:relative;">
                {{ $categories->count() }}
            </div>
            <div style="font-size:.75rem; color:var(--app-muted); margin-top:4px; position:relative;">categorías registradas</div>
        </div>
    </div>
</section>

<div class="row g-4">
    {{-- ── Main: Filters + Table ── --}}
    <div class="col-12 col-xl-8">
        {{-- Filters --}}
        <div class="card content-card mb-3 anim-fade-up" style="animation-delay:80ms;">
            <div class="card-body">
                <form method="GET" action="{{ route('expenses.index') }}" class="row g-3 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label for="search" class="form-label" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Buscar</label>
                        <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}"
                               class="form-control" style="border-radius:10px;" placeholder="Descripción del gasto">
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="category_id" class="form-label" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Categoría</label>
                        <select id="category_id" name="category_id" class="form-select" style="border-radius:10px;">
                            <option value="">Todas</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string)($filters['category_id'] ?? '') === (string)$category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="date_from" class="form-label" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Desde</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="form-control" style="border-radius:10px;">
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="date_to" class="form-label" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Hasta</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="form-control" style="border-radius:10px;">
                    </div>
                    <div class="col-12 col-lg-1 d-grid">
                        <button type="submit" class="btn fw-semibold"
                                style="background:var(--app-primary); color:#fff; border-radius:10px; border:none;">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Expense list --}}
        <div class="card content-card anim-fade-up" style="animation-delay:100ms;">
            <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                <h3 class="h6 fw-bold mb-0" style="color:var(--app-primary);">Historial de Gastos</h3>
            </div>
            <div class="card-body p-0">
                @forelse ($expenses as $expense)
                    @php
                        $catIcon = match(true) {
                            str_contains(strtolower($expense->category?->name ?? ''), 'combus') => 'fa-gas-pump',
                            str_contains(strtolower($expense->category?->name ?? ''), 'alimen') => 'fa-utensils',
                            str_contains(strtolower($expense->category?->name ?? ''), 'ofici') => 'fa-print',
                            str_contains(strtolower($expense->category?->name ?? ''), 'mante') => 'fa-wrench',
                            str_contains(strtolower($expense->category?->name ?? ''), 'trans') => 'fa-car',
                            default => 'fa-receipt',
                        };
                    @endphp
                    <a href="{{ route('expenses.show', $expense) }}" class="expense-row">
                        <div class="d-flex align-items-center gap-3">
                            <span class="expense-icon" style="background:rgba(0,38,83,.08); color:var(--app-primary);">
                                <i class="fa-solid {{ $catIcon }}"></i>
                            </span>
                            <div>
                                <div class="fw-semibold" style="font-size:.88rem; color:var(--app-text);">{{ Str::limit($expense->description, 38) }}</div>
                                <div class="text-muted" style="font-size:.74rem;">
                                    {{ $expense->expense_date->format('d M Y') }}
                                    @if($expense->category) · {{ $expense->category->name }} @endif
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold" style="font-size:.9rem; color:var(--app-text);">
                                {{ currency() }} {{ number_format((float)$expense->amount, 2) }}
                            </div>
                            <span class="expense-pill pill-paid">Registrado</span>
                        </div>
                    </a>
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-receipt fa-2x mb-3 d-block opacity-40"></i>
                        No hay gastos registrados con los filtros aplicados.
                    </div>
                @endforelse
            </div>
            @if($expenses->hasPages())
                <div class="card-footer bg-white border-top" style="border-radius:0 0 16px 16px; padding:10px 16px;">
                    {{ $expenses->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ── Sidebar: Categories ── --}}
    <div class="col-12 col-xl-4">
        <div class="card content-card mb-3 anim-fade-up" style="animation-delay:120ms;">
            <div class="card-header bg-white border-0 pt-3 px-4">
                <h3 class="h6 fw-bold mb-0" style="color:var(--app-primary);">Nueva categoría</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('expense-categories.store') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label for="category_name" class="form-label" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Nombre</label>
                        <input id="category_name" name="name" type="text" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               style="border-radius:10px;" maxlength="150" placeholder="Ej. Combustible">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn fw-semibold"
                            style="border-radius:10px; border:1px solid var(--app-primary); color:var(--app-primary); background:transparent; padding:10px;">
                        <i class="fa-solid fa-tags me-2"></i> Crear categoría
                    </button>
                </form>
            </div>
        </div>

        <div class="card content-card anim-fade-up" style="animation-delay:140ms;">
            <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                <h3 class="h6 fw-bold mb-0" style="color:var(--app-primary);">Categorías</h3>
                <span class="badge" style="background:var(--app-surface-low); color:var(--app-muted); font-weight:600;">{{ $categories->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse ($categories as $category)
                    <div class="d-flex align-items-center justify-content-between gap-3 px-4 py-3 border-bottom" style="border-color:var(--app-border-light) !important;">
                        <div class="d-flex align-items-center gap-3">
                            <span style="width:8px; height:8px; border-radius:99px; background:var(--app-primary); flex-shrink:0;"></span>
                            <div>
                                <div class="fw-semibold" style="font-size:.88rem; color:var(--app-text);">{{ $category->name }}</div>
                                <div class="text-muted" style="font-size:.74rem;">{{ number_format($category->expenses_count) }} gastos</div>
                            </div>
                        </div>
                        <form action="{{ route('expense-categories.destroy', $category) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar esta categoría?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0"
                                    style="color:var(--app-error); font-size:.8rem;">Eliminar</button>
                        </form>
                    </div>
                @empty
                    <div class="text-muted text-center py-4" style="font-size:.85rem;">No hay categorías registradas.</div>
                @endforelse
                @error('category')
                    <div class="alert alert-danger m-3 mb-0 p-2" style="font-size:.82rem; border-radius:10px;">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

@endsection
