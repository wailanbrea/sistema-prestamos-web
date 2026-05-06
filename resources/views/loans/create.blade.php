@extends('layouts.app')

@include('loans.partials.labels')

@section('title', 'Nuevo préstamo - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Nuevo préstamo</h1>
        <p class="text-muted mb-0">{{ $quote ? 'Convertir cotización en préstamo real.' : 'Crear préstamo desde cero.' }}</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('loans.store') }}" novalidate>
                @csrf
                @if ($quote)
                    <input type="hidden" name="quote_id" value="{{ $quote->id }}">
                    <div class="alert alert-info">
                        Cotización COT-{{ str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT) }}: RD$ {{ number_format((float) $quote->amount, 2) }} · {{ $methodLabels[$quote->calculation_method] ?? $quote->calculation_method }}
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label for="client_id" class="form-label">Cliente</label>
                        <select id="client_id" name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                            <option value="">Seleccionar cliente</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $quote?->client_id) === (string) $client->id)>{{ $client->full_name }} {{ $client->identification ? '· '.$client->identification : '' }}</option>
                            @endforeach
                        </select>
                        @error('client_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-lg-6">
                        <label for="collector_id" class="form-label">Cobrador</label>
                        <select id="collector_id" name="collector_id" class="form-select @error('collector_id') is-invalid @enderror">
                            <option value="">Sin cobrador</option>
                            @foreach ($collectors as $collector)
                                <option value="{{ $collector->id }}" @selected((string) old('collector_id') === (string) $collector->id)>{{ $collector->name }}</option>
                            @endforeach
                        </select>
                        @error('collector_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @unless ($quote)
                        <div class="col-12 col-md-4">
                            <label for="principal_amount" class="form-label">Monto</label>
                            <input id="principal_amount" name="principal_amount" type="number" step="0.01" min="1" value="{{ old('principal_amount') }}" class="form-control @error('principal_amount') is-invalid @enderror" required>
                            @error('principal_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="interest_rate" class="form-label">Tasa</label>
                            <input id="interest_rate" name="interest_rate" type="number" step="0.0001" min="0" value="{{ old('interest_rate') }}" class="form-control @error('interest_rate') is-invalid @enderror" required>
                            @error('interest_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="term_quantity" class="form-label">Cuotas</label>
                            <input id="term_quantity" name="term_quantity" type="number" min="1" max="1000" value="{{ old('term_quantity') }}" class="form-control @error('term_quantity') is-invalid @enderror" required>
                            @error('term_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="payment_frequency" class="form-label">Frecuencia</label>
                            <select id="payment_frequency" name="payment_frequency" class="form-select @error('payment_frequency') is-invalid @enderror">
                                @foreach ($frequencyLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_frequency', 'monthly') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="calculation_method" class="form-label">Método</label>
                            <select id="calculation_method" name="calculation_method" class="form-select @error('calculation_method') is-invalid @enderror">
                                @foreach ($methodLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('calculation_method', 'flat_interest') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="interest_type" class="form-label">Tipo de interés</label>
                            <select id="interest_type" name="interest_type" class="form-select @error('interest_type') is-invalid @enderror">
                                <option value="fixed" @selected(old('interest_type', 'fixed') === 'fixed')>Fijo</option>
                                <option value="compound" @selected(old('interest_type') === 'compound')>Compuesto</option>
                                <option value="amortized" @selected(old('interest_type') === 'amortized')>Amortizado</option>
                            </select>
                        </div>
                    @endunless

                    <div class="col-12 col-md-6">
                        <label for="start_date" class="form-label">Fecha inicial</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $quote?->start_date?->toDateString() ?? now()->toDateString()) }}" class="form-control @error('start_date') is-invalid @enderror" required>
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="first_payment_date" class="form-label">Primer pago</label>
                        <input id="first_payment_date" name="first_payment_date" type="date" value="{{ old('first_payment_date', $quote?->first_payment_date?->toDateString() ?? now()->addMonth()->toDateString()) }}" class="form-control @error('first_payment_date') is-invalid @enderror" required>
                        @error('first_payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="late_fee_type" class="form-label">Tipo de mora</label>
                        <select id="late_fee_type" name="late_fee_type" class="form-select @error('late_fee_type') is-invalid @enderror">
                            <option value="none" @selected(old('late_fee_type', 'none') === 'none')>Sin mora</option>
                            <option value="fixed" @selected(old('late_fee_type') === 'fixed')>Fija</option>
                            <option value="daily_percentage" @selected(old('late_fee_type') === 'daily_percentage')>Porcentaje diario</option>
                            <option value="daily_fixed" @selected(old('late_fee_type') === 'daily_fixed')>Monto diario</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="late_fee_value" class="form-label">Valor de mora</label>
                        <input id="late_fee_value" name="late_fee_value" type="number" step="0.01" min="0" value="{{ old('late_fee_value', '0') }}" class="form-control @error('late_fee_value') is-invalid @enderror">
                        @error('late_fee_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="guarantee_description" class="form-label">Garantía</label>
                        <textarea id="guarantee_description" name="guarantee_description" rows="3" class="form-control @error('guarantee_description') is-invalid @enderror">{{ old('guarantee_description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Crear préstamo</button>
                </div>
            </form>
        </div>
    </section>
@endsection
