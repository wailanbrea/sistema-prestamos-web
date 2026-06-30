@extends('layouts.app')

@include('loans.partials.labels')

@php
    $fmtRate = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.') ?: '0';
@endphp

@section('title', 'Editar '.$loan->loan_number.' - '.config('app.name'))

@section('content')
    <style>
        .form-section-title { font-size: .78rem; letter-spacing: .04em; text-transform: uppercase; color: var(--app-muted); font-weight: 700; }
    </style>

    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Editar {{ $loan->loan_number }}</h1>
                <p class="text-muted mb-0">{{ $loan->client->full_name }}</p>
            </div>
            <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </section>

    @if ($hasPayments)
        <div class="alert alert-warning" style="max-width: 920px;">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>Este prestamo ya tiene pagos registrados. Puedes modificar monto, tasa, plazo y mora; las cuotas ya cobradas se conservan y el cambio se recalcula solo desde las cuotas futuras sin pagos.
        </div>
    @endif

    <section class="card content-card" style="max-width: 920px;">
        <div class="card-body">
            <form method="POST" action="{{ route('loans.update', $loan) }}" novalidate>
                @csrf
                @method('PUT')
                @php $disabled = ''; @endphp

                <div class="row g-3">
                    {{-- Cliente --}}
                    <div class="col-12"><span class="form-section-title">Cliente</span></div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" value="{{ $loan->client->full_name }}" disabled>
                    </div>
                    @if (\App\Support\MenuAccess::planAllowsMenu(auth()->user(), 'collectors.index'))
                    <div class="col-12 col-md-6">
                        <label for="collector_id" class="form-label">Cobrador</label>
                        <select id="collector_id" name="collector_id" class="form-select @error('collector_id') is-invalid @enderror">
                            <option value="">Sin cobrador</option>
                            @foreach ($collectors as $collector)
                                <option value="{{ $collector->id }}" @selected((string) old('collector_id', $loan->collector_id) === (string) $collector->id)>{{ $collector->name }}</option>
                            @endforeach
                        </select>
                        @error('collector_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @endif
                    <div class="col-12 col-md-6">
                        <label for="currency" class="form-label">Moneda</label>
                        <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror" {{ $disabled }}>
                            @foreach (config('loan_labels.currencies') as $value => $label)
                                <option value="{{ $value }}" @selected(old('currency', $loan->currency ?? loan_default_currency()) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Condiciones --}}
                    <div class="col-12 mt-3"><span class="form-section-title">Condiciones</span></div>
                    <div class="col-6 col-md-3">
                        <label for="principal_amount" class="form-label">Monto</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ money_symbol(old('currency', $loan->currency ?? loan_default_currency())) }}</span>
                            <input id="principal_amount" name="principal_amount" type="number" step="0.01" min="1" value="{{ old('principal_amount', $loan->principal_amount) }}" class="form-control @error('principal_amount') is-invalid @enderror" {{ $disabled }}>
                        </div>
                        @error('principal_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="interest_rate" class="form-label">Tasa</label>
                        <div class="input-group">
                            <input id="interest_rate" name="interest_rate" type="number" step="0.01" min="0" value="{{ old('interest_rate', $fmtRate($loan->interest_rate)) }}" class="form-control @error('interest_rate') is-invalid @enderror" {{ $disabled }}>
                            <span class="input-group-text">%</span>
                        </div>
                        @error('interest_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="term_quantity" class="form-label">Cuotas</label>
                        <input id="term_quantity" name="term_quantity" type="number" min="1" max="1000" value="{{ old('term_quantity', $loan->term_quantity) }}" class="form-control @error('term_quantity') is-invalid @enderror" {{ $disabled }}>
                        @error('term_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="payment_frequency" class="form-label">Frecuencia</label>
                        <select id="payment_frequency" name="payment_frequency" class="form-select @error('payment_frequency') is-invalid @enderror" {{ $disabled }}>
                            @foreach ($frequencyLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_frequency', $loan->payment_frequency) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="calculation_method" class="form-label">Método de cálculo</label>
                        <select id="calculation_method" name="calculation_method" class="form-select @error('calculation_method') is-invalid @enderror" {{ $disabled }}>
                            @foreach ($methodLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('calculation_method', $loan->calculation_method) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div id="methodHint" class="form-text"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="interest_type" class="form-label">Tipo de interés</label>
                        <select id="interest_type" name="interest_type" class="form-select @error('interest_type') is-invalid @enderror" {{ $disabled }}>
                            <option value="fixed" @selected(old('interest_type', $loan->interest_type) === 'fixed')>Fijo</option>
                            <option value="compound" @selected(old('interest_type', $loan->interest_type) === 'compound')>Compuesto</option>
                            <option value="amortized" @selected(old('interest_type', $loan->interest_type) === 'amortized')>Amortizado</option>
                        </select>
                    </div>

                    {{-- Fechas y mora --}}
                    <div class="col-12 mt-3"><span class="form-section-title">Fechas y mora</span></div>
                    <div class="col-6 col-md-3">
                        <label for="start_date" class="form-label">Fecha inicial</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $loan->start_date->toDateString()) }}" class="form-control @error('start_date') is-invalid @enderror" {{ $disabled }}>
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="first_payment_date" class="form-label">Primer pago</label>
                        <input id="first_payment_date" name="first_payment_date" type="date" value="{{ old('first_payment_date', $loan->first_payment_date->toDateString()) }}" class="form-control @error('first_payment_date') is-invalid @enderror" {{ $disabled }}>
                        @error('first_payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="late_fee_type" class="form-label">Tipo de mora</label>
                        <select id="late_fee_type" name="late_fee_type" class="form-select @error('late_fee_type') is-invalid @enderror">
                            <option value="none" @selected(old('late_fee_type', $loan->late_fee_type) === 'none')>Sin mora</option>
                            <option value="fixed" @selected(old('late_fee_type', $loan->late_fee_type) === 'fixed')>Fija</option>
                            <option value="daily_percentage" @selected(old('late_fee_type', $loan->late_fee_type) === 'daily_percentage')>Porcentaje diario</option>
                            <option value="daily_fixed" @selected(old('late_fee_type', $loan->late_fee_type) === 'daily_fixed')>Monto diario</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="late_fee_value" class="form-label">Valor de mora</label>
                        <input id="late_fee_value" name="late_fee_value" type="number" step="0.01" min="0" value="{{ old('late_fee_value', $fmtRate($loan->late_fee_value)) }}" class="form-control @error('late_fee_value') is-invalid @enderror">
                        @error('late_fee_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Opciones --}}
                    <div class="col-12 mt-3"><span class="form-section-title">Opciones</span></div>
                    <div class="col-12">
                        <input type="hidden" name="allows_capital_prepayment" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="allows_capital_prepayment" name="allows_capital_prepayment" value="1" @checked(old('allows_capital_prepayment', $loan->allows_capital_prepayment))>
                            <label class="form-check-label" for="allows_capital_prepayment">Permitir abono a capital <span class="text-muted">(recalcula las cuotas restantes al recibir pagos extra)</span></label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="guarantee_description" class="form-label">Garantía <span class="text-muted small">(opcional)</span></label>
                        <textarea id="guarantee_description" name="guarantee_description" rows="2" class="form-control @error('guarantee_description') is-invalid @enderror">{{ old('guarantee_description', $loan->guarantee_description) }}</textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="notes" class="form-label">Notas <span class="text-muted small">(opcional)</span></label>
                        <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $loan->notes) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    (function () {
        const hints = {
            flat_interest: 'La tasa es el % <strong>total</strong> sobre el capital (no por cuota). Ej: 10% de 10,000 = 1,000 de interés en todo el préstamo. Cuota fija.',
            fixed_installment: 'Igual que interés fijo: la tasa es el % total sobre el capital y la cuota es constante.',
            capital_plus_interest: 'La tasa es el % de interés <strong>por cuota</strong> sobre el capital. El interés se mantiene y la cuota es fija.',
            interest_only: 'Cada cuota paga solo el interés (% del capital) y el capital completo se paga en la última cuota.',
            french_amortization: 'Cuota fija; la tasa es el % <strong>por período</strong> sobre el saldo pendiente. El interés baja y el capital sube cada cuota. Ideal para préstamos formales.',
        };
        const sel = document.getElementById('calculation_method');
        const hint = document.getElementById('methodHint');
        if (!sel || !hint) return;
        const update = () => { hint.innerHTML = hints[sel.value] || ''; };
        sel.addEventListener('change', update);
        update();
    })();
</script>
@endpush
