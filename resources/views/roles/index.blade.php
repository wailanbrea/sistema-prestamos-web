@extends('layouts.app')

@section('title', 'Roles y permisos - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Roles y permisos</h1>
                <p class="text-muted mb-0">Define que pantallas y acciones puede usar cada rol.</p>
            </div>
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Permisos</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td class="fw-semibold">{{ $role->name }}</td>
                                <td>{{ $role->permissions_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Configurar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
