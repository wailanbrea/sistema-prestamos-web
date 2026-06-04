@extends('layouts.app')

@section('title', 'Clientes - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Clientes</h1>
                <p class="text-muted mb-0">Gestión de clientes, riesgo, contacto y datos laborales.</p>
            </div>
            @can('clients.create')
                <div class="d-flex gap-2">
                    <a href="{{ route('clients.links.index') }}" class="btn btn-outline-success">
                        <i class="fa-brands fa-whatsapp me-2"></i> Link por WhatsApp
                    </a>
                    <a href="{{ route('clients.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-2"></i> Nuevo cliente
                    </a>
                </div>
            @endcan
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('clients.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label for="search" class="form-label">Buscar</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Nombre, código, cédula o teléfono">
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'moroso' => 'Moroso', 'blocked' => 'Bloqueado'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="risk_level" class="form-label">Riesgo</label>
                    <select id="risk_level" name="risk_level" class="form-select">
                        <option value="">Todos</option>
                        @foreach (['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto', 'critical' => 'Crítico'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['risk_level'] ?? '') === $value)>{{ $label }}</option>
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
                            <th>Cliente</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th>Riesgo</th>
                            <th class="text-end">Ingreso</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr>
                                <td>
                                    <a href="{{ route('clients.show', $client) }}" class="fw-semibold text-decoration-none">{{ $client->full_name }}</a>
                                    <div class="text-muted small">{{ $client->code ?: 'Sin código' }} · {{ $client->identification ?: 'Sin identificación' }}</div>
                                </td>
                                <td>
                                    <div>{{ $client->phone ?: 'Sin teléfono' }}</div>
                                    <div class="text-muted small">{{ $client->email ?: 'Sin email' }}</div>
                                </td>
                                <td>@include('clients.partials.status-badge', ['status' => $client->status])</td>
                                <td>@include('clients.partials.risk-badge', ['risk' => $client->risk_level])</td>
                                <td class="text-end">{{ currency() }} {{ number_format((float) $client->monthly_income, 2) }}</td>
                                <td class="text-end">
                                    @can('clients.update')
                                        <a href="{{ route('clients.edit', $client) }}" class="btn btn-link text-dark text-decoration-none">Editar</a>
                                    @endcan
                                    @can('clients.delete')
                                        <form action="{{ route('clients.destroy', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este cliente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger text-decoration-none">Eliminar</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">No hay clientes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $clients->links() }}
            </div>
        </div>
    </section>
@endsection
