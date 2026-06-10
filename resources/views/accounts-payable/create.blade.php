@extends('layouts.app')

@section('title', 'Nueva cuenta por pagar - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Nueva cuenta por pagar</h1>
                <p class="text-muted mb-0">Registra un prestamo recibido por la empresa y genera su plan de pago.</p>
            </div>
            <a href="{{ route('accounts-payable.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <section class="card content-card h-100">
                <div class="card-body">
                    <form method="POST" action="{{ route('accounts-payable.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="creditor_id" class="form-label">Acreedor</label>
                                <select id="creditor_id" name="creditor_id" class="form-select @error('creditor_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar acreedor</option>
                                    @foreach ($creditors as $creditor)
                                        <option value="{{ $creditor->id }}" @selected((string) old('creditor_id') === (string) $creditor->id)>{{ $creditor->name }}</option>
                                    @endforeach
                                </select>
                                @error('creditor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="currency" class="form-label">Moneda</label>
                                <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror" required>
                                    @foreach (config('loan_labels.currencies') as $value => $label)
                                        <option value="{{ $value }}" @selected(old('currency', account_payable_default_currency()) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="principal_amount" class="form-label">Monto recibido</label>
                                <div class="input-group">
                                    <span class="input-group-text js-account-payable-currency-symbol">{{ money_symbol(old('currency', account_payable_default_currency())) }}</span>
                                    <input id="principal_amount" name="principal_amount" type="number" step="0.01" min="0.01" value="{{ old('principal_amount') }}" class="form-control @error('principal_amount') is-invalid @enderror" required>
                                </div>
                                @error('principal_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="interest_rate" class="form-label">Tasa</label>
                                <div class="input-group">
                                    <input id="interest_rate" name="interest_rate" type="number" step="0.0001" min="0" value="{{ old('interest_rate') }}" class="form-control @error('interest_rate') is-invalid @enderror" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('interest_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="term_quantity" class="form-label">Cantidad de cuotas</label>
                                <input id="term_quantity" name="term_quantity" type="number" min="1" max="1000" value="{{ old('term_quantity') }}" class="form-control @error('term_quantity') is-invalid @enderror" required>
                                @error('term_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="payment_frequency" class="form-label">Frecuencia</label>
                                <select id="payment_frequency" name="payment_frequency" class="form-select @error('payment_frequency') is-invalid @enderror" required>
                                    @foreach (config('loan_labels.frequencies', []) as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_frequency', 'monthly') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('payment_frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="calculation_method" class="form-label">Metodo de calculo</label>
                                <select id="calculation_method" name="calculation_method" class="form-select @error('calculation_method') is-invalid @enderror" required>
                                    @foreach (config('loan_labels.methods', []) as $value => $label)
                                        <option value="{{ $value }}" @selected(old('calculation_method', 'french_amortization') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('calculation_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text" id="methodHint"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="interest_type" class="form-label">Tipo de interes</label>
                                <select id="interest_type" name="interest_type" class="form-select @error('interest_type') is-invalid @enderror" required>
                                    <option value="fixed" @selected(old('interest_type', 'fixed') === 'fixed')>Fijo</option>
                                    <option value="compound" @selected(old('interest_type') === 'compound')>Compuesto</option>
                                    <option value="amortized" @selected(old('interest_type') === 'amortized')>Amortizado</option>
                                </select>
                                @error('interest_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="disbursement_date" class="form-label">Fecha de desembolso</label>
                                <input id="disbursement_date" name="disbursement_date" type="date" value="{{ old('disbursement_date', now()->toDateString()) }}" class="form-control @error('disbursement_date') is-invalid @enderror" required>
                                @error('disbursement_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="first_payment_date" class="form-label">Primer pago</label>
                                <input id="first_payment_date" name="first_payment_date" type="date" value="{{ old('first_payment_date', now()->addMonth()->toDateString()) }}" class="form-control @error('first_payment_date') is-invalid @enderror" required>
                                @error('first_payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="late_fee_type" class="form-label">Tipo de mora</label>
                                <select id="late_fee_type" name="late_fee_type" class="form-select @error('late_fee_type') is-invalid @enderror" required>
                                    <option value="none" @selected(old('late_fee_type', 'none') === 'none')>Sin mora</option>
                                    <option value="fixed" @selected(old('late_fee_type') === 'fixed')>Fija</option>
                                    <option value="daily_fixed" @selected(old('late_fee_type') === 'daily_fixed')>Monto diario</option>
                                </select>
                                @error('late_fee_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="late_fee_value" class="form-label">Valor de mora</label>
                                <input id="late_fee_value" name="late_fee_value" type="number" step="0.01" min="0" value="{{ old('late_fee_value', '0') }}" class="form-control @error('late_fee_value') is-invalid @enderror" required>
                                @error('late_fee_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label">Notas</label>
                                <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('accounts-payable.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-file-invoice-dollar me-2"></i> Registrar cuenta
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="card content-card mb-4">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Nuevo acreedor</h2>
                    <form method="POST" action="{{ route('accounts-payable.creditors.store') }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="creditor_name" class="form-label">Nombre</label>
                            <input id="creditor_name" name="name" type="text" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" maxlength="180" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label for="creditor_phone" class="form-label">Telefono</label>
                            <input id="creditor_phone" name="phone" type="text" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" maxlength="50">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label for="creditor_email" class="form-label">Correo</label>
                            <input id="creditor_email" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" maxlength="150">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label for="creditor_document" class="form-label">Documento</label>
                            <input id="creditor_document" name="document" type="text" value="{{ old('document') }}" class="form-control @error('document') is-invalid @enderror" maxlength="50">
                            @error('document') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fa-solid fa-user-plus me-2"></i> Crear acreedor
                        </button>
                    </form>
                </div>
            </section>

            <section class="card content-card">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Guia rapida</h2>
                    <div class="small text-muted vstack gap-2">
                        <div>1. Registra quien presto el dinero.</div>
                        <div>2. Define monto, tasa, cuotas y frecuencia.</div>
                        <div>3. Al guardar, el sistema crea el calendario y registra la entrada en caja.</div>
                        <div>4. Cada pago reduce capital, interes y mora segun el orden de aplicacion.</div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const hints = {
            flat_interest: 'La tasa se trata como interes total sobre el capital del prestamo.',
            fixed_installment: 'Genera una cuota fija y estable para todo el plazo.',
            capital_plus_interest: 'Cada cuota combina capital lineal mas interes calculado.',
            interest_only: 'Las primeras cuotas cubren interes y el capital fuerte queda al final.',
            french_amortization: 'Cuota fija con interes decreciente sobre saldo pendiente.',
        };

        const method = document.getElementById('calculation_method');
        const hint = document.getElementById('methodHint');
        const currency = document.getElementById('currency');
        const currencySpans = document.querySelectorAll('.js-account-payable-currency-symbol');
        if (!method || !hint) {
            return;
        }

        const update = () => {
            hint.textContent = hints[method.value] || '';
        };

        const updateCurrency = () => {
            currencySpans.forEach((span) => {
                span.textContent = currency?.value || @json(account_payable_default_currency());
            });
        };

        method.addEventListener('change', update);
        currency?.addEventListener('change', updateCurrency);
        update();
        updateCurrency();
    })();
</script>
@endpush
