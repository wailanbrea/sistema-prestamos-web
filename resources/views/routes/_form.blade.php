@csrf

@if ($method ?? null)
    @method($method)
@endif

@php
    $selectedClients = collect(old('client_ids', isset($routeModel) ? $routeModel->clients->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)
        ->all();
    $routeBuilderClients = $clients->map(fn ($client) => [
        'id' => (int) $client->id,
        'name' => $client->full_name,
        'phone' => $client->phone,
        'address' => $client->address,
        'latitude' => $client->latitude === null ? null : (float) $client->latitude,
        'longitude' => $client->longitude === null ? null : (float) $client->longitude,
        'selected' => in_array((string) $client->id, $selectedClients, true),
    ])->values();
@endphp

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Nombre de la ruta</label>
        <input id="name" name="name" type="text" value="{{ old('name', $routeModel->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" maxlength="150" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="status" class="form-label">Estado</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach (['active' => 'Activa', 'inactive' => 'Inactiva'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $routeModel->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="zone_id" class="form-label">Zona</label>
        <select id="zone_id" name="zone_id" class="form-select @error('zone_id') is-invalid @enderror">
            <option value="">Sin zona</option>
            @foreach ($zones as $zone)
                <option value="{{ $zone->id }}" @selected((string) old('zone_id', $routeModel->zone_id ?? '') === (string) $zone->id)>{{ $zone->name }}</option>
            @endforeach
        </select>
        @error('zone_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="collector_id" class="form-label">Cobrador</label>
        <select id="collector_id" name="collector_id" class="form-select @error('collector_id') is-invalid @enderror">
            <option value="">Sin cobrador asignado</option>
            @foreach ($collectors as $collector)
                <option value="{{ $collector->id }}" @selected((string) old('collector_id', $routeModel->collector_id ?? '') === (string) $collector->id)>{{ $collector->name }}</option>
            @endforeach
        </select>
        @error('collector_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Descripcion</label>
        <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $routeModel->description ?? '') }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-2 mb-2">
            <div>
                <label class="form-label mb-1">Constructor visual de ruta</label>
                <div class="form-text mb-0">Agrega clientes desde la lista o el mapa. Arrastra en "Orden de visita" para cambiar la secuencia.</div>
            </div>
            <button type="button" id="clear-route-clients" class="btn btn-sm btn-outline-danger">
                <i class="fa-solid fa-eraser me-1"></i> Limpiar ruta
            </button>
        </div>

        @if (($googleMapsApiKey ?? '') === '')
            <div class="alert alert-warning">
                Configura <strong>GOOGLE_MAPS_API_KEY</strong> en .env para activar el mapa y la ruta real por calles.
            </div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-xl-7">
                <div class="border rounded-3 overflow-hidden position-relative">
                    <div id="route-builder-map" style="height: 520px;"></div>
                    <div id="route-builder-warning" class="alert alert-warning m-3 position-absolute top-0 start-0 d-none" style="z-index: 5; max-width: 420px;"></div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="row g-3">
                    <div class="col-12">
                        <input id="route-client-search" type="search" class="form-control" placeholder="Buscar cliente, telefono o direccion">
                    </div>
                    <div class="col-12 col-lg-6 col-xl-12 col-xxl-6">
                        <div class="border rounded-3 h-100">
                            <div class="px-3 py-2 border-bottom fw-semibold">Clientes disponibles</div>
                            <div id="available-clients" class="vstack gap-2 p-3" style="min-height: 220px; max-height: 420px; overflow: auto;"></div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 col-xl-12 col-xxl-6">
                        <div class="border rounded-3 h-100">
                            <div class="px-3 py-2 border-bottom fw-semibold d-flex justify-content-between">
                                <span>Orden de visita</span>
                                <span id="route-client-count" class="badge text-bg-primary">0</span>
                            </div>
                            <div id="selected-clients" class="vstack gap-2 p-3" style="min-height: 220px; max-height: 420px; overflow: auto;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="route-client-inputs"></div>
        @error('client_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @error('client_ids.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('routes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-2"></i> Guardar ruta
    </button>
</div>

<template id="route-client-card-template">
    <div class="route-client-card border rounded-3 p-2 bg-white" data-client-id="">
        <div class="d-flex align-items-start gap-2">
            <span class="route-client-handle text-muted pt-1" title="Arrastrar"><i class="fa-solid fa-grip-vertical"></i></span>
            <div class="min-w-0 flex-grow-1">
                <div class="fw-semibold text-truncate route-client-name"></div>
                <div class="small text-muted text-truncate route-client-meta"></div>
                <div class="small route-client-location"></div>
            </div>
            <button type="button" class="btn btn-sm route-client-action"></button>
        </div>
    </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    window.routeBuilderClients = @json($routeBuilderClients);
    window.routeBuilderSelectedIds = @json($selectedClients);

    (function () {
        const clients = window.routeBuilderClients || [];
        let selectedIds = [...new Set((window.routeBuilderSelectedIds || []).map(String))];
        let map = null;
        let infoWindow = null;
        let directionsService = null;
        let directionsRenderer = null;
        const markers = new Map();
        const availableEl = document.getElementById('available-clients');
        const selectedEl = document.getElementById('selected-clients');
        const inputsEl = document.getElementById('route-client-inputs');
        const countEl = document.getElementById('route-client-count');
        const searchEl = document.getElementById('route-client-search');
        const warningEl = document.getElementById('route-builder-warning');
        const template = document.getElementById('route-client-card-template');

        function byId(id) {
            return clients.find((client) => String(client.id) === String(id));
        }

        function hasCoordinates(client) {
            return client && client.latitude !== null && client.longitude !== null;
        }

        function clientMatchesSearch(client) {
            const term = (searchEl.value || '').trim().toLowerCase();
            if (!term) return true;
            return [client.name, client.phone, client.address].filter(Boolean).join(' ').toLowerCase().includes(term);
        }

        function render() {
            renderAvailable();
            renderSelected();
            renderHiddenInputs();
            refreshMarkers();
            renderRoute();
        }

        function renderAvailable() {
            availableEl.innerHTML = '';
            clients
                .filter((client) => !selectedIds.includes(String(client.id)))
                .filter(clientMatchesSearch)
                .forEach((client) => availableEl.appendChild(cardFor(client, 'add')));

            if (availableEl.innerHTML === '') {
                availableEl.innerHTML = '<div class="text-muted small">No hay clientes disponibles.</div>';
            }
        }

        function renderSelected() {
            selectedEl.innerHTML = '';
            selectedIds.map(byId).filter(Boolean).forEach((client, index) => {
                selectedEl.appendChild(cardFor(client, 'remove', index + 1));
            });
            countEl.textContent = String(selectedIds.length);

            if (selectedEl.innerHTML === '') {
                selectedEl.innerHTML = '<div class="text-muted small">Arrastra o agrega clientes para construir la ruta.</div>';
            }
        }

        function cardFor(client, action, order = null) {
            const node = template.content.firstElementChild.cloneNode(true);
            node.dataset.clientId = String(client.id);
            node.querySelector('.route-client-name').textContent = order ? `${order}. ${client.name}` : client.name;
            node.querySelector('.route-client-meta').textContent = [client.phone, client.address].filter(Boolean).join(' · ') || 'Sin contacto';
            node.querySelector('.route-client-location').innerHTML = hasCoordinates(client)
                ? '<span class="text-success"><i class="fa-solid fa-location-dot me-1"></i>Con coordenadas</span>'
                : '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Sin coordenadas</span>';

            const button = node.querySelector('.route-client-action');
            button.className = action === 'add' ? 'btn btn-sm btn-outline-primary route-client-action' : 'btn btn-sm btn-outline-danger route-client-action';
            button.innerHTML = action === 'add' ? '<i class="fa-solid fa-plus"></i>' : '<i class="fa-solid fa-xmark"></i>';
            button.addEventListener('click', () => action === 'add' ? addClient(client.id) : removeClient(client.id));

            node.addEventListener('mouseenter', () => openMarker(client.id));
            return node;
        }

        function renderHiddenInputs() {
            inputsEl.innerHTML = '';
            selectedIds.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'client_ids[]';
                input.value = id;
                inputsEl.appendChild(input);
            });
        }

        function addClient(id) {
            const normalized = String(id);
            if (!selectedIds.includes(normalized)) {
                selectedIds.push(normalized);
                render();
            }
        }

        function removeClient(id) {
            selectedIds = selectedIds.filter((selectedId) => selectedId !== String(id));
            render();
        }

        function showWarning(message) {
            warningEl.textContent = message || '';
            warningEl.classList.toggle('d-none', !message);
        }

        function refreshMarkers() {
            if (!map) return;
            markers.forEach((marker) => marker.setMap(null));
            markers.clear();

            const bounds = new google.maps.LatLngBounds();
            let hasAnyMarker = false;
            clients.forEach((client) => {
                if (!hasCoordinates(client)) return;
                const selectedIndex = selectedIds.indexOf(String(client.id));
                const marker = new google.maps.Marker({
                    map,
                    position: { lat: Number(client.latitude), lng: Number(client.longitude) },
                    title: client.name,
                    label: selectedIndex >= 0 ? String(selectedIndex + 1) : undefined,
                    opacity: selectedIndex >= 0 ? 1 : 0.62,
                });
                marker.addListener('click', () => {
                    infoWindow.setContent(`
                        <div style="min-width:220px">
                            <strong>${client.name}</strong>
                            <div>${client.address || ''}</div>
                            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="window.routeBuilderAddClient(${client.id})">Agregar a ruta</button>
                        </div>
                    `);
                    infoWindow.open(map, marker);
                });
                markers.set(String(client.id), marker);
                bounds.extend(marker.getPosition());
                hasAnyMarker = true;
            });

            if (hasAnyMarker) {
                map.fitBounds(bounds, 80);
            }
        }

        function openMarker(id) {
            if (!map || !markers.has(String(id))) return;
            const marker = markers.get(String(id));
            map.panTo(marker.getPosition());
        }

        function renderRoute() {
            if (!map || !directionsService || !directionsRenderer) return;
            directionsRenderer.set('directions', null);
            showWarning(null);

            const selectedClients = selectedIds.map(byId).filter(hasCoordinates);
            if (selectedClients.length < 2) return;

            if (selectedClients.length > 25) {
                showWarning('Google Directions permite hasta 25 puntos por solicitud. Reduce la ruta o dividela.');
                return;
            }

            const origin = selectedClients[0];
            const destination = selectedClients[selectedClients.length - 1];
            const waypoints = selectedClients.slice(1, -1).map((client) => ({
                location: { lat: Number(client.latitude), lng: Number(client.longitude) },
                stopover: true,
            }));

            directionsService.route({
                origin: { lat: Number(origin.latitude), lng: Number(origin.longitude) },
                destination: { lat: Number(destination.latitude), lng: Number(destination.longitude) },
                waypoints,
                optimizeWaypoints: false,
                travelMode: google.maps.TravelMode.DRIVING,
            }, (response, status) => {
                if (status === google.maps.DirectionsStatus.OK && response) {
                    directionsRenderer.setDirections(response);
                    return;
                }

                showWarning('Google no pudo calcular una ruta real. Revisa coordenadas o habilita Directions API.');
            });
        }

        new Sortable(selectedEl, {
            animation: 150,
            handle: '.route-client-handle',
            onEnd: () => {
                selectedIds = Array.from(selectedEl.querySelectorAll('[data-client-id]')).map((node) => node.dataset.clientId);
                render();
            },
        });

        searchEl.addEventListener('input', renderAvailable);
        document.getElementById('clear-route-clients').addEventListener('click', () => {
            selectedIds = [];
            render();
        });

        window.routeBuilderAddClient = addClient;
        window.initRouteBuilderMap = function () {
            map = new google.maps.Map(document.getElementById('route-builder-map'), {
                center: { lat: 18.4861, lng: -69.9312 },
                zoom: 11,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
            });
            infoWindow = new google.maps.InfoWindow();
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map,
                suppressMarkers: true,
                preserveViewport: true,
                polylineOptions: {
                    strokeColor: '#5e72e4',
                    strokeOpacity: 0.9,
                    strokeWeight: 5,
                },
            });
            render();
        };

        render();
    })();
</script>

@if (($googleMapsApiKey ?? '') !== '')
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}&libraries=places&v=weekly&callback=initRouteBuilderMap"></script>
@endif
