@extends('layouts.app')

@section('title', 'Roles y permisos - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Roles y permisos</h1>
                <p class="text-muted mb-0">Define qué pantallas y acciones puede usar cada rol.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('roles.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i> Nuevo rol</a>
                <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </section>

    @if ($errors->has('role'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('role') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <section class="card content-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Tipo</th>
                            <th class="text-center">Usuarios</th>
                            <th class="text-center">Permisos</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td class="fw-semibold">{{ $role->name }}</td>
                                <td>
                                    @if ($role->is_system)
                                        <span class="badge text-bg-secondary">Sistema</span>
                                    @else
                                        <span class="badge text-bg-info">Personalizado</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $role->users_count }}</td>
                                <td class="text-center">{{ $role->permissions_count }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" title="Configurar"><i class="fa-solid fa-sliders"></i></a>
                                    <form action="{{ route('roles.duplicate', $role) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Duplicar"><i class="fa-solid fa-copy"></i></button>
                                    </form>
                                    @if (! $role->is_system && ! $role->is_protected)
                                        <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este rol?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
