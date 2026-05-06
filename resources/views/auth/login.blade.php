@extends('layouts.guest')

@section('title', 'Iniciar sesión - '.config('app.name'))

@section('content')
    <section class="card auth-card">
        <div class="card-body p-4 p-sm-5">
            <div class="mb-4">
                <span class="brand-mark mb-3"><i class="fa-solid fa-hand-holding-dollar"></i></span>
                <h1 class="h4 fw-bold mb-1">Iniciar sesión</h1>
                <p class="text-muted mb-0">Accede al panel financiero de tu empresa.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        autocomplete="email"
                        autofocus
                        required
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                    <label class="form-check-label" for="remember">Mantener sesión iniciada</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-lock me-2"></i> Entrar
                </button>
            </form>
        </div>
    </section>
@endsection
