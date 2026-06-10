@extends('layouts.app')

@section('title', 'Nuevo cliente - '.config('app.name'))

@section('content')
    <section class="mb-3">
        <div class="text-muted small text-uppercase">Clientes</div>
        <h1 class="h4 fw-bold mb-1">Nuevo cliente</h1>
        <p class="text-muted small mb-0">Registra la informacion principal del cliente.</p>
    </section>

    <section class="card content-card">
        <div class="card-body p-3 p-lg-4">
            <form method="POST" action="{{ route('clients.store') }}" novalidate>
                @include('clients._form')
            </form>
        </div>
    </section>
@endsection
