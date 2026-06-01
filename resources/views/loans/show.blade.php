@extends('layouts.app')

@include('loans.partials.labels')

@section('title', $loan->loan_number.' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $loan->loan_number }}</h1>
                <p class="text-muted mb-0">{{ $loan->client->full_name }} · {{ $frequencyLabels[$loan->payment_frequency] ?? $loan->payment_frequency }}</p>
            </div>
            <div class="d-flex gap-2">
                @can('loans.approve')
                    @if ($loan->status === 'pending')
                        <form action="{{ route('loans.approve', $loan) }}" method="POST" onsubmit="return confirm('¿Aprobar este préstamo? Se desembolsará en caja.');">
                            @csrf
                            <button type="submit" class="btn btn-success"><i class="fa-solid fa-check me-2"></i>Aprobar</button>
                        </form>
                    @endif
                @endcan
                @can('payments.create')
                    @if (in_array($loan->status, ['active', 'late'], true))
                        <a href="{{ route('payments.create', ['loan_id' => $loan->id]) }}" class="btn btn-outline-primary"><i class="fa-solid fa-cash-register me-2"></i>Cobrar</a>
                    @endif
                @endcan
                @can('loans.update')
                    <a href="{{ route('loans.edit', $loan) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-pen me-2"></i>Editar</a>
                @endcan
                @can('loans.delete')
                    <form action="{{ route('loans.destroy', $loan) }}" method="POST" onsubmit="return confirm('¿Eliminar este préstamo? Solo es posible si no tiene pagos registrados.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-trash me-2"></i>Eliminar</button>
                    </form>
                @endcan
                <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </section>

    @if ($errors->has('loan'))
        <div class="alert alert-danger">{{ $errors->first('loan') }}</div>
    @endif

    <section class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Principal</div><div class="h4 fw-bold mb-0">{{ currency() }} {{ number_format((float) $loan->principal_amount, 2) }}</div></div></article></div>
        <div class="col-12 col-md-6 col-xl-3"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Balance</div><div class="h4 fw-bold mb-0">{{ currency() }} {{ number_format((float) $loan->remaining_balance, 2) }}</div></div></article></div>
        <div class="col-12 col-md-6 col-xl-3"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Cuota</div><div class="h4 fw-bold mb-0">{{ currency() }} {{ number_format((float) $loan->installment_amount, 2) }}</div></div></article></div>
        <div class="col-12 col-md-6 col-xl-3"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Estado</div><div><span class="badge {{ $loanStatusLabels[$loan->status]['class'] ?? 'text-bg-secondary' }}">{{ $loanStatusLabels[$loan->status]['label'] ?? $loan->status }}</span></div></div></article></div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-8">
            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Cuotas generadas</h2>
                    <p class="text-muted small mb-0">Plan de pago oficial del préstamo.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Vencimiento</th>
                                    <th class="text-end">Capital</th>
                                    <th class="text-end">Interés</th>
                                    <th class="text-end">Cuota</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($loan->installments as $installment)
                                    <tr>
                                        <td>{{ $installment->installment_number }}</td>
                                        <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ currency() }} {{ number_format((float) $installment->principal_amount, 2) }}</td>
                                        <td class="text-end">{{ currency() }} {{ number_format((float) $installment->interest_amount, 2) }}</td>
                                        <td class="text-end fw-semibold">{{ currency() }} {{ number_format((float) $installment->installment_amount, 2) }}</td>
                                        <td>@include('partials.status-badge', ['map' => 'installment_statuses', 'value' => $installment->status])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>
        <div class="col-12 col-xl-4">
            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Condiciones</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Cobrador</span><strong>{{ $loan->collector?->name ?? 'Sin cobrador' }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Método</span><strong>{{ $methodLabels[$loan->calculation_method] ?? $loan->calculation_method }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Tasa</span><strong>{{ rtrim(rtrim(number_format((float) $loan->interest_rate, 4, '.', ''), '0'), '.') ?: '0' }}%</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Inicio</span><strong>{{ $loan->start_date->format('d/m/Y') }}</strong></div>
                    <div class="d-flex justify-content-between py-3"><span class="text-muted">Primer pago</span><strong>{{ $loan->first_payment_date->format('d/m/Y') }}</strong></div>
                </div>
            </article>
        </div>
    </section>
@endsection
