@extends('layouts.app')

@section('title', 'Configurar rol - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Rol {{ $role->name }}
                    @if ($isSystem)<span class="badge text-bg-secondary align-middle ms-1">Sistema</span>@endif
                </h1>
                <p class="text-muted mb-0">Marca las pantallas y acciones permitidas para este rol.</p>
            </div>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </section>

    @if ($isProtected)
        <div class="alert alert-info">
            <i class="fa-solid fa-shield-halved me-2"></i>El rol <strong>Administrador</strong> siempre conserva todos los permisos; no se pueden modificar.
        </div>
    @endif

    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')

        @include('roles._form', ['disabledAll' => $isProtected])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            @unless ($isProtected)
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Guardar permisos
                </button>
            @endunless
        </div>
    </form>
@endsection
