@csrf

@if ($method ?? null)
    @method($method)
@endif

@php($isEdit = isset($collector) && $collector->exists)

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Nombre del cobrador</label>
        <input id="name" name="name" type="text" value="{{ old('name', $collector->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" maxlength="150" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="phone" class="form-label">Telefono</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $collector->phone ?? '') }}" class="form-control @error('phone') is-invalid @enderror" maxlength="50">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if (! $isEdit)
        <div class="col-12">
            <div class="card border-light-subtle">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Acceso del cobrador</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="access_mode" class="form-label">Como se vinculara el acceso</label>
                            <select id="access_mode" name="access_mode" class="form-select @error('access_mode') is-invalid @enderror">
                                <option value="new" @selected(old('access_mode', 'new') === 'new')>Crear usuario nuevo</option>
                                <option value="existing" @selected(old('access_mode') === 'existing')>Vincular usuario existente</option>
                                <option value="none" @selected(old('access_mode') === 'none')>Sin acceso por ahora</option>
                            </select>
                            @error('access_mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 row g-3 m-0 p-0" data-access-section="new">
                            <div class="col-12 col-md-6">
                                <label for="user_name" class="form-label">Nombre del usuario</label>
                                <input id="user_name" name="user_name" type="text" value="{{ old('user_name', old('name', $collector->name ?? '')) }}" class="form-control @error('user_name') is-invalid @enderror" maxlength="150">
                                @error('user_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="user_email" class="form-label">Correo del usuario</label>
                                <input id="user_email" name="user_email" type="email" value="{{ old('user_email') }}" class="form-control @error('user_email') is-invalid @enderror" maxlength="150">
                                @error('user_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="user_password" class="form-label">Contrasena temporal</label>
                                <input id="user_password" name="user_password" type="text" value="{{ old('user_password') }}" class="form-control @error('user_password') is-invalid @enderror" maxlength="150">
                                @error('user_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-text">Se creara un usuario con rol `Cobrador` dentro de esta misma empresa.</div>
                            </div>
                        </div>

                        <div class="col-12" data-access-section="existing">
                            <label for="user_id" class="form-label">Usuario vinculado</label>
                            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">Seleccione un usuario</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) old('user_id', $collector->user_id ?? '') === (string) $user->id)>
                                        {{ $user->name }} - {{ $user->email }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="col-12 col-md-6">
            <label for="user_id" class="form-label">Usuario vinculado</label>
            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                <option value="">Sin usuario vinculado</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('user_id', $collector->user_id ?? '') === (string) $user->id)>
                        {{ $user->name }} - {{ $user->email }}
                    </option>
                @endforeach
            </select>
            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-12 col-md-6">
        <label for="status" class="form-label">Estado</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach (['active' => 'Activo', 'inactive' => 'Inactivo'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $collector->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="commission_type" class="form-label">Tipo de comision</label>
        <select id="commission_type" name="commission_type" class="form-select @error('commission_type') is-invalid @enderror" required>
            @foreach (['none' => 'Sin comision', 'percentage' => 'Porcentaje del cobro', 'fixed' => 'Monto fijo por cobro'] as $value => $label)
                <option value="{{ $value }}" @selected(old('commission_type', $collector->commission_type ?? 'none') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('commission_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="commission_value" class="form-label">Valor de comision</label>
        <input id="commission_value" name="commission_value" type="number" step="0.01" min="0" max="1000000" value="{{ old('commission_value', $collector->commission_value ?? '0') }}" class="form-control @error('commission_value') is-invalid @enderror">
        @error('commission_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="commission_base" class="form-label">Regla de comision</label>
        <select id="commission_base" name="commission_base" class="form-select @error('commission_base') is-invalid @enderror" required>
            @foreach (['payment_total' => 'Sobre el total cobrado', 'principal_only' => 'Solo sobre capital cobrado'] as $value => $label)
                <option value="{{ $value }}" @selected(old('commission_base', $collector->commission_base ?? 'payment_total') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('commission_base') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('collectors.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-2"></i> Guardar cobrador
    </button>
</div>

@if (! $isEdit)
    <script>
        (() => {
            const modeSelect = document.getElementById('access_mode');
            if (!modeSelect) {
                return;
            }

            const sections = Array.from(document.querySelectorAll('[data-access-section]'));

            const syncSections = () => {
                const mode = modeSelect.value;
                sections.forEach((section) => {
                    section.style.display = section.getAttribute('data-access-section') === mode ? '' : 'none';
                });
            };

            modeSelect.addEventListener('change', syncSections);
            syncSections();
        })();
    </script>
@endif
