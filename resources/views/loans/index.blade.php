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
    .due-summary { min-width: 150px; }
    .due-summary .amount { font-weight: 700; }
</style>
@endpush

@section('title', 'Préstamos - '.config('app.name'))

@section('content')
    @php
        $showAllLoans = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $statusFilter = (string) ($filters['status'] ?? '');
    @endphp

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

    <section class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase">Préstamos</div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format((int) ($summary['total'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase">Vigentes</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format((int) ($summary['outstanding'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase">Atrasados</div>
                    <div class="fs-4 fw-bold {{ (int) ($summary['late'] ?? 0) > 0 ? 'text-danger' : 'text-dark' }}">{{ number_format((int) ($summary['late'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase">Saldados</div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format((int) ($summary['paid'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase">Cuotas vencidas</div>
                    <div class="fs-4 fw-bold {{ (int) ($summary['overdue_installments'] ?? 0) > 0 ? 'text-danger' : 'text-dark' }}">{{ number_format((int) ($summary['overdue_installments'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase">Capital prestado</div>
                    <div class="fs-5 fw-bold text-dark">{{ currency() }} {{ number_format((float) ($summary['principal_total'] ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase">Balance pendiente</div>
                    <div class="fs-5 fw-bold text-primary">{{ currency() }} {{ number_format((float) ($summary['balance_total'] ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body py-3">
                    <div class="text-muted small text-uppercase">Mora pendiente</div>
                    <div class="fs-5 fw-bold {{ (float) ($summary['late_fee_pending'] ?? 0) > 0 ? 'text-danger' : 'text-dark' }}">{{ currency() }} {{ number_format((float) ($summary['late_fee_pending'] ?? 0), 2) }}</div>
                </div>
            </div>
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
                @if ($showAllLoans)
                    <input type="hidden" name="show_all" value="1">
                @endif
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
                            <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $data['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-secondary"><i class="fa-solid fa-filter"></i></button>
                </div>
            </form>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                <div class="small text-muted">
                    @if ($statusFilter !== '')
                        Mostrando préstamos con el estado seleccionado.
                    @elseif ($showAllLoans)
                        Mostrando todos los préstamos, incluyendo saldados.
                    @else
                        Mostrando solo préstamos activos y atrasados.
                    @endif
                </div>
                @if ($showAllLoans || $statusFilter !== '')
                    <a href="{{ route('loans.index', array_filter(['client_id' => $filters['client_id'] ?? null])) }}" class="btn btn-sm btn-outline-primary">
                        Ver activos
                    </a>
                @else
                    <a href="{{ route('loans.index', array_filter(['client_id' => $filters['client_id'] ?? null, 'show_all' => 1])) }}" class="btn btn-sm btn-primary">
                        Ver todos
                    </a>
                @endif
            </div>
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
                            <th class="text-end">Vencidas / hoy</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loans as $loan)
                            <tr onclick="location.href='{{ route('loans.show', $loan) }}'"  >
                                <td>
                                    <a href="{{ route('loans.show', $loan) }}" class="fw-semibold text-decoration-none">{{ $loan->loan_number }}</a>
                                    <div class="text-muted small">{{ $loan->start_date->format('d/m/Y') }}</div>
                                    <div class="small text-success">Ganancia esperada: {{ $loan->currency ?? currency() }} {{ number_format((float) $loan->total_interest, 2) }}</div>
                                </td>
                                <td>{{ $loan->client->full_name }}</td>
                                <td>{{ $frequencyLabels[$loan->payment_frequency] ?? $loan->payment_frequency }}</td>
                                <td class="text-end">{{ $loan->currency ?? currency() }} {{ number_format((float) $loan->principal_amount, 2) }}</td>
                                <td class="text-end">{{ $loan->currency ?? currency() }} {{ number_format((float) $loan->remaining_balance, 2) }}</td>
                                <td class="text-end due-summary">
                                    <div class="{{ (int) ($loan->overdue_installments_count ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                                        <span class="fw-semibold">{{ (int) ($loan->overdue_installments_count ?? 0) }}</span> vencida{{ (int) ($loan->overdue_installments_count ?? 0) === 1 ? '' : 's' }}
                                        @if ((float) ($loan->overdue_amount_due ?? 0) > 0)
                                            <span class="d-block small">{{ $loan->currency ?? currency() }} {{ number_format((float) ($loan->overdue_amount_due ?? 0), 2) }}</span>
                                        @endif
                                    </div>
                                    <div class="small mt-1">
                                        <span class="text-muted">Pagar hoy:</span>
                                        <span class="amount {{ (float) ($loan->amount_due_today ?? 0) > 0 ? 'text-primary' : 'text-muted' }}">
                                            {{ $loan->currency ?? currency() }} {{ number_format((float) ($loan->amount_due_today ?? 0), 2) }}
                                        </span>
                                    </div>
                                </td>
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
                                <td colspan="8" class="text-center text-muted py-5">No hay préstamos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $loans->links() }}</div>
        </div>
    </section>
@endsection
