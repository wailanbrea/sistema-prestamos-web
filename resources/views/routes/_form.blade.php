@csrf

@if ($method ?? null)
    @method($method)
@endif

@php
    $selectedClients = collect(old('client_ids', isset($routeModel) ? $routeModel->clients->pluck('id')->all() : []))->map(fn ($id) => (string) $id)->all();
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
        <label for="description" class="form-label">Descripción</label>
        <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $routeModel->description ?? '') }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="client_ids" class="form-label">Clientes de la ruta</label>
        <select id="client_ids" name="client_ids[]" class="form-select @error('client_ids') is-invalid @enderror @error('client_ids.*') is-invalid @enderror" multiple size="10">
            @foreach ($clients as $client)
                <option value="{{ $client->id }}" @selected(in_array((string) $client->id, $selectedClients, true))>
                    {{ $client->full_name }}{{ $client->phone ? ' · '.$client->phone : '' }}
                </option>
            @endforeach
        </select>
        <div class="form-text">El orden seleccionado se guarda como orden de visita inicial.</div>
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
