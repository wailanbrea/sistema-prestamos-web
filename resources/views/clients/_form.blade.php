@csrf

@if ($method ?? null)
    @method($method)
@endif

@php
    $compactMode = (bool) ($compact ?? false);
    $labelClass = $compactMode ? 'form-label small mb-1' : 'form-label';
    $inputClass = $compactMode ? 'form-control form-control-sm' : 'form-control';
    $selectClass = $compactMode ? 'form-select form-select-sm' : 'form-select';
    $rowGapClass = $compactMode ? 'row g-2' : 'row g-3';
    $textAreaRows = $compactMode ? 2 : 3;
    $googleMapsApiKey = (string) config('services.google_maps.api_key');
    $defaultMapCenter = default_map_center();
@endphp

<div class="{{ $rowGapClass }}">
    <div class="col-12 col-md-4">
        <label for="code" class="{{ $labelClass }}">Codigo</label>
        <input id="code" name="code" type="text" value="{{ old('code', $client->code ?? '') }}" class="{{ $inputClass }} @error('code') is-invalid @enderror" maxlength="50">
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-8">
        <label for="full_name" class="{{ $labelClass }}">Nombre completo</label>
        <input id="full_name" name="full_name" type="text" value="{{ old('full_name', $client->full_name ?? '') }}" class="{{ $inputClass }} @error('full_name') is-invalid @enderror" maxlength="180" required>
        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="identification" class="{{ $labelClass }}">Cedula / identificacion</label>
        <input id="identification" name="identification" type="text" value="{{ old('identification', $client->identification ?? '') }}" class="{{ $inputClass }} @error('identification') is-invalid @enderror" maxlength="50">
        @error('identification') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="phone" class="{{ $labelClass }}">Telefono</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $client->phone ?? '') }}" class="{{ $inputClass }} @error('phone') is-invalid @enderror" maxlength="50">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="secondary_phone" class="{{ $labelClass }}">Telefono secundario</label>
        <input id="secondary_phone" name="secondary_phone" type="text" value="{{ old('secondary_phone', $client->secondary_phone ?? '') }}" class="{{ $inputClass }} @error('secondary_phone') is-invalid @enderror" maxlength="50">
        @error('secondary_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="email" class="{{ $labelClass }}">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $client->email ?? '') }}" class="{{ $inputClass }} @error('email') is-invalid @enderror" maxlength="150">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="monthly_income" class="{{ $labelClass }}">Ingreso mensual</label>
        <input id="monthly_income" name="monthly_income" type="number" step="0.01" min="0" value="{{ old('monthly_income', $client->monthly_income ?? '0') }}" class="{{ $inputClass }} @error('monthly_income') is-invalid @enderror">
        @error('monthly_income') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="status" class="{{ $labelClass }}">Estado</label>
        <select id="status" name="status" class="{{ $selectClass }} @error('status') is-invalid @enderror" required>
            @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'moroso' => 'Moroso', 'blocked' => 'Bloqueado'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $client->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="risk_level" class="{{ $labelClass }}">Nivel de riesgo</label>
        <select id="risk_level" name="risk_level" class="{{ $selectClass }} @error('risk_level') is-invalid @enderror" required>
            @foreach (['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto', 'critical' => 'Critico'] as $value => $label)
                <option value="{{ $value }}" @selected(old('risk_level', $client->risk_level ?? 'low') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('risk_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12"><div class="text-muted small text-uppercase fw-semibold">Ubicacion residencial</div></div>

    <div class="col-12">
        <label for="address" class="{{ $labelClass }}">Direccion</label>
        <input id="address" name="address" type="text" value="{{ old('address', $client->address ?? '') }}" class="{{ $inputClass }} @error('address') is-invalid @enderror" placeholder="Escribe una direccion, sector o punto de referencia" autocomplete="off" required>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Busca la direccion y elige el punto en el mapa. Tambien puedes mover el marcador o usar tu ubicacion actual.</div>
    </div>

    <div class="col-12">
        <div class="border rounded-3 overflow-hidden bg-white">
            @if ($googleMapsApiKey === '')
                <div class="alert alert-warning rounded-0 m-0">
                    El mapa no esta disponible ahora mismo. Aun puedes escribir la direccion manualmente.
                </div>
            @endif
            <div id="home-form-map" style="height: {{ $compactMode ? '260px' : '320px' }};"></div>
        </div>
    </div>

    <div class="col-12 col-md-6 d-grid">
        <label class="{{ $labelClass }}">&nbsp;</label>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="home-use-location">Usar mi ubicacion</button>
    </div>

    <div class="col-12 col-md-6">
        <label for="location_reference" class="{{ $labelClass }}">Referencia ubicacion</label>
        <input id="location_reference" name="location_reference" type="text" value="{{ old('location_reference', $client->location_reference ?? '') }}" class="{{ $inputClass }} @error('location_reference') is-invalid @enderror" maxlength="180">
        @error('location_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 mt-2"><div class="text-muted small text-uppercase fw-semibold">Ubicacion laboral</div></div>

    <div class="col-12 col-md-6">
        <label for="workplace" class="{{ $labelClass }}">Empresa o negocio</label>
        <input id="workplace" name="workplace" type="text" value="{{ old('workplace', $client->workplace ?? '') }}" class="{{ $inputClass }} @error('workplace') is-invalid @enderror" maxlength="180" placeholder="Nombre de la empresa o negocio">
        @error('workplace') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="workplace_phone" class="{{ $labelClass }}">Telefono laboral</label>
        <input id="workplace_phone" name="workplace_phone" type="text" value="{{ old('workplace_phone', $client->workplace_phone ?? '') }}" class="{{ $inputClass }} @error('workplace_phone') is-invalid @enderror" maxlength="50">
        @error('workplace_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="workplace_address" class="{{ $labelClass }}">Direccion del trabajo</label>
        <input id="workplace_address" name="workplace_address" type="text" value="{{ old('workplace_address', $client->workplace_address ?? '') }}" class="{{ $inputClass }} @error('workplace_address') is-invalid @enderror" placeholder="Escribe la direccion del trabajo" autocomplete="off">
        @error('workplace_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Busca la direccion del trabajo y marca su punto en el mapa.</div>
    </div>

    <div class="col-12">
        <div class="border rounded-3 overflow-hidden bg-white">
            @if ($googleMapsApiKey === '')
                <div class="alert alert-warning rounded-0 m-0">
                    El mapa no esta disponible ahora mismo. Aun puedes escribir la direccion laboral manualmente.
                </div>
            @endif
            <div id="work-form-map" style="height: {{ $compactMode ? '260px' : '320px' }};"></div>
        </div>
    </div>

    <div class="col-12 col-md-6 d-grid">
        <label class="{{ $labelClass }}">&nbsp;</label>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="work-use-location">Usar mi ubicacion actual</button>
    </div>

    <div class="col-12 col-md-6">
        <label for="workplace_location_reference" class="{{ $labelClass }}">Referencia laboral</label>
        <input id="workplace_location_reference" name="workplace_location_reference" type="text" value="{{ old('workplace_location_reference', $client->workplace_location_reference ?? '') }}" class="{{ $inputClass }} @error('workplace_location_reference') is-invalid @enderror" maxlength="180">
        @error('workplace_location_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <input id="latitude" name="latitude" type="hidden" value="{{ old('latitude', $client->latitude ?? '') }}">
    <input id="longitude" name="longitude" type="hidden" value="{{ old('longitude', $client->longitude ?? '') }}">
    <input id="workplace_latitude" name="workplace_latitude" type="hidden" value="{{ old('workplace_latitude', $client->workplace_latitude ?? '') }}">
    <input id="workplace_longitude" name="workplace_longitude" type="hidden" value="{{ old('workplace_longitude', $client->workplace_longitude ?? '') }}">
    @error('latitude') <div class="col-12"><div class="invalid-feedback d-block">{{ $message }}</div></div> @enderror
    @error('longitude') <div class="col-12"><div class="invalid-feedback d-block">{{ $message }}</div></div> @enderror
    @error('workplace_latitude') <div class="col-12"><div class="invalid-feedback d-block">{{ $message }}</div></div> @enderror
    @error('workplace_longitude') <div class="col-12"><div class="invalid-feedback d-block">{{ $message }}</div></div> @enderror

    <div class="col-12">
        <label for="notes" class="{{ $labelClass }}">Notas</label>
        <textarea id="notes" name="notes" rows="{{ $textAreaRows }}" class="{{ $inputClass }} @error('notes') is-invalid @enderror">{{ old('notes', $client->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 {{ $compactMode ? 'mt-2' : 'mt-3' }}">
    <a href="{{ isset($client) ? route('clients.show', $client) : route('clients.index') }}" class="btn btn-sm btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
    </button>
</div>

@push('scripts')
<script>
    window.clientLocationWidgets = window.clientLocationWidgets || {};

    function initClientLocationMapWidget(key, config) {
        if (typeof google === 'undefined' || !document.getElementById(config.mapId) || window.clientLocationWidgets[key]) {
            return;
        }

        const latInput = document.getElementById(config.latId);
        const lngInput = document.getElementById(config.lngId);
        const addressField = document.getElementById(config.addressId);
        const referenceField = document.getElementById(config.referenceId);
        const existingLat = parseFloat(latInput?.value || '');
        const existingLng = parseFloat(lngInput?.value || '');
        const initialCenter = (!Number.isNaN(existingLat) && !Number.isNaN(existingLng))
            ? { lat: existingLat, lng: existingLng }
            : { lat: @json($defaultMapCenter['lat']), lng: @json($defaultMapCenter['lng']) };

        const map = new google.maps.Map(document.getElementById(config.mapId), {
            center: initialCenter,
            zoom: (!Number.isNaN(existingLat) && !Number.isNaN(existingLng)) ? 15 : 11,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });

        const marker = new google.maps.Marker({
            map,
            position: initialCenter,
            draggable: true,
        });

        const geocoder = new google.maps.Geocoder();
        const autocomplete = addressField
            ? new google.maps.places.Autocomplete(addressField, { fields: ['formatted_address', 'geometry'] })
            : null;

        const setCoordinates = (lat, lng, shouldPan = true) => {
            const latitude = Number(lat);
            const longitude = Number(lng);
            if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
                return;
            }

            latInput.value = latitude.toFixed(7);
            lngInput.value = longitude.toFixed(7);
            marker.setPosition({ lat: latitude, lng: longitude });

            if (shouldPan) {
                map.panTo({ lat: latitude, lng: longitude });
            }
        };

        const fillAddress = (formatted) => {
            if (addressField && formatted) {
                addressField.value = formatted;
            }

            if (referenceField && formatted && !referenceField.value.trim()) {
                referenceField.value = formatted;
            }
        };

        const reverseGeocode = (lat, lng) => {
            geocoder.geocode({ location: { lat: Number(lat), lng: Number(lng) } }, (results, status) => {
                if (status !== 'OK' || !results || !results.length) {
                    return;
                }

                fillAddress(results[0].formatted_address || '');
            });
        };

        map.addListener('click', (event) => {
            const lat = event.latLng.lat();
            const lng = event.latLng.lng();
            setCoordinates(lat, lng);
            reverseGeocode(lat, lng);
        });

        marker.addListener('dragend', (event) => {
            const lat = event.latLng.lat();
            const lng = event.latLng.lng();
            setCoordinates(lat, lng);
            reverseGeocode(lat, lng);
        });

        if (autocomplete) {
            autocomplete.bindTo('bounds', map);
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) {
                    return;
                }

                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                setCoordinates(lat, lng);
                fillAddress(place.formatted_address || '');
                map.setZoom(16);
            });
        }

        document.getElementById(config.useLocationButtonId)?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                return;
            }

            navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                setCoordinates(lat, lng);
                reverseGeocode(lat, lng);
            });
        });

        window.clientLocationWidgets[key] = { map, marker, geocoder, autocomplete };
    }

    function initClientLocationMaps() {
        initClientLocationMapWidget('home', {
            mapId: 'home-form-map',
            latId: 'latitude',
            lngId: 'longitude',
            addressId: 'address',
            referenceId: 'location_reference',
            useLocationButtonId: 'home-use-location',
        });

        initClientLocationMapWidget('work', {
            mapId: 'work-form-map',
            latId: 'workplace_latitude',
            lngId: 'workplace_longitude',
            addressId: 'workplace_address',
            referenceId: 'workplace_location_reference',
            useLocationButtonId: 'work-use-location',
        });
    }

    window.initClientLocationMaps = initClientLocationMaps;
    document.addEventListener('DOMContentLoaded', initClientLocationMaps);
</script>
@if ($googleMapsApiKey !== '')
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}&libraries=places&v=weekly&callback=initClientLocationMaps"></script>
@endif
@endpush
