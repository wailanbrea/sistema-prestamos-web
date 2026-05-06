@extends('layouts.app')

@section('title', 'Editar ruta - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Editar ruta</h1>
        <p class="text-muted mb-0">{{ $routeModel->name }}</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('routes.update', $routeModel) }}">
                @include('routes._form', ['method' => 'PUT'])
            </form>
        </div>
    </section>
@endsection
