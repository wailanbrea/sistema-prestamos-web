@extends('layouts.app')

@section('title', 'Zonas y rutas - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Zonas y rutas</h1>
                <p class="text-muted mb-0">Organización territorial para cobradores y seguimiento de clientes.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('routes.map') }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-map-location-dot me-2"></i> Mapa
                </a>
                <a href="{{ route('routes.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-2"></i> Nueva ruta
                </a>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card content-card h-100">
                <div class="card-body">
                    <form method="GET" action="{{ route('routes.index') }}" class="row g-3 align-items-end">
                        <div class="col-12 col-lg-5">
                            <label for="search" class="form-label">Buscar ruta</label>
                            <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Nombre o descripción">
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label for="zone_id" class="form-label">Zona</label>
                            <select id="zone_id" name="zone_id" class="form-select">
                                <option value="">Todas</option>
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}" @selected((string) ($filters['zone_id'] ?? '') === (string) $zone->id)>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label for="status" class="form-label">Estado</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Todas</option>
                                @foreach (['active' => 'Activa', 'inactive' => 'Inactiva'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
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
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Nueva zona</h2>
                    <form method="POST" action="{{ route('zones.store') }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="zone_name" class="form-label">Nombre</label>
                            <input id="zone_name" name="name" type="text" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" maxlength="150">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label for="zone_description" class="form-label">Descripción</label>
                            <textarea id="zone_description" name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fa-solid fa-map-location-dot me-2"></i> Crear zona
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card content-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Ruta</th>
                                    <th>Zona</th>
                                    <th>Cobrador</th>
                                    <th class="text-end">Clientes</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($routes as $routeModel)
                                    <tr>
                                        <td>
                                            <a href="{{ route('routes.show', $routeModel) }}" class="fw-semibold text-decoration-none">{{ $routeModel->name }}</a>
                                            <div class="text-muted small">{{ $routeModel->description ?: 'Sin descripción' }}</div>
                                        </td>
                                        <td>{{ $routeModel->zone?->name ?: 'Sin zona' }}</td>
                                        <td>{{ $routeModel->collector?->name ?: 'Sin cobrador' }}</td>
                                        <td class="text-end">{{ $routeModel->clients_count }}</td>
                                        <td>@include('routes.partials.status-badge', ['status' => $routeModel->status])</td>
                                        <td class="text-end">
                                            <a href="{{ route('routes.edit', $routeModel) }}" class="btn btn-link text-dark text-decoration-none">Editar</a>
                                            <form action="{{ route('routes.destroy', $routeModel) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta ruta?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link text-danger text-decoration-none">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">No hay rutas registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $routes->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card content-card">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Zonas</h2>
                    <div class="vstack gap-3">
                        @forelse ($zones as $zone)
                            <div class="d-flex align-items-start justify-content-between gap-3 border-bottom pb-3">
                                <div>
                                    <div class="fw-semibold">{{ $zone->name }}</div>
                                    <div class="text-muted small">{{ $zone->routes_count }} rutas</div>
                                </div>
                                <form action="{{ route('zones.destroy', $zone) }}" method="POST" onsubmit="return confirm('¿Eliminar esta zona?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none">Eliminar</button>
                                </form>
                            </div>
                        @empty
                            <div class="text-muted">No hay zonas registradas.</div>
                        @endforelse
                    </div>
                    @error('zone') <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </section>
@endsection
