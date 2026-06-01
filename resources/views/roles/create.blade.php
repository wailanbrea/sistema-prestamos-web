@extends('layouts.app')

@section('title', 'Nuevo rol - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Nuevo rol</h1>
        <p class="text-muted mb-0">Crea un rol personalizado para tu empresa y elige sus permisos.</p>
    </section>

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf

        <section class="card content-card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label">Nombre del rol</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" maxlength="100" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </section>

        @include('roles._form', ['disabledAll' => false])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i> Crear rol</button>
        </div>
    </form>
@endsection
