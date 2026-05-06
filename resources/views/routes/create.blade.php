@extends('layouts.app')

@section('title', 'Nueva ruta - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Nueva ruta</h1>
        <p class="text-muted mb-0">Asigna zona, cobrador y clientes de la ruta.</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('routes.store') }}">
                @include('routes._form')
            </form>
        </div>
    </section>
@endsection
