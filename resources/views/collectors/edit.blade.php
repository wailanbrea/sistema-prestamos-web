@extends('layouts.app')

@section('title', 'Editar cobrador - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Editar cobrador</h1>
        <p class="text-muted mb-0">{{ $collector->name }}</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('collectors.update', $collector) }}">
                @include('collectors._form', ['method' => 'PUT'])
            </form>
        </div>
    </section>
@endsection
