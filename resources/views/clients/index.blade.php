@extends('layouts.app')

@section('title', 'Clientes — '.config('app.name'))

@push('styles')
<style>
    .client-avatar {
        width: 38px; height: 38px; border-radius: 99px;
        display: inline-grid; place-items: center;
        font-weight: 700; font-size: .8rem; flex-shrink: 0;
    }
    .status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 99px;
        font-size: .7rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
    }
    .status-badge::before {
        content: ''; width: 6px; height: 6px; border-radius: 99px;
        background: currentColor; opacity: .7;
    }
    .filter-chip {
        padding: 6px 16px; border-radius: 99px; font-size: .8rem; font-weight: 500;
        border: 1px solid var(--app-border); background: var(--app-surface);
        color: var(--app-muted); white-space: nowrap; cursor: pointer;
        transition: all .15s ease; text-decoration: none;
    }
    .filter-chip.active, .filter-chip:hover {
        background: var(--app-primary); border-color: var(--app-primary); color: #fff;
    }
    .filter-chips { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; }
    .filter-chips::-webkit-scrollbar { display: none; }

    .clients-table { border-collapse: separate; border-spacing: 0; }
    .clients-table td, .clients-table th { border-color: var(--app-border-light) !important; }
    .clients-table thead th {
        font-size: .7rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .06em; color: var(--app-muted);
        background: var(--app-surface-low); border-bottom: 1px solid var(--app-border) !important;
        padding: 10px 16px;
    }
    .clients-table tbody td { padding: 12px 16px; vertical-align: middle; transition: background .12s ease; }
    .clients-table tbody tr { cursor: pointer; }
    .clients-table tbody tr:hover td { background: rgba(0,38,83,.04); }
    .clients-table tbody tr:hover td:first-child {
        border-left: 3px solid var(--app-primary);
        padding-left: 13px;
    }
    .clients-table tbody td:first-child {
        border-left: 3px solid transparent;
        transition: background .12s ease, border-color .12s ease, padding-left .12s ease;
    }
    .btn-action {
        font-size: .78rem; padding: 4px 12px; border-radius: 8px;
        font-weight: 500; text-decoration: none; border: 1px solid;
        transition: all .12s ease; display: inline-block;
    }
    .btn-action-outline {
        border-color: var(--app-border); color: var(--app-text); background: transparent;
    }
    .btn-action-outline:hover { border-color: var(--app-primary); color: var(--app-primary); }
    .btn-action-primary {
        border-color: var(--app-primary); color: var(--app-primary); background: transparent;
    }
    .btn-action-primary:hover { background: var(--app-primary); color: #fff; }
</style>
@endpush

@section('content')

{{-- ── Header ── --}}
<section class="mb-4 anim-fade-up">
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div>
            <h1 class="h4 fw-bold mb-1" style="color:var(--app-primary);">Clientes</h1>
            <p class="text-muted mb-0" style="font-size:.88rem;">
                {{ number_format($clients->total()) }} clientes registrados
            </p>
        </div>
        @can('clients.create')
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="{{ route('clients.links.index') }}" class="btn btn-sm fw-semibold"
                   style="border-radius:10px; border:1px solid var(--app-border); background:var(--app-surface); color:var(--app-text); padding:8px 14px;">
                    <i class="fa-brands fa-whatsapp me-2 text-success"></i> Link WhatsApp
                </a>
                <a href="{{ route('clients.create') }}" class="btn btn-sm fw-semibold"
                   style="background:var(--app-primary); color:#fff; border-radius:10px; border:none; padding:8px 16px;">
                    <i class="fa-solid fa-plus me-2"></i> Nuevo cliente
                </a>
            </div>
        @endcan
    </div>
</section>

{{-- ── Search + filters ── --}}
<section class="card content-card mb-3 anim-fade-up" style="animation-delay:60ms;">
    <div class="card-body pb-2">
        <form method="GET" action="{{ route('clients.index') }}" id="filterForm">
            <div class="d-flex gap-2 mb-3">
                <div class="position-relative flex-grow-1">
                    <i class="fa-solid fa-magnifying-glass position-absolute"
                       style="left:14px; top:50%; transform:translateY(-50%); color:var(--app-muted); pointer-events:none; font-size:.85rem;"></i>
                    <input name="search" id="search" type="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Nombre, cédula o teléfono..."
                           class="form-control"
                           style="padding-left:40px; border-radius:10px; border-color:var(--app-border); font-size:.9rem;">
                </div>
                <button type="submit" class="btn"
                        style="background:var(--app-primary); color:#fff; border-radius:10px; border:none; padding:8px 16px; font-size:.88rem;">
                    Buscar
                </button>
            </div>

            <div class="filter-chips pb-2">
                @php
                    $statusFilters = [
                        ''         => 'Todos',
                        'active'   => 'Activos',
                        'moroso'   => 'En mora',
                        'inactive' => 'Inactivos',
                        'blocked'  => 'Bloqueados',
                    ];
                @endphp
                @foreach ($statusFilters as $val => $label)
                    <a href="{{ route('clients.index', array_merge(request()->except('status','page'), ['status' => $val])) }}"
                       class="filter-chip {{ ($filters['status'] ?? '') === $val ? 'active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if(!empty($filters['search']))
                <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
            @endif
        </form>
    </div>
</section>

{{-- ── Table ── --}}
<div class="card content-card anim-fade-up" style="animation-delay:100ms;">
    <div class="table-responsive">
        <table class="table clients-table mb-0">
            <thead>
                <tr>
                    <th style="width:40%;">Cliente</th>
                    <th class="d-none d-md-table-cell">Cédula</th>
                    <th class="d-none d-lg-table-cell">Teléfono</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    @php
                        $initials = strtoupper(
                            substr($client->full_name, 0, 1) .
                            (str_contains($client->full_name, ' ') ? substr(strrchr($client->full_name, ' '), 1, 1) : '')
                        );
                        [$statusLabel, $pillBg, $pillText] = match($client->status) {
                            'active'   => ['Activo',    '#dcfce7', '#166534'],
                            'moroso'   => ['En mora',   '#fee2e2', '#991b1b'],
                            'inactive' => ['Inactivo',  '#f1f5f9', '#475569'],
                            'blocked'  => ['Bloqueado', '#fef9c3', '#854d0e'],
                            default    => [ucfirst($client->status), '#f1f5f9', '#475569'],
                        };
                    @endphp
                    <tr onclick="location.href='{{ route('clients.show', $client) }}'">
                        <td>
                            <a href="{{ route('clients.show', $client) }}"
                               class="d-flex align-items-center gap-3 text-decoration-none">
                                <div class="client-avatar"
                                     style="background:var(--app-primary-light); color:var(--app-primary);">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size:.9rem; color:var(--app-text);">
                                        {{ $client->full_name }}
                                    </div>
                                    <div class="text-muted d-md-none" style="font-size:.74rem;">
                                        {{ $client->identification ?: 'Sin cédula' }}
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td class="text-muted d-none d-md-table-cell" style="font-size:.86rem;">
                            {{ $client->identification ?: '—' }}
                        </td>
                        <td class="text-muted d-none d-lg-table-cell" style="font-size:.86rem;">
                            {{ $client->phone ?: '—' }}
                        </td>
                        <td>
                            <span class="status-badge"
                                  style="background:{{ $pillBg }}; color:{{ $pillText }};">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('clients.show', $client) }}"
                               class="btn-action btn-action-outline">Ver</a>
                            @can('clients.update')
                                <a href="{{ route('clients.edit', $client) }}"
                                   class="btn-action btn-action-primary ms-1">Editar</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="fa-solid fa-users-slash fa-2x mb-3 d-block opacity-40"></i>
                            No hay clientes con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($clients->hasPages())
        <div class="px-4 py-3 border-top" style="border-color:var(--app-border-light) !important;">
            {{ $clients->withQueryString()->links() }}
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    let debounce;
    document.getElementById('search')?.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => document.getElementById('filterForm').submit(), 500);
    });
</script>
@endpush
