@csrf

@if ($method ?? null)
    @method($method)
@endif

@php
    $currentRole = old('role', isset($managedUser) ? $managedUser->roles->first()?->name : '');
@endphp

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name', $managedUser->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $managedUser->email ?? '') }}" class="form-control @error('email') is-invalid @enderror" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="phone" class="form-label">Teléfono</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $managedUser->phone ?? '') }}" class="form-control @error('phone') is-invalid @enderror" maxlength="50">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="role" class="form-label">Rol</label>
        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
            <option value="">Seleccione un rol</option>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected($currentRole === $role)>{{ $role }}</option>
            @endforeach
        </select>
        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="status" class="form-label">Estado</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach (['active' => 'Activo', 'blocked' => 'Bloqueado'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $managedUser->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="password" class="form-label">Contraseña</label>
        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" @if(! isset($managedUser)) required @endif>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" @if(! isset($managedUser)) required @endif>
    </div>

    <div class="col-12 mt-2">
        @php $visibleMenus = old('visible_menus', isset($managedUser) ? $managedUser->visible_menus : null); @endphp
        <label class="form-label mb-1">Menús visibles</label>
        <div class="text-muted small mb-2">
            Desmarca los menús que este usuario <strong>no</strong> debe ver ni abrir (se ocultan y se bloquea su acceso, incluso para administradores).
            La gestión de Configuración, Usuarios y Roles se mantiene accesible si el usuario tiene ese permiso.
        </div>
        <div class="d-flex gap-2 mb-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="menuSelectAll">Marcar todos</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="menuSelectNone">Desmarcar todos</button>
        </div>
        <div class="row g-2">
            @foreach (config('navigation.sections') as $section)
                <div class="col-12 col-md-4">
                    <div class="border rounded-3 p-2 h-100">
                        <div class="fw-semibold small text-uppercase text-muted mb-1">{{ $section['label'] }}</div>
                        @foreach ($section['items'] as $item)
                            <div class="form-check">
                                <input class="form-check-input menu-check" type="checkbox" name="visible_menus[]" value="{{ $item['route'] }}" id="menu_{{ $item['route'] }}"
                                    @checked($visibleMenus === null || in_array($item['route'], (array) $visibleMenus, true))>
                                <label class="form-check-label" for="menu_{{ $item['route'] }}">{{ $item['label'] }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.getElementById('menuSelectAll')?.addEventListener('click', () => document.querySelectorAll('.menu-check').forEach((c) => c.checked = true));
    document.getElementById('menuSelectNone')?.addEventListener('click', () => document.querySelectorAll('.menu-check').forEach((c) => c.checked = false));
</script>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-2"></i> Guardar usuario
    </button>
</div>
