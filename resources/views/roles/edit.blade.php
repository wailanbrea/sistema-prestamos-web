@extends('layouts.app')

@section('title', 'Configurar rol - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Rol {{ $role->name }}</h1>
        <p class="text-muted mb-0">Marca las pantallas y acciones permitidas para este rol.</p>
    </section>

    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')

        <section class="row g-3">
            @foreach ($permissionGroups as $group => $permissions)
                <div class="col-12 col-xl-6">
                    <article class="card content-card h-100">
                        <div class="card-header bg-white border-0 pb-0">
                            <h2 class="h6 fw-bold mb-0">{{ $group }}</h2>
                        </div>
                        <div class="card-body">
                            <div class="vstack gap-2">
                                @foreach ($permissions as $permission)
                                    <label class="border rounded-3 p-3 d-flex gap-3 align-items-start">
                                        <input class="form-check-input mt-1" type="checkbox" name="permissions[]" value="{{ $permission['name'] }}" @checked(in_array($permission['name'], old('permissions', $selectedPermissions), true))>
                                        <span>
                                            <span class="fw-semibold d-block">{{ $permission['label'] }}</span>
                                            <span class="text-muted small">{{ $permission['screen'] }} · {{ $permission['name'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </section>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-2"></i> Guardar permisos
            </button>
        </div>
    </form>
@endsection
