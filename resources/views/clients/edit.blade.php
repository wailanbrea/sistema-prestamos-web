@extends('layouts.app')

@section('title', 'Editar cliente - '.config('app.name'))

@section('content')
    <section class="mb-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <div class="text-muted small text-uppercase">Editar cliente</div>
                <h1 class="h4 fw-bold mb-1">{{ $client->full_name }}</h1>
                <p class="text-muted small mb-0">{{ $client->code ?: 'Sin codigo' }}</p>
            </div>
            <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-secondary">Ver expediente</a>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body p-3">
            <form method="POST" action="{{ route('clients.update', $client) }}" novalidate>
                @include('clients._form', ['method' => 'PUT', 'compact' => true])
            </form>
        </div>
    </section>
@endsection
