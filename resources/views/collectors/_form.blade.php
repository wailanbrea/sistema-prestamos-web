@csrf

@if ($method ?? null)
    @method($method)
@endif

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Nombre del cobrador</label>
        <input id="name" name="name" type="text" value="{{ old('name', $collector->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" maxlength="150" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="phone" class="form-label">Teléfono</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $collector->phone ?? '') }}" class="form-control @error('phone') is-invalid @enderror" maxlength="50">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="user_id" class="form-label">Usuario vinculado</label>
        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
            <option value="">Sin usuario vinculado</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $collector->user_id ?? '') === (string) $user->id)>
                    {{ $user->name }} · {{ $user->email }}
                </option>
            @endforeach
        </select>
        @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

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
        <label for="commission_type" class="form-label">Tipo de comisión</label>
        <select id="commission_type" name="commission_type" class="form-select @error('commission_type') is-invalid @enderror" required>
            @foreach (['none' => 'Sin comisión', 'percentage' => 'Porcentaje del cobro', 'fixed' => 'Monto fijo por cobro'] as $value => $label)
                <option value="{{ $value }}" @selected(old('commission_type', $collector->commission_type ?? 'none') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('commission_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="commission_value" class="form-label">Valor de comisión</label>
        <input id="commission_value" name="commission_value" type="number" step="0.01" min="0" max="1000000" value="{{ old('commission_value', $collector->commission_value ?? '0') }}" class="form-control @error('commission_value') is-invalid @enderror">
        @error('commission_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('collectors.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-2"></i> Guardar cobrador
    </button>
</div>
