@extends('layouts.app')

@section('title', 'Contratos - '.config('app.name'))

@php
    $badgeMap = [
        'draft' => 'secondary', 'generated' => 'info', 'sent' => 'primary', 'viewed' => 'warning',
        'signed' => 'success', 'rejected' => 'danger', 'cancelled' => 'dark', 'expired' => 'secondary',
    ];
@endphp

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Contratos digitales</h1>
        <p class="text-muted mb-0">Generación, envío y firma electrónica de contratos de préstamo.</p>
    </section>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="date_from" class="form-label">Desde</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label for="date_to" class="form-label">Hasta</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Filtrar</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Contrato</th>
                            <th>Cliente</th>
                            <th>Préstamo</th>
                            <th>Estado</th>
                            <th>Creado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contracts as $contract)
                            <tr>
                                <td><strong>{{ $contract->contract_number }}</strong></td>
                                <td>{{ $contract->client?->full_name ?? 'N/D' }}</td>
                                <td>{{ $contract->loan?->loan_number ?? 'N/D' }}</td>
                                <td><span class="badge bg-{{ $badgeMap[$contract->status] ?? 'secondary' }}">{{ $statuses[$contract->status] ?? $contract->status }}</span></td>
                                <td>{{ $contract->created_at?->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('contracts.show', $contract->uuid) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No hay contratos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="mt-3">{{ $contracts->links() }}</div>
@endsection
