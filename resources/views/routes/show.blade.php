@extends('layouts.app')

@section('title', $routeModel->name.' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $routeModel->name }}</h1>
                <div class="d-flex flex-wrap gap-2">
                    @include('routes.partials.status-badge', ['status' => $routeModel->status])
                    <span class="badge text-bg-light border text-dark">{{ $routeModel->zone?->name ?: 'Sin zona' }}</span>
                    <span class="badge text-bg-light border text-dark">{{ $routeModel->collector?->name ?: 'Sin cobrador' }}</span>
                </div>
            </div>
            <a href="{{ route('routes.edit', $routeModel) }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-pen me-2"></i> Editar
            </a>
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Detalle</h2>
            <dl class="row mb-0">
                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $routeModel->description ?: 'Sin descripción' }}</dd>
                <dt class="col-sm-3">Cobrador</dt>
                <dd class="col-sm-9">
                    {{ $routeModel->collector?->name ?: 'Sin cobrador' }}
                    @if ($routeModel->collector?->phone)
                        <span class="text-muted">· {{ $routeModel->collector->phone }}</span>
                    @endif
                </dd>
            </dl>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Clientes asignados</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($routeModel->clients as $client)
                            <tr>
                                <td>#{{ $client->pivot->order_number }}</td>
                                <td>
                                    <a href="{{ route('clients.show', $client) }}" class="text-decoration-none fw-semibold">{{ $client->full_name }}</a>
                                </td>
                                <td>{{ $client->phone ?: 'Sin teléfono' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-5">Esta ruta no tiene clientes asignados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
