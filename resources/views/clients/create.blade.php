@extends('layouts.app')

@section('title', 'Nuevo cliente - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Nuevo cliente</h1>
        <p class="text-muted mb-0">Registra la información principal del cliente.</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('clients.store') }}" novalidate>
                @include('clients._form')
            </form>
        </div>
    </section>
@endsection
