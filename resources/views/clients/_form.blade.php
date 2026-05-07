@csrf

@if ($method ?? null)
    @method($method)
@endif

<div class="row g-3">
    <div class="col-12 col-md-4">
        <label for="code" class="form-label">Código</label>
        <input id="code" name="code" type="text" value="{{ old('code', $client->code ?? '') }}" class="form-control @error('code') is-invalid @enderror" maxlength="50">
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-8">
        <label for="full_name" class="form-label">Nombre completo</label>
        <input id="full_name" name="full_name" type="text" value="{{ old('full_name', $client->full_name ?? '') }}" class="form-control @error('full_name') is-invalid @enderror" maxlength="180" required>
        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="identification" class="form-label">Cédula / identificación</label>
        <input id="identification" name="identification" type="text" value="{{ old('identification', $client->identification ?? '') }}" class="form-control @error('identification') is-invalid @enderror" maxlength="50">
        @error('identification') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="phone" class="form-label">Teléfono</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $client->phone ?? '') }}" class="form-control @error('phone') is-invalid @enderror" maxlength="50">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="secondary_phone" class="form-label">Teléfono secundario</label>
        <input id="secondary_phone" name="secondary_phone" type="text" value="{{ old('secondary_phone', $client->secondary_phone ?? '') }}" class="form-control @error('secondary_phone') is-invalid @enderror" maxlength="50">
        @error('secondary_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $client->email ?? '') }}" class="form-control @error('email') is-invalid @enderror" maxlength="150">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="monthly_income" class="form-label">Ingreso mensual</label>
        <input id="monthly_income" name="monthly_income" type="number" step="0.01" min="0" value="{{ old('monthly_income', $client->monthly_income ?? '0') }}" class="form-control @error('monthly_income') is-invalid @enderror">
        @error('monthly_income') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="workplace" class="form-label">Lugar de trabajo</label>
        <input id="workplace" name="workplace" type="text" value="{{ old('workplace', $client->workplace ?? '') }}" class="form-control @error('workplace') is-invalid @enderror" maxlength="180">
        @error('workplace') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="workplace_phone" class="form-label">Teléfono laboral</label>
        <input id="workplace_phone" name="workplace_phone" type="text" value="{{ old('workplace_phone', $client->workplace_phone ?? '') }}" class="form-control @error('workplace_phone') is-invalid @enderror" maxlength="50">
        @error('workplace_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="status" class="form-label">Estado</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'moroso' => 'Moroso', 'blocked' => 'Bloqueado'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $client->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="risk_level" class="form-label">Nivel de riesgo</label>
        <select id="risk_level" name="risk_level" class="form-select @error('risk_level') is-invalid @enderror" required>
            @foreach (['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto', 'critical' => 'Crítico'] as $value => $label)
                <option value="{{ $value }}" @selected(old('risk_level', $client->risk_level ?? 'low') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('risk_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="address" class="form-label">Dirección</label>
        <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $client->address ?? '') }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="latitude" class="form-label">Latitud</label>
        <input id="latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" value="{{ old('latitude', $client->latitude ?? '') }}" class="form-control @error('latitude') is-invalid @enderror">
        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="longitude" class="form-label">Longitud</label>
        <input id="longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" value="{{ old('longitude', $client->longitude ?? '') }}" class="form-control @error('longitude') is-invalid @enderror">
        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="location_reference" class="form-label">Referencia ubicación</label>
        <input id="location_reference" name="location_reference" type="text" value="{{ old('location_reference', $client->location_reference ?? '') }}" class="form-control @error('location_reference') is-invalid @enderror" maxlength="180">
        @error('location_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notas</label>
        <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $client->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-2"></i> Guardar cliente
    </button>
</div>
