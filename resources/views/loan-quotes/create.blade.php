@extends('layouts.app')

@include('loan-quotes.partials.labels')

@php
    $fmtRate = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.') ?: '0';
@endphp

@section('title', 'Nueva cotización - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Nueva cotización</h1>
        <p class="text-muted mb-0">Calcula cuotas e intereses antes de crear un préstamo real.</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('loan-quotes.store') }}" novalidate>
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label for="client_id" class="form-label">Cliente opcional</label>
                        <select id="client_id" name="client_id" class="form-select @error('client_id') is-invalid @enderror">
                            <option value="">Sin cliente</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->full_name }} {{ $client->identification ? '· '.$client->identification : '' }}</option>
                            @endforeach
                        </select>
                        @error('client_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-lg-3">
                        <label for="amount" class="form-label">Monto</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="1" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-lg-3">
                        <label for="interest_rate" class="form-label">Tasa</label>
                        <input id="interest_rate" name="interest_rate" type="number" step="0.01" min="0" value="{{ old('interest_rate', $fmtRate(company_setting('default_interest_rate', 0))) }}" class="form-control @error('interest_rate') is-invalid @enderror" required>
                        @error('interest_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="payment_frequency" class="form-label">Frecuencia</label>
                        <select id="payment_frequency" name="payment_frequency" class="form-select @error('payment_frequency') is-invalid @enderror" required>
                            @foreach ($frequencyLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_frequency', 'monthly') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="calculation_method" class="form-label">Método</label>
                        <select id="calculation_method" name="calculation_method" class="form-select @error('calculation_method') is-invalid @enderror" required>
                            @foreach (enabled_loan_calculation_methods() as $value => $label)
                                {{-- "Cuota fija" da el mismo resultado que "Interés fijo"; se oculta para no duplicar --}}
                                @continue($value === 'fixed_installment')
                                <option value="{{ $value }}" @selected(old('calculation_method', 'french_amortization') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('calculation_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- "Tipo de interés" no afecta el cálculo (lo define el método); se fija por defecto para no confundir --}}
                    <input type="hidden" name="interest_type" value="{{ old('interest_type', 'amortized') }}">
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-start gap-2 mb-0 py-2 px-3" role="alert" style="border-left: 4px solid var(--bs-info, #0dcaf0);">
                            <i class="fa-solid fa-circle-info mt-1"></i>
                            <span id="methodHelp" class="small"></span>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="term_quantity" class="form-label">Cantidad de cuotas</label>
                        <input id="term_quantity" name="term_quantity" type="number" min="1" max="1000" value="{{ old('term_quantity') }}" class="form-control @error('term_quantity') is-invalid @enderror" required>
                        @error('term_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="start_date" class="form-label">Fecha inicial</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date', now()->toDateString()) }}" class="form-control @error('start_date') is-invalid @enderror">
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="first_payment_date" class="form-label">Primer pago</label>
                        <input id="first_payment_date" name="first_payment_date" type="date" value="{{ old('first_payment_date', now()->addMonth()->toDateString()) }}" class="form-control @error('first_payment_date') is-invalid @enderror">
                        @error('first_payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('loan-quotes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-calculator me-2"></i> Calcular y guardar
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    const methodDescriptions = {
        flat_interest: 'La tasa es el % TOTAL sobre el capital (no por cuota). Ej: 10% de 10,000 = 1,000 de interés en todo el préstamo. Cuota fija.',
        fixed_installment: 'Igual que interés fijo: la tasa es el % total sobre el capital y la cuota es constante.',
        capital_plus_interest: 'La tasa es el % de interés POR CUOTA sobre el capital. El interés se mantiene y la cuota es fija (más caro).',
        interest_only: 'Cada cuota paga solo el interés (% del capital por cuota) y el capital completo se paga en la última cuota.',
        german_amortization: 'Capital fijo en cada cuota; la tasa es el % por período sobre el saldo pendiente. La cuota va bajando.',
        french_amortization: 'Cuota fija; la tasa es el % por período sobre el saldo pendiente. El interés baja y el capital sube. Ideal para préstamos formales.',
    };

    const methodSelect = document.getElementById('calculation_method');
    const methodHelp = document.getElementById('methodHelp');
    const syncMethodHelp = () => {
        if (methodSelect && methodHelp) {
            methodHelp.textContent = methodDescriptions[methodSelect.value] || '';
        }
    };

    methodSelect?.addEventListener('change', syncMethodHelp);
    syncMethodHelp();
</script>
@endpush
