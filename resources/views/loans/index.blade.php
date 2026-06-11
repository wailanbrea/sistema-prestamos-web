@extends('layouts.app')

@include('loans.partials.labels')

@push('styles')
<style>
    .loans-table { border-collapse: separate; border-spacing: 0; }
    .loans-table td { transition: background .12s ease, border-color .12s ease, padding-left .12s ease; }
    .loans-table tbody tr { cursor: pointer; }
    .loans-table tbody tr:hover td { background: rgba(0,38,83,.04); }
    .loans-table tbody tr:hover td:first-child { border-left-color: var(--app-primary); padding-left: 13px; }
    .loans-table tbody td:first-child { border-left: 3px solid transparent; }
</style>
@endpush

@section('title', 'Préstamos - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Préstamos</h1>
                <p class="text-muted mb-0">Control de préstamos activos, saldados, atrasados y balance pendiente.</p>
            </div>
            @can('loans.create')
                <a href="{{ route('loans.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-2"></i> Nuevo préstamo
                </a>
            @endcan
        </div>
    </section>

    @if ($errors->has('loan'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('loan') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('loans.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="client_id" class="form-label">Cliente</label>
                    <select id="client_id" name="client_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) ($filters['client_id'] ?? '') === (string) $client->id)>{{ $client->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($loanStatusLabels as $value => $data)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $data['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-secondary"><i class="fa-solid fa-filter"></i></button>
                </div>
            </form>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0 loans-table">
                    <thead>
                        <tr>
                            <th>Préstamo</th>
                            <th>Cliente</th>
                            <th>Frecuencia</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">Balance</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loans as $loan)
                            @php($loanCurrency = $loan->currency ?? currency())
                            <tr onclick="location.href='{{ route('loans.show', $loan) }}'"  >
                                <td>
                                    <a href="{{ route('loans.show', $loan) }}" class="fw-semibold text-decoration-none">{{ $loan->loan_number }}</a>
                                    <div class="text-muted small">{{ $loan->start_date->format('d/m/Y') }}</div>
                                    <div class="small text-success">Ganancia esperada: {{ $loanCurrency }} {{ number_format((float) $loan->total_interest, 2) }}</div>
                                </td>
                                <td>{{ $loan->client->full_name }}</td>
                                <td>{{ $frequencyLabels[$loan->payment_frequency] ?? $loan->payment_frequency }}</td>
                                <td class="text-end">{{ $loanCurrency }} {{ number_format((float) $loan->principal_amount, 2) }}</td>
                                <td class="text-end">{{ $loanCurrency }} {{ number_format((float) $loan->remaining_balance, 2) }}</td>
                                <td><span class="badge {{ $loanStatusLabels[$loan->status]['class'] ?? 'text-bg-secondary' }}">{{ $loanStatusLabels[$loan->status]['label'] ?? $loan->status }}</span></td>
                                <td class="text-end text-nowrap" onclick="event.stopPropagation()">
                                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-link text-decoration-none" title="Ver"><i class="fa-solid fa-eye"></i></a>
                                    @can('loans.update')
                                        <a href="{{ route('loans.edit', $loan) }}" class="btn btn-sm btn-link text-dark text-decoration-none" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('loans.delete')
                                        <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este préstamo? Solo es posible si no tiene pagos registrados.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">No hay préstamos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $loans->links() }}</div>
        </div>
    </section>
@endsection
