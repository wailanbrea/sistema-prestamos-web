@extends('layouts.app')

@section('title', $collector->name.' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $collector->name }}</h1>
                <div class="d-flex flex-wrap gap-2">
                    @include('collectors.partials.status-badge', ['status' => $collector->status])
                    @include('collectors.partials.commission-badge', ['type' => $collector->commission_type])
                </div>
            </div>
            <a href="{{ route('collectors.edit', $collector) }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-pen me-2"></i> Editar
            </a>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Información</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Teléfono</dt>
                        <dd class="col-sm-7">{{ $collector->phone ?: 'Sin teléfono' }}</dd>
                        <dt class="col-sm-5">Usuario vinculado</dt>
                        <dd class="col-sm-7">
                            @if ($collector->user)
                                {{ $collector->user->name }}<br>
                                <span class="text-muted small">{{ $collector->user->email }}</span>
                            @else
                                Sin usuario vinculado
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Comisión</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Tipo</dt>
                        <dd class="col-sm-7">
                            @include('collectors.partials.commission-badge', ['type' => $collector->commission_type])
                        </dd>
                        <dt class="col-sm-5">Valor</dt>
                        <dd class="col-sm-7">
                            @if ($collector->commission_type === 'percentage')
                                {{ number_format((float) $collector->commission_value, 2) }}%
                            @elseif ($collector->commission_type === 'fixed')
                                {{ currency() }} {{ number_format((float) $collector->commission_value, 2) }}
                            @else
                                No aplica
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </section>
@endsection
