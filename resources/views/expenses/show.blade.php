@extends('layouts.app')

@section('title', 'Gasto #'.$expense->id.' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Gasto #{{ $expense->id }}</h1>
                <p class="text-muted mb-0">{{ $expense->expense_date->format('d/m/Y') }}</p>
            </div>
            <a href="{{ route('expenses.create') }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-plus me-2"></i> Otro gasto
            </a>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $expense->description }}</dd>
                <dt class="col-sm-3">Categoría</dt>
                <dd class="col-sm-9">{{ $expense->category?->name ?: 'Sin categoría' }}</dd>
                <dt class="col-sm-3">Método</dt>
                <dd class="col-sm-9">@include('expenses.partials.method-label', ['method' => $expense->payment_method])</dd>
                <dt class="col-sm-3">Monto</dt>
                <dd class="col-sm-9 fs-4 fw-bold">RD$ {{ number_format((float) $expense->amount, 2) }}</dd>
            </dl>
        </div>
    </section>
@endsection
