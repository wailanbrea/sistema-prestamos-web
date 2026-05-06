@extends('layouts.app')

@section('title', 'Editar cliente - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Editar cliente</h1>
        <p class="text-muted mb-0">{{ $client->full_name }}</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('clients.update', $client) }}" novalidate>
                @include('clients._form', ['method' => 'PUT'])
            </form>
        </div>
    </section>
@endsection
