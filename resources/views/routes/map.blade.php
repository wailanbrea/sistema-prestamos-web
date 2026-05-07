@extends('layouts.app')

@section('title', 'Mapa de cobros - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Mapa de cobros</h1>
                <p class="text-muted mb-0">UbicaciÃ³n, deuda, pagos y rutas asignadas por cobrador.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('routes.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-route me-2"></i> Rutas
                </a>
                <a href="{{ route('routes.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-2"></i> Nueva ruta
                </a>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <article class="card metric-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Clientes en rutas</div>
                    <div class="h4 fw-bold mb-0">{{ number_format((int) $totals['clients']) }}</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-md-3">
            <article class="card metric-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Con coordenadas</div>
                    <div class="h4 fw-bold mb-0">{{ number_format((int) $totals['mapped_clients']) }}</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-md-3">
            <article class="card metric-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Faltan coordenadas</div>
                    <div class="h4 fw-bold mb-0 text-danger">{{ number_format((int) $totals['missing_coordinates']) }}</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-md-3">
            <article class="card metric-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Balance en ruta</div>
                    <div class="h4 fw-bold mb-0">RD$ {{ number_format((float) $totals['remaining_balance'], 2) }}</div>
                </div>
            </article>
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('routes.map') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="collector_id" class="form-label">Cobrador</label>
                    <select id="collector_id" name="collector_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($collectors as $collector)
                            <option value="{{ $collector->id }}" @selected((string) ($filters['collector_id'] ?? '') === (string) $collector->id)>{{ $collector->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label for="route_id" class="form-label">Ruta</label>
                    <select id="route_id" name="route_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($routes as $routeModel)
                            <option value="{{ $routeModel->id }}" @selected((string) ($filters['route_id'] ?? '') === (string) $routeModel->id)>
                                {{ $routeModel->name }}{{ $routeModel->collector ? ' · '.$routeModel->collector->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-filter me-2"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card content-card">
                <div class="card-body p-0">
                    @if ($googleMapsApiKey === '')
                        <div class="alert alert-warning m-3 mb-0">
                            Configura <strong>GOOGLE_MAPS_API_KEY</strong> en el archivo .env para activar el mapa interactivo.
                        </div>
                    @endif
                    <div id="collection-map" style="height: 620px; border-radius: 8px; overflow: hidden;"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card content-card h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Clientes de la ruta</h2>
                    <p class="text-muted small mb-0">Debe, pagado y estado de ubicaciÃ³n.</p>
                </div>
                <div class="card-body">
                    <div class="vstack gap-3">
                        @forelse ($clients as $client)
                            <div class="border rounded-3 p-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <a href="{{ route('clients.show', $client['id']) }}" class="fw-semibold text-decoration-none">{{ $client['full_name'] }}</a>
                                        <div class="text-muted small">{{ $client['address'] }}</div>
                                    </div>
                                    @if ($client['latitude'] === null || $client['longitude'] === null)
                                        <span class="badge text-bg-danger align-self-start">Sin mapa</span>
                                    @elseif ($client['late_loans_count'] > 0)
                                        <span class="badge text-bg-warning align-self-start">Mora</span>
                                    @else
                                        <span class="badge text-bg-success align-self-start">OK</span>
                                    @endif
                                </div>
                                <div class="row g-2 mt-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Debe</span>
                                        <strong>RD$ {{ number_format((float) $client['remaining_balance'], 2) }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Pagado</span>
                                        <strong>RD$ {{ number_format((float) $client['total_paid'], 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">No hay clientes para los filtros seleccionados.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $mappedClients = collect($clients)
            ->filter(fn ($client) => $client['latitude'] !== null && $client['longitude'] !== null)
            ->values();
    @endphp

    <script>
        window.collectionMapClients = @json($mappedClients);

        function initCollectionMap() {
            const element = document.getElementById('collection-map');
            const clients = window.collectionMapClients || [];
            const defaultCenter = { lat: 18.4861, lng: -69.9312 };
            const map = new google.maps.Map(element, {
                center: defaultCenter,
                zoom: clients.length ? 12 : 10,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
            });

            if (!clients.length) {
                return;
            }

            const bounds = new google.maps.LatLngBounds();
            const path = [];
            const currency = new Intl.NumberFormat('es-DO', {
                style: 'currency',
                currency: 'DOP',
            });
            const infoWindow = new google.maps.InfoWindow();

            clients.forEach((client, index) => {
                const position = {
                    lat: Number(client.latitude),
                    lng: Number(client.longitude),
                };
                bounds.extend(position);
                path.push(position);

                const marker = new google.maps.Marker({
                    map,
                    position,
                    label: String(index + 1),
                    title: client.full_name,
                });

                marker.addListener('click', () => {
                    const routes = (client.routes || []).map((route) => route.name).join(', ') || 'Sin ruta';
                    infoWindow.setContent(`
                        <div style="min-width:240px">
                            <strong>${client.full_name}</strong>
                            <div>${client.address || ''}</div>
                            <div style="margin-top:8px">Debe: <strong>${currency.format(Number(client.remaining_balance || 0))}</strong></div>
                            <div>Pagado: <strong>${currency.format(Number(client.total_paid || 0))}</strong></div>
                            <div>Ruta: ${routes}</div>
                            <a href="/clientes/${client.id}" style="display:inline-block;margin-top:8px">Ver cliente</a>
                        </div>
                    `);
                    infoWindow.open(map, marker);
                });
            });

            if (path.length > 1) {
                new google.maps.Polyline({
                    path,
                    geodesic: true,
                    strokeColor: '#5e72e4',
                    strokeOpacity: 0.85,
                    strokeWeight: 4,
                    map,
                });
            }

            map.fitBounds(bounds, 80);
        }
    </script>

    @if ($googleMapsApiKey !== '')
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}&callback=initCollectionMap"></script>
    @endif
@endsection
