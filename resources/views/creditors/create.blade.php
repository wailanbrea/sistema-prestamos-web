@extends('layouts.app')

@section('title', 'Nuevo acreedor - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Nuevo acreedor</h1>
                <p class="text-muted mb-0">Registra la ficha base del acreedor antes de crear cuentas por pagar.</p>
            </div>
            <a href="{{ route('creditors.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('creditors.store') }}">
                @include('creditors._form')
            </form>
        </div>
    </section>
@endsection
