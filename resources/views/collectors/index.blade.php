@extends('layouts.app')

@section('title', 'Cobradores - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Cobradores</h1>
                <p class="text-muted mb-0">Control de cobradores, usuarios vinculados y comisiones por cobro.</p>
            </div>
            <a href="{{ route('collectors.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i> Nuevo cobrador
            </a>
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('collectors.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label for="search" class="form-label">Buscar</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Nombre o teléfono">
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach (['active' => 'Activo', 'inactive' => 'Inactivo'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="commission_type" class="form-label">Comisión</label>
                    <select id="commission_type" name="commission_type" class="form-select">
                        <option value="">Todas</option>
                        @foreach (['none' => 'Sin comisión', 'percentage' => 'Porcentaje', 'fixed' => 'Monto fijo'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['commission_type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-1 d-grid">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cobrador</th>
                            <th>Usuario</th>
                            <th>Comisión</th>
                            <th>Regla</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($collectors as $collector)
                            <tr>
                                <td>
                                    <a href="{{ route('collectors.show', $collector) }}" class="fw-semibold text-decoration-none">{{ $collector->name }}</a>
                                    <div class="text-muted small">{{ $collector->phone ?: 'Sin teléfono' }}</div>
                                </td>
                                <td>
                                    @if ($collector->user)
                                        <div>{{ $collector->user->name }}</div>
                                        <div class="text-muted small">{{ $collector->user->email }}</div>
                                    @else
                                        <span class="text-muted">Sin usuario vinculado</span>
                                    @endif
                                </td>
                                <td>
                                    @include('collectors.partials.commission-badge', ['type' => $collector->commission_type])
                                    <div class="text-muted small">
                                        @if ($collector->commission_type === 'percentage')
                                            {{ number_format((float) $collector->commission_value, 2) }}%
                                        @elseif ($collector->commission_type === 'fixed')
                                            {{ currency() }} {{ number_format((float) $collector->commission_value, 2) }}
                                        @else
                                            No aplica
                                        @endif
                                    </div>
                                </td>
                                <td><span class="text-muted small">{{ $collector->commission_base === 'principal_only' ? 'Solo capital' : 'Total cobrado' }}</span></td>
                                <td>@include('collectors.partials.status-badge', ['status' => $collector->status])</td>
                                <td class="text-end">
                                    <a href="{{ route('collectors.edit', $collector) }}" class="btn btn-link text-dark text-decoration-none">Editar</a>
                                    <form action="{{ route('collectors.destroy', $collector) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este cobrador?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link text-danger text-decoration-none">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">No hay cobradores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $collectors->links() }}
            </div>
        </div>
    </section>
@endsection
