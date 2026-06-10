@extends('layouts.app')

@section('title', $account->reference.' - '.config('app.name'))

@section('content')
    @php($accountCurrency = $account->currency ?? currency())
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $account->reference }}</h1>
                <p class="text-muted mb-0">{{ $account->creditor?->name ?: 'Sin acreedor' }}</p>
                <div class="mt-2">@include('partials.status-badge', ['map' => 'account_payable_statuses', 'value' => $account->status])</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($account->payments->isEmpty())
                    <a href="{{ route('accounts-payable.edit', $account) }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-pen me-2"></i> Editar
                    </a>
                    <form action="{{ route('accounts-payable.destroy', $account) }}" method="POST" onsubmit="return confirm('¿Eliminar esta cuenta por pagar? Solo es posible si no tiene pagos registrados.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fa-solid fa-trash me-2"></i> Eliminar
                        </button>
                    </form>
                @endif
                @if (in_array($account->status, ['active', 'late'], true))
                    <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#paymentForm">
                        <i class="fa-solid fa-money-bill-wave me-2"></i> Registrar pago
                    </button>
                @endif
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-lg-3">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Capital tomado</div>
                    <div class="fs-4 fw-bold">{{ $accountCurrency }} {{ number_format((float) $account->principal_amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Saldo capital</div>
                    <div class="fs-4 fw-bold">{{ $accountCurrency }} {{ number_format((float) $account->remaining_balance, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Interes pagado</div>
                    <div class="fs-4 fw-bold">{{ $accountCurrency }} {{ number_format((float) $account->paid_interest, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Mora pagada</div>
                    <div class="fs-4 fw-bold">{{ $accountCurrency }} {{ number_format((float) $account->paid_late_fee, 2) }}</div>
                </div>
            </div>
        </div>
    </section>

    @if (in_array($account->status, ['active', 'late'], true))
        <section id="paymentForm" class="collapse mb-4">
            <div class="card content-card border-0">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Registrar pago</h2>
                    <form method="POST" action="{{ route('accounts-payable.payments.store', $account) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="payment_date" class="form-label">Fecha</label>
                                <input id="payment_date" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control @error('payment_date') is-invalid @enderror" required>
                                @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="amount" class="form-label">Monto</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $accountCurrency }}</span>
                                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                                </div>
                                @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="payment_method" class="form-label">Metodo</label>
                                <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                    @foreach (config('loan_labels.payment_methods', []) as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="payment_notes" class="form-label">Notas</label>
                                <textarea id="payment_notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">Aplicar pago</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    @endif

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-5">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Condiciones</h2>
                    <div class="vstack gap-2">
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Acreedor</span><span class="fw-semibold">{{ $account->creditor?->name ?: 'Sin acreedor' }}</span></div>
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Frecuencia</span><span>{{ config('loan_labels.frequencies.'.$account->payment_frequency, $account->payment_frequency) }}</span></div>
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Metodo</span><span>{{ config('loan_labels.methods.'.$account->calculation_method, $account->calculation_method) }}</span></div>
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Tasa</span><span>{{ rtrim(rtrim(number_format((float) $account->interest_rate, 4, '.', ''), '0'), '.') }}%</span></div>
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Moneda</span><span>{{ $accountCurrency }}</span></div>
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Cuota</span><span>{{ $accountCurrency }} {{ number_format((float) $account->installment_amount, 2) }}</span></div>
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Total interes</span><span>{{ $accountCurrency }} {{ number_format((float) $account->total_interest, 2) }}</span></div>
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Total a pagar</span><span>{{ $accountCurrency }} {{ number_format((float) $account->total_amount, 2) }}</span></div>
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Desembolso</span><span>{{ $account->disbursement_date?->format('d/m/Y') }}</span></div>
                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Primer pago</span><span>{{ $account->first_payment_date?->format('d/m/Y') }}</span></div>
                    </div>
                    @if ($account->notes)
                        <hr>
                        <div class="text-muted small text-uppercase mb-2">Notas</div>
                        <div>{{ $account->notes }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Pagos registrados</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Pago</th>
                                    <th>Fecha</th>
                                    <th>Metodo</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-end">Capital</th>
                                    <th class="text-end">Interes</th>
                                    <th class="text-end">Mora</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($account->payments as $payment)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $payment->payment_number }}</div>
                                            <div class="small text-muted">Saldo: {{ $accountCurrency }} {{ number_format((float) $payment->new_balance, 2) }}</div>
                                        </td>
                                        <td>{{ $payment->payment_date?->format('d/m/Y') }}</td>
                                        <td>{{ config('loan_labels.payment_methods.'.$payment->payment_method, $payment->payment_method) }}</td>
                                        <td class="text-end">{{ $accountCurrency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                        <td class="text-end">{{ $accountCurrency }} {{ number_format((float) $payment->principal_paid, 2) }}</td>
                                        <td class="text-end">{{ $accountCurrency }} {{ number_format((float) $payment->interest_paid, 2) }}</td>
                                        <td class="text-end">{{ $accountCurrency }} {{ number_format((float) $payment->late_fee_paid, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No hay pagos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Calendario de cuotas</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vence</th>
                            <th class="text-end">Capital</th>
                            <th class="text-end">Interes</th>
                            <th class="text-end">Mora</th>
                            <th class="text-end">Pagado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($account->installments as $installment)
                            <tr>
                                <td>#{{ $installment->installment_number }}</td>
                                <td>
                                    {{ $installment->due_date?->format('d/m/Y') }}
                                    @if ((int) $installment->days_late > 0)
                                        <div class="small text-danger">{{ (int) $installment->days_late }} dias atraso</div>
                                    @endif
                                </td>
                                <td class="text-end">{{ $accountCurrency }} {{ number_format((float) $installment->principal_amount, 2) }}</td>
                                <td class="text-end">{{ $accountCurrency }} {{ number_format((float) $installment->interest_amount, 2) }}</td>
                                <td class="text-end">{{ $accountCurrency }} {{ number_format((float) $installment->late_fee, 2) }}</td>
                                <td class="text-end">{{ $accountCurrency }} {{ number_format((float) $installment->total_paid, 2) }}</td>
                                <td>@include('partials.status-badge', ['map' => 'installment_statuses', 'value' => $installment->status])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
