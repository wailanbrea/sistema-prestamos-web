@extends('layouts.app')

@section('title', 'Nuevo cobro - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Nuevo cobro</h1>
        <p class="text-muted mb-0">Registra un pago y aplica el monto a mora, interés y capital en ese orden.</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('payments.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12">
                        <label for="loan_id" class="form-label">Préstamo</label>
                        <select id="loan_id" name="loan_id" class="form-select @error('loan_id') is-invalid @enderror" required>
                            <option value="">Seleccione un préstamo activo</option>
                            @foreach ($loans as $loan)
                                <option value="{{ $loan->id }}" @selected((string) old('loan_id', $selectedLoan->id ?? '') === (string) $loan->id)>
                                    {{ $loan->loan_number }} · {{ $loan->client->full_name }} · balance RD$ {{ number_format((float) $loan->remaining_balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('loan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="amount" class="form-label">Monto pagado</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="payment_date" class="form-label">Fecha de pago</label>
                        <input id="payment_date" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control @error('payment_date') is-invalid @enderror" required>
                        @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="payment_method" class="form-label">Método</label>
                        <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                            @foreach (['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta', 'check' => 'Cheque', 'other' => 'Otro'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="collector_id" class="form-label">Cobrador</label>
                        <select id="collector_id" name="collector_id" class="form-select @error('collector_id') is-invalid @enderror">
                            <option value="">Usar cobrador asignado al préstamo</option>
                            @foreach ($collectors as $collector)
                                <option value="{{ $collector->id }}" @selected((string) old('collector_id', $selectedLoan->collector_id ?? '') === (string) $collector->id)>
                                    {{ $collector->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('collector_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-cash-register me-2"></i> Registrar cobro
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
