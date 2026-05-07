@extends('layouts.app')

@section('title', $client->full_name.' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $client->full_name }}</h1>
                <p class="text-muted mb-0">{{ $client->code ?: 'Sin código' }} · {{ $client->identification ?: 'Sin identificación' }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Volver</a>
                @can('clients.update')
                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary">
                        <i class="fa-solid fa-pen-to-square me-2"></i> Editar
                    </a>
                @endcan
            </div>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-8">
            <article class="card content-card h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Información general</h2>
                    <p class="text-muted small mb-0">Datos personales, contacto y trabajo.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Teléfono</span><strong>{{ $client->phone ?: 'No registrado' }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Teléfono secundario</span><strong>{{ $client->secondary_phone ?: 'No registrado' }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Email</span><strong>{{ $client->email ?: 'No registrado' }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Ingreso mensual</span><strong>RD$ {{ number_format((float) $client->monthly_income, 2) }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Lugar de trabajo</span><strong>{{ $client->workplace ?: 'No registrado' }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Teléfono laboral</span><strong>{{ $client->workplace_phone ?: 'No registrado' }}</strong></div>
                        <div class="col-12">
                            <span class="text-muted small d-block">Dirección</span>
                            <strong>{{ $client->address ?: 'No registrada' }}</strong>
                            @if ($client->latitude !== null && $client->longitude !== null)
                                <div class="small mt-1">
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($client->latitude.','.$client->longitude) }}" target="_blank" rel="noopener" class="text-decoration-none">
                                        <i class="fa-solid fa-map-location-dot me-1"></i> Ver ubicación
                                    </a>
                                    <span class="text-muted ms-2">{{ $client->location_reference ?: 'Coordenadas registradas' }}</span>
                                </div>
                            @else
                                <div class="text-danger small mt-1">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Falta latitud y longitud para mostrarlo en el mapa.
                                </div>
                            @endif
                        </div>
                        <div class="col-12"><span class="text-muted small d-block">Notas</span><div>{{ $client->notes ?: 'Sin notas' }}</div></div>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-4">
            <article class="card content-card mb-3">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Estado</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-3">
                        <span class="text-muted">Estado</span>
                        @include('clients.partials.status-badge', ['status' => $client->status])
                    </div>
                    <div class="d-flex justify-content-between py-3">
                        <span class="text-muted">Riesgo</span>
                        @include('clients.partials.risk-badge', ['risk' => $client->risk_level])
                    </div>
                </div>
            </article>

            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Historial</h2>
                    <p class="text-muted small mb-0">Se integrará con préstamos, pagos y documentos.</p>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Préstamos</span><strong>{{ $client->loans()->count() }}</strong></div>
                    <div class="d-flex justify-content-between py-3"><span class="text-muted">Pagos</span><strong>{{ $client->payments()->count() }}</strong></div>
                </div>
            </article>
        </div>
    </section>
@endsection
