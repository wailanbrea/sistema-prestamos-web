@csrf

@if ($method ?? null)
    @method($method)
@endif

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name', $creditor->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" maxlength="180" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="document" class="form-label">Documento</label>
        <input id="document" name="document" type="text" value="{{ old('document', $creditor->document ?? '') }}" class="form-control @error('document') is-invalid @enderror" maxlength="50">
        @error('document') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="phone" class="form-label">Telefono</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $creditor->phone ?? '') }}" class="form-control @error('phone') is-invalid @enderror" maxlength="50">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="email" class="form-label">Correo</label>
        <input id="email" name="email" type="email" value="{{ old('email', $creditor->email ?? '') }}" class="form-control @error('email') is-invalid @enderror" maxlength="150">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="address" class="form-label">Direccion</label>
        <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $creditor->address ?? '') }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="status" class="form-label">Estado</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach (['active' => 'Activo', 'inactive' => 'Inactivo'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $creditor->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notas</label>
        <textarea id="notes" name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $creditor->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('creditors.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-2"></i> Guardar
    </button>
</div>
