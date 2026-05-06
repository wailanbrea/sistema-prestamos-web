@extends('layouts.app')

@section('title', 'Nuevo usuario - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Nuevo usuario</h1>
        <p class="text-muted mb-0">Crea un usuario interno y asigna su rol operativo.</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @include('users._form')
            </form>
        </div>
    </section>
@endsection
