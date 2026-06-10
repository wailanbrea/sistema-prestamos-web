@extends('layouts.app')

@section('title', 'Editar acreedor - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Editar acreedor</h1>
                <p class="text-muted mb-0">{{ $creditor->name }}</p>
            </div>
            <a href="{{ route('creditors.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('creditors.update', $creditor) }}">
                @include('creditors._form', ['method' => 'PUT'])
            </form>
        </div>
    </section>
@endsection
