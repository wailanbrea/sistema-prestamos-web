<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .registration-shell { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .registration-card { width: min(920px, 100%); border: 0; border-radius: 18px; box-shadow: 0 20px 50px rgba(17, 24, 39, .12); }
        .map-shell { border: 1px solid #dee2e6; border-radius: 14px; overflow: hidden; background: #fff; }
    </style>
</head>
<body>
    <div class="registration-shell">
        <div class="card registration-card">
            <div class="card-body p-4 p-lg-5">
                <div class="mb-4">
                    <h1 class="h3 fw-bold mb-1">Completa tu registro</h1>
                    <p class="text-muted mb-0">Este enlace es de uso unico. Completa tus datos para quedar registrado como cliente.</p>
                </div>

                <form id="client-registration-form" method="POST" action="{{ route('client-registration.submit', $link->token) }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="code" class="form-label">Codigo</label>
                            <input id="code" name="code" type="text" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" maxlength="50">
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="full_name" class="form-label">Nombre completo</label>
                            <input id="full_name" name="full_name" type="text" value="{{ old('full_name', $link->recipient_name) }}" class="form-control @error('full_name') is-invalid @enderror" maxlength="180" required>
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="identification" class="form-label">Cedula / identificacion</label>
                            <input id="identification" name="identification" type="text" value="{{ old('identification') }}" class="form-control @error('identification') is-invalid @enderror" maxlength="50">
                            @error('identification') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="phone" class="form-label">Telefono</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone', $link->recipient_phone) }}" class="form-control @error('phone') is-invalid @enderror" maxlength="50">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="secondary_phone" class="form-label">Telefono secundario</label>
                            <input id="secondary_phone" name="secondary_phone" type="text" value="{{ old('secondary_phone') }}" class="form-control @error('secondary_phone') is-invalid @enderror" maxlength="50">
                            @error('secondary_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" maxlength="150">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="monthly_income" class="form-label">Ingreso mensual</label>
                            <input id="monthly_income" name="monthly_income" type="number" step="0.01" min="0" value="{{ old('monthly_income', '0') }}" class="form-control @error('monthly_income') is-invalid @enderror">
                            @error('monthly_income') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="workplace" class="form-label">Lugar de trabajo</label>
                            <input id="workplace" name="workplace" type="text" value="{{ old('workplace') }}" class="form-control @error('workplace') is-invalid @enderror" maxlength="180">
                            @error('workplace') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="workplace_phone" class="form-label">Telefono laboral</label>
                            <input id="workplace_phone" name="workplace_phone" type="text" value="{{ old('workplace_phone') }}" class="form-control @error('workplace_phone') is-invalid @enderror" maxlength="50">
                            @error('workplace_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Direccion</label>
                            <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror" required>{{ old('address') }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="map_search" class="form-label">Buscar ubicacion en el mapa</label>
                            <input id="map_search" type="text" class="form-control" placeholder="Escribe una direccion, sector o punto de referencia" autocomplete="off">
                            <div class="form-text">Puedes usar tu ubicacion actual, buscar una direccion o tocar el mapa para elegir el punto.</div>
                        </div>
                        <div class="col-12">
                            <div class="map-shell">
                                @if (($googleMapsApiKey ?? '') === '')
                                    <div class="alert alert-warning m-3 mb-0">
                                        El mapa no esta disponible ahora mismo. Aun puedes usar "Usar mi ubicacion" para capturar coordenadas.
                                    </div>
                                @endif
                                <div id="registration-map" style="height: 360px;"></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="latitude" class="form-label">Latitud</label>
                            <input id="latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" value="{{ old('latitude') }}" class="form-control @error('latitude') is-invalid @enderror">
                            @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="longitude" class="form-label">Longitud</label>
                            <input id="longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" value="{{ old('longitude') }}" class="form-control @error('longitude') is-invalid @enderror">
                            @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4 d-grid">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-secondary" id="useLocation">Usar mi ubicacion</button>
                        </div>
                        <div class="col-12">
                            <label for="location_reference" class="form-label">Referencia de ubicacion</label>
                            <input id="location_reference" name="location_reference" type="text" value="{{ old('location_reference') }}" class="form-control @error('location_reference') is-invalid @enderror" maxlength="180">
                            @error('location_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Notas</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h2 class="h6 fw-bold mb-1">Fotos del ID</h2>
                                    <p class="text-muted small mb-3">Debes tomar o subir la foto frontal y la foto trasera de tu identificacion. Se aceptan JPG, PNG o WEBP hasta 5 MB por imagen.</p>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label for="id_front" class="form-label">Foto frontal del ID</label>
                                            <input id="id_front" name="id_front" type="file" accept="image/*" capture="environment" class="form-control @error('id_front') is-invalid @enderror" required>
                                            @error('id_front') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="id_back" class="form-label">Foto trasera del ID</label>
                                            <input id="id_back" name="id_back" type="file" accept="image/*" capture="environment" class="form-control @error('id_back') is-invalid @enderror" required>
                                            @error('id_back') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button id="client-registration-submit" type="submit" class="btn btn-primary btn-lg">
                            Enviar registro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.clientRegistrationMap = {
            map: null,
            marker: null,
            geocoder: null,
            autocomplete: null,
        };

        function setCoordinates(lat, lng, options = {}) {
            const shouldPan = options.shouldPan !== false;
            const latitude = Number(lat);
            const longitude = Number(lng);

            if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
                return;
            }

            document.getElementById('latitude').value = latitude.toFixed(7);
            document.getElementById('longitude').value = longitude.toFixed(7);

            if (window.clientRegistrationMap.marker) {
                window.clientRegistrationMap.marker.setPosition({ lat: latitude, lng: longitude });
            }

            if (shouldPan && window.clientRegistrationMap.map) {
                window.clientRegistrationMap.map.panTo({ lat: latitude, lng: longitude });
            }
        }

        function fillAddress(value) {
            const addressField = document.getElementById('address');
            if (addressField) {
                addressField.value = value || '';
            }
        }

        function fillLocationReference(value) {
            const referenceField = document.getElementById('location_reference');
            if (referenceField && (!referenceField.value || referenceField.value.trim() === '')) {
                referenceField.value = value || '';
            }
        }

        function reverseGeocode(lat, lng) {
            const geocoder = window.clientRegistrationMap.geocoder;
            if (!geocoder) {
                return;
            }

            geocoder.geocode({ location: { lat: Number(lat), lng: Number(lng) } }, (results, status) => {
                if (status !== 'OK' || !results || !results.length) {
                    return;
                }

                const formatted = results[0].formatted_address || '';
                fillAddress(formatted);
                fillLocationReference(formatted);
            });
        }

        function useCurrentLocation() {
            if (!navigator.geolocation) {
                return;
            }

            navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                setCoordinates(lat, lng);
                reverseGeocode(lat, lng);
            });
        }

        document.getElementById('useLocation')?.addEventListener('click', useCurrentLocation);

        function syncManualCoordinates() {
            const lat = parseFloat(document.getElementById('latitude').value);
            const lng = parseFloat(document.getElementById('longitude').value);

            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }

            setCoordinates(lat, lng);
            reverseGeocode(lat, lng);
        }

        document.getElementById('latitude')?.addEventListener('change', syncManualCoordinates);
        document.getElementById('longitude')?.addEventListener('change', syncManualCoordinates);

        document.getElementById('client-registration-form')?.addEventListener('submit', () => {
            const submitButton = document.getElementById('client-registration-submit');

            if (!submitButton || submitButton.disabled) {
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Enviando...';
        });

        function initClientRegistrationMap() {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const existingLat = parseFloat(latInput.value);
            const existingLng = parseFloat(lngInput.value);
            const initialCenter = (!Number.isNaN(existingLat) && !Number.isNaN(existingLng))
                ? { lat: existingLat, lng: existingLng }
                : { lat: @json(($defaultMapCenter['lat'] ?? 18.4861)), lng: @json(($defaultMapCenter['lng'] ?? -69.9312)) };

            const map = new google.maps.Map(document.getElementById('registration-map'), {
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
            const searchInput = document.getElementById('map_search');
            const autocomplete = searchInput
                ? new google.maps.places.Autocomplete(searchInput, {
                    fields: ['formatted_address', 'geometry'],
                })
                : null;

            window.clientRegistrationMap.map = map;
            window.clientRegistrationMap.marker = marker;
            window.clientRegistrationMap.geocoder = geocoder;
            window.clientRegistrationMap.autocomplete = autocomplete;

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
                    fillLocationReference(place.formatted_address || '');
                    map.setZoom(16);
                });
            }
        }
    </script>
    @if (($googleMapsApiKey ?? '') !== '')
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}&libraries=places&v=weekly&callback=initClientRegistrationMap"></script>
    @endif
</body>
</html>
