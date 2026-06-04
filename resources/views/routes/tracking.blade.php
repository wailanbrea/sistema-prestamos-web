@extends('layouts.app')

@section('title', 'Seguimiento de cobradores - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Seguimiento de cobradores</h1>
                <p class="text-muted mb-0">Ubicación en vivo, progreso de ruta y visitas verificadas por GPS.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('routes.map') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-map-location-dot me-2"></i> Mapa de cobros
                </a>
                <a href="{{ route('routes.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-route me-2"></i> Rutas
                </a>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <article class="card metric-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Cobradores transmitiendo</div>
                    <div id="tracking-active-count" class="h4 fw-bold mb-0">{{ count($sessions) }}</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-md-4">
            <article class="card metric-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Puntos asignados</div>
                    <div id="tracking-total-stops" class="h4 fw-bold mb-0">{{ collect($sessions)->sum(fn ($session) => count($session['stops'])) }}</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-md-4">
            <article class="card metric-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Puntos visitados</div>
                    <div id="tracking-visited-stops" class="h4 fw-bold mb-0 text-success">{{ collect($sessions)->sum(fn ($session) => collect($session['stops'])->where('visited', true)->count()) }}</div>
                </div>
            </article>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card content-card">
                <div class="card-body p-0 position-relative">
                    @if ($googleMapsApiKey === '')
                        <div class="alert alert-warning m-3 mb-0">
                            Configura <strong>GOOGLE_MAPS_API_KEY</strong> en el archivo .env para activar el mapa interactivo.
                        </div>
                    @endif
                    <div id="tracking-map" style="height: 660px; border-radius: 8px; overflow: hidden;"></div>
                    <div class="position-absolute top-0 end-0 m-3 badge text-bg-light border">
                        Actualiza cada 30 segundos
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card content-card h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Sesiones activas</h2>
                    <p class="text-muted small mb-0">El sistema marca visita cuando el GPS entra en el radio permitido.</p>
                </div>
                <div class="card-body">
                    <div id="tracking-session-list" class="vstack gap-3">
                        @forelse ($sessions as $session)
                            @php
                                $visited = collect($session['stops'])->where('visited', true)->count();
                                $total = count($session['stops']);
                            @endphp
                            <article class="border rounded-3 p-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold">{{ $session['collector']['name'] ?? 'Cobrador' }}</div>
                                        <div class="text-muted small">{{ $session['route']['name'] ?? 'Ruta' }}</div>
                                    </div>
                                    <span class="badge text-bg-primary align-self-start">{{ $visited }}/{{ $total }}</span>
                                </div>
                                <div class="progress mt-3" role="progressbar" aria-label="Progreso de visita">
                                    <div class="progress-bar" style="width: {{ $total > 0 ? round(($visited / $total) * 100) : 0 }}%"></div>
                                </div>
                                <div class="text-muted small mt-2">
                                    Última señal: {{ $session['last_location_at'] ? \Carbon\CarbonImmutable::parse($session['last_location_at'])->diffForHumans() : 'Sin señal todavía' }}
                                </div>
                                <div class="vstack gap-2 mt-3">
                                    @foreach ($session['stops'] as $stop)
                                        <div class="d-flex justify-content-between gap-2 small">
                                            <span class="{{ $stop['visited'] ? 'text-success' : 'text-muted' }}">
                                                {{ $stop['expected_order'] }}. {{ $stop['client_name'] }}
                                            </span>
                                            @if ($stop['visited'])
                                                <span class="badge {{ $stop['visit_status'] === 'visited_out_of_order' ? 'text-bg-warning' : 'text-bg-success' }}">
                                                    {{ $stop['visit_status'] === 'visited_out_of_order' ? 'Fuera de orden' : 'Visitado' }}
                                                </span>
                                            @else
                                                <span class="badge text-bg-light">Pendiente</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <div class="text-center text-muted py-5">
                                No hay cobradores compartiendo ubicación ahora mismo.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        window.routeTrackingSessions = @json($sessions);
        window.routeTrackingDataUrl = @json(route('routes.tracking.data'));
        window.trackingMapState = {
            map: null,
            infoWindow: null,
            directionsService: null,
            overlays: [],
            routeRenderers: [],
        };

        function initTrackingMap() {
            const element = document.getElementById('tracking-map');
            const defaultCenter = { lat: 18.4861, lng: -69.9312 };
            window.trackingMapState.map = new google.maps.Map(element, {
                center: defaultCenter,
                zoom: 11,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
            });
            window.trackingMapState.infoWindow = new google.maps.InfoWindow();
            window.trackingMapState.directionsService = new google.maps.DirectionsService();

            renderTrackingSessions(window.routeTrackingSessions || [], true);
            setInterval(refreshTrackingSessions, 30000);
        }

        async function refreshTrackingSessions() {
            try {
                const response = await fetch(window.routeTrackingDataUrl, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                window.routeTrackingSessions = payload.data || [];
                renderTrackingSummary(window.routeTrackingSessions);
                renderTrackingSessions(window.routeTrackingSessions, false);
            } catch (error) {
                console.warn('No se pudo actualizar el seguimiento.', error);
            }
        }

        function renderTrackingSessions(sessions, shouldFitBounds) {
            const state = window.trackingMapState;
            const map = state.map;
            if (!map) {
                return;
            }

            state.overlays.forEach((overlay) => overlay.setMap(null));
            state.routeRenderers.forEach((renderer) => renderer.setMap(null));
            state.overlays = [];
            state.routeRenderers = [];

            const bounds = new google.maps.LatLngBounds();
            let hasBounds = false;

            sessions.forEach((session) => {
                const lastLat = Number(session.last_latitude);
                const lastLng = Number(session.last_longitude);

                if (!Number.isNaN(lastLat) && !Number.isNaN(lastLng)) {
                    const collectorPosition = { lat: lastLat, lng: lastLng };
                    bounds.extend(collectorPosition);
                    hasBounds = true;

                    const marker = new google.maps.Marker({
                        map,
                        position: collectorPosition,
                        title: session.collector?.name || 'Cobrador',
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 9,
                            fillColor: '#0d6efd',
                            fillOpacity: 1,
                            strokeColor: '#ffffff',
                            strokeWeight: 3,
                        },
                    });
                    state.overlays.push(marker);
                    marker.addListener('click', () => {
                        state.infoWindow.setContent(`
                            <div style="min-width:220px">
                                <strong>${session.collector?.name || 'Cobrador'}</strong>
                                <div>${session.route?.name || 'Ruta'}</div>
                                <div style="margin-top:8px">Ultima señal: ${session.last_location_at || 'Pendiente'}</div>
                            </div>
                        `);
                        state.infoWindow.open(map, marker);
                    });
                }

                const routePath = [];
                (session.stops || []).forEach((stop) => {
                    const lat = Number(stop.latitude);
                    const lng = Number(stop.longitude);
                    if (Number.isNaN(lat) || Number.isNaN(lng)) {
                        return;
                    }

                    const position = { lat, lng };
                    routePath.push(position);
                    bounds.extend(position);
                    hasBounds = true;

                    const marker = new google.maps.Marker({
                        map,
                        position,
                        label: String(stop.expected_order),
                        title: stop.client_name,
                        opacity: stop.visited ? 1 : 0.65,
                    });
                    state.overlays.push(marker);
                    marker.addListener('click', () => {
                        state.infoWindow.setContent(`
                            <div style="min-width:240px">
                                <strong>${stop.client_name}</strong>
                                <div>${stop.address || ''}</div>
                                <div style="margin-top:8px">Estado: <strong>${stop.visited ? 'Visitado' : 'Pendiente'}</strong></div>
                                ${stop.visited_at ? `<div>Visitado: ${stop.visited_at}</div>` : ''}
                                ${stop.distance_meters ? `<div>Distancia registrada: ${stop.distance_meters} m</div>` : ''}
                            </div>
                        `);
                        state.infoWindow.open(map, marker);
                    });
                });

                if (routePath.length > 1) {
                    const renderer = new google.maps.DirectionsRenderer({
                        map,
                        suppressMarkers: true,
                        preserveViewport: true,
                        polylineOptions: {
                            strokeColor: '#5e72e4',
                            strokeOpacity: 0.65,
                            strokeWeight: 5,
                        },
                    });
                    state.routeRenderers.push(renderer);
                    state.directionsService.route({
                        origin: routePath[0],
                        destination: routePath[routePath.length - 1],
                        waypoints: routePath.slice(1, -1).slice(0, 23).map((point) => ({
                            location: point,
                            stopover: true,
                        })),
                        travelMode: google.maps.TravelMode.DRIVING,
                        optimizeWaypoints: false,
                    }, (response, status) => {
                        if (status === google.maps.DirectionsStatus.OK && response) {
                            renderer.setDirections(response);
                        }
                    });
                }
            });

            if (hasBounds && shouldFitBounds) {
                map.fitBounds(bounds, 80);
            }
        }

        function renderTrackingSummary(sessions) {
            const totalStops = sessions.reduce((total, session) => total + (session.stops || []).length, 0);
            const visitedStops = sessions.reduce((total, session) => total + (session.stops || []).filter((stop) => stop.visited).length, 0);
            document.getElementById('tracking-active-count').textContent = sessions.length;
            document.getElementById('tracking-total-stops').textContent = totalStops;
            document.getElementById('tracking-visited-stops').textContent = visitedStops;

            const list = document.getElementById('tracking-session-list');
            if (!list) {
                return;
            }

            if (!sessions.length) {
                list.innerHTML = '<div class="text-center text-muted py-5">No hay cobradores compartiendo ubicacion ahora mismo.</div>';
                return;
            }

            list.innerHTML = sessions.map((session) => {
                const stops = session.stops || [];
                const visited = stops.filter((stop) => stop.visited).length;
                const total = stops.length;
                const progress = total > 0 ? Math.round((visited / total) * 100) : 0;
                const lastSignal = session.last_location_at || 'Sin senal todavia';

                return `
                    <article class="border rounded-3 p-3">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold">${escapeHtml(session.collector?.name || 'Cobrador')}</div>
                                <div class="text-muted small">${escapeHtml(session.route?.name || 'Ruta')}</div>
                            </div>
                            <span class="badge text-bg-primary align-self-start">${visited}/${total}</span>
                        </div>
                        <div class="progress mt-3" role="progressbar" aria-label="Progreso de visita">
                            <div class="progress-bar" style="width: ${progress}%"></div>
                        </div>
                        <div class="text-muted small mt-2">Ultima senal: ${escapeHtml(lastSignal)}</div>
                        <div class="vstack gap-2 mt-3">
                            ${stops.map((stop) => `
                                <div class="d-flex justify-content-between gap-2 small">
                                    <span class="${stop.visited ? 'text-success' : 'text-muted'}">${stop.expected_order}. ${escapeHtml(stop.client_name)}</span>
                                    ${visitBadge(stop)}
                                </div>
                            `).join('')}
                        </div>
                    </article>
                `;
            }).join('');
        }

        function visitBadge(stop) {
            if (!stop.visited) {
                return '<span class="badge text-bg-light">Pendiente</span>';
            }

            if (stop.visit_status === 'visited_out_of_order') {
                return '<span class="badge text-bg-warning">Fuera de orden</span>';
            }

            return '<span class="badge text-bg-success">Visitado</span>';
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    </script>

    @if ($googleMapsApiKey !== '')
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}&libraries=places&v=weekly&callback=initTrackingMap"></script>
    @endif
@endsection
