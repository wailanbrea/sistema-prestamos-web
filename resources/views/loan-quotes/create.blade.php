@extends('layouts.app')

@include('loan-quotes.partials.labels')

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
                        <input id="interest_rate" name="interest_rate" type="number" step="0.0001" min="0" value="{{ old('interest_rate') }}" class="form-control @error('interest_rate') is-invalid @enderror" required>
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
                            @foreach ($methodLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('calculation_method', 'flat_interest') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('calculation_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="interest_type" class="form-label">Tipo de interés</label>
                        <select id="interest_type" name="interest_type" class="form-select @error('interest_type') is-invalid @enderror" required>
                            <option value="fixed" @selected(old('interest_type', 'fixed') === 'fixed')>Fijo</option>
                            <option value="compound" @selected(old('interest_type') === 'compound')>Compuesto</option>
                            <option value="amortized" @selected(old('interest_type') === 'amortized')>Amortizado</option>
                        </select>
                        @error('interest_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
