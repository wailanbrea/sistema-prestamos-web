@extends('layouts.app')

@section('title', $client->full_name.' - '.config('app.name'))

@section('content')
    <section class="mb-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <div class="text-muted small text-uppercase">Expediente del cliente</div>
                <h1 class="h4 fw-bold mb-1">{{ $client->full_name }}</h1>
                <p class="text-muted small mb-0">{{ $client->code ?: 'Sin codigo' }} · {{ $client->identification ?: 'Sin identificacion' }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('clients.index') }}" class="btn btn-sm btn-outline-secondary">Volver</a>
                @can('clients.update')
                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                    </a>
                @endcan
            </div>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-8">
            <article class="card content-card h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Informacion general</h2>
                    <p class="text-muted small mb-0">Datos personales, contacto y trabajo.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Telefono</span><strong>{{ $client->phone ?: 'No registrado' }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Telefono secundario</span><strong>{{ $client->secondary_phone ?: 'No registrado' }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Email</span><strong>{{ $client->email ?: 'No registrado' }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Ingreso mensual</span><strong>{{ currency() }} {{ number_format((float) $client->monthly_income, 2) }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Lugar de trabajo</span><strong>{{ $client->workplace ?: 'No registrado' }}</strong></div>
                        <div class="col-12 col-md-6"><span class="text-muted small d-block">Telefono laboral</span><strong>{{ $client->workplace_phone ?: 'No registrado' }}</strong></div>
                        <div class="col-12">
                            <span class="text-muted small d-block">Direccion</span>
                            <strong>{{ $client->address ?: 'No registrada' }}</strong>
                            @if ($client->latitude !== null && $client->longitude !== null)
                                <div class="small mt-1">
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($client->latitude.','.$client->longitude) }}" target="_blank" rel="noopener" class="text-decoration-none">
                                        <i class="fa-solid fa-map-location-dot me-1"></i> Ver ubicacion
                                    </a>
                                    <span class="text-muted ms-2">{{ $client->location_reference ?: 'Coordenadas registradas' }}</span>
                                </div>
                            @else
                                <div class="text-danger small mt-1">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Falta latitud y longitud para mostrarlo en el mapa.
                                </div>
                            @endif
                        </div>
                        <div class="col-12">
                            <span class="text-muted small d-block">Direccion laboral</span>
                            <strong>{{ $client->workplace_address ?: 'No registrada' }}</strong>
                            @if ($client->workplace_latitude !== null && $client->workplace_longitude !== null)
                                <div class="small mt-1">
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($client->workplace_latitude.','.$client->workplace_longitude) }}" target="_blank" rel="noopener" class="text-decoration-none">
                                        <i class="fa-solid fa-map-location-dot me-1"></i> Ver ubicacion laboral
                                    </a>
                                    <span class="text-muted ms-2">{{ $client->workplace_location_reference ?: 'Coordenadas laborales registradas' }}</span>
                                </div>
                            @elseif ($client->workplace_address)
                                <div class="text-muted small mt-1">Sin coordenadas laborales registradas.</div>
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
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Estado</span>
                        @include('clients.partials.status-badge', ['status' => $client->status])
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Riesgo</span>
                        @include('clients.partials.risk-badge', ['risk' => $client->risk_level])
                    </div>
                </div>
            </article>

            <article class="card content-card mb-3">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Documentos de identidad</h2>
                    <p class="text-muted small mb-0">Vista previa y descarga de los archivos privados cargados por el cliente.</p>
                </div>
                <div class="card-body">
                    @php
                        $identityDocuments = $client->documents->whereIn('document_type', ['identity_front', 'identity_back'])->keyBy('document_type');
                    @endphp
                    <div class="row g-3">
                        @foreach (['identity_front' => 'ID frontal', 'identity_back' => 'ID reverso'] as $type => $label)
                            <div class="col-12">
                                <div class="border rounded-3 p-2">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <span class="fw-semibold small">{{ $label }}</span>
                                        @if ($identityDocuments->has($type))
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('clients.documents.preview', [$client, $identityDocuments->get($type)]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fa-solid fa-up-right-from-square me-1"></i> Ver
                                                </a>
                                                <a href="{{ route('clients.documents.download', [$client, $identityDocuments->get($type)]) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-download me-1"></i> Descargar
                                                </a>
                                            </div>
                                        @else
                                            <span class="text-muted small">No cargado</span>
                                        @endif
                                    </div>
                                    @if ($identityDocuments->has($type))
                                        <a href="{{ route('clients.documents.preview', [$client, $identityDocuments->get($type)]) }}" target="_blank" rel="noopener" class="d-block text-decoration-none">
                                            <img
                                                src="{{ route('clients.documents.preview', [$client, $identityDocuments->get($type)]) }}"
                                                alt="{{ $label }}"
                                                class="img-fluid rounded-2 border"
                                                style="width: 100%; max-height: 220px; object-fit: cover;"
                                            >
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>

            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Historial</h2>
                    <p class="text-muted small mb-0">Resumen del expediente financiero del cliente.</p>
                </div>
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Prestamos</span><strong>{{ $client->loans()->count() }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-muted">Pagos</span><strong>{{ $client->payments()->count() }}</strong></div>
                </div>
            </article>
        </div>
    </section>
@endsection
