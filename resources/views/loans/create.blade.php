@extends('layouts.app')

@include('loans.partials.labels')

@php
    // Muestra la tasa sin ceros de relleno: 10.0000 -> 10, 10.5000 -> 10.5
    $fmtRate = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.') ?: '0';
@endphp

@section('title', 'Nuevo préstamo - '.config('app.name'))

@section('content')
    <style>
        .form-section-title { font-size: .78rem; letter-spacing: .04em; text-transform: uppercase; color: var(--app-muted); font-weight: 700; }
    </style>

    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Nuevo préstamo</h1>
                <p class="text-muted mb-0">{{ $quote ? 'Convertir cotización en préstamo real.' : 'Crear préstamo desde cero.' }}</p>
            </div>
            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-12 {{ $quote ? '' : 'col-xl-7' }}">
        <section class="card content-card h-100">
        <div class="card-body">
            <form method="POST" action="{{ route('loans.store') }}" novalidate>
                @csrf
                @if ($quote)
                    <input type="hidden" name="quote_id" value="{{ $quote->id }}">
                    <div class="alert alert-info">
                        Cotización {{ company_setting('quote_prefix', 'COT') }}-{{ str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT) }}: {{ money_symbol(old('currency', loan_default_currency())) }} {{ number_format((float) $quote->amount, 2) }} · {{ $methodLabels[$quote->calculation_method] ?? $quote->calculation_method }}
                    </div>
                @endif

                <div class="row g-3">
                    {{-- Cliente --}}
                    <div class="col-12"><span class="form-section-title">Cliente</span></div>
                    <div class="col-12 col-md-6">
                        <label for="client_id" class="form-label">Cliente</label>
                        <select id="client_id" name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                            <option value="">Seleccionar cliente</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $quote?->client_id) === (string) $client->id)>{{ $client->full_name }} {{ $client->identification ? '· '.$client->identification : '' }}</option>
                            @endforeach
                        </select>
                        @error('client_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @if (\App\Support\MenuAccess::planAllowsMenu(auth()->user(), 'collectors.index'))
                    <div class="col-12 col-md-6">
                        <label for="collector_id" class="form-label">Cobrador</label>
                        <select id="collector_id" name="collector_id" class="form-select @error('collector_id') is-invalid @enderror">
                            <option value="">Sin cobrador</option>
                            @foreach ($collectors as $collector)
                                <option value="{{ $collector->id }}" @selected((string) old('collector_id') === (string) $collector->id)>{{ $collector->name }}</option>
                            @endforeach
                        </select>
                        @error('collector_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @endif
                    <div class="col-12 col-md-6">
                        <label for="currency" class="form-label">Moneda</label>
                        <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror" required>
                            @foreach (config('loan_labels.currencies') as $value => $label)
                                <option value="{{ $value }}" @selected(old('currency', loan_default_currency()) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @unless ($quote)
                        {{-- Condiciones --}}
                        <div class="col-12 mt-3"><span class="form-section-title">Condiciones</span></div>
                        <div class="col-6 col-md-3">
                            <label for="principal_amount" class="form-label">Monto</label>
                            <div class="input-group">
                                <span class="input-group-text js-loan-currency-symbol">{{ old('currency', loan_default_currency()) }}</span>
                                <input id="principal_amount" name="principal_amount" type="number" step="0.01" min="1" value="{{ old('principal_amount') }}" class="form-control @error('principal_amount') is-invalid @enderror" required>
                            </div>
                            @error('principal_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="interest_rate" class="form-label" id="interest_rate_label">Tasa</label>
                            <div class="input-group">
                                <input id="interest_rate" name="interest_rate" type="number" step="0.01" min="0" value="{{ old('interest_rate', isset($settings) && $settings ? $fmtRate($settings->default_interest_rate) : '') }}" class="form-control @error('interest_rate') is-invalid @enderror" required>
                                <span class="input-group-text" id="interest_rate_suffix">%</span>
                            </div>
                            @error('interest_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <div id="interest_rate_help" class="form-text d-none"></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="term_quantity" class="form-label">Cuotas</label>
                            <input id="term_quantity" name="term_quantity" type="number" min="1" max="1000" value="{{ old('term_quantity') }}" class="form-control @error('term_quantity') is-invalid @enderror" required>
                            @error('term_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="payment_frequency" class="form-label">Frecuencia</label>
                            <select id="payment_frequency" name="payment_frequency" class="form-select @error('payment_frequency') is-invalid @enderror">
                                @foreach ($frequencyLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_frequency', 'monthly') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="calculation_method" class="form-label">Método de cálculo</label>
                            <select id="calculation_method" name="calculation_method" class="form-select @error('calculation_method') is-invalid @enderror">
                                @foreach (enabled_loan_calculation_methods() as $value => $label)
                                    {{-- "Cuota fija" da el mismo resultado que "Interés fijo"; se oculta para no duplicar opciones --}}
                                    @continue($value === 'fixed_installment')
                                    <option value="{{ $value }}" @selected(old('calculation_method', 'french_amortization') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- "Tipo de interés" no afectaba el cálculo (lo define el método); se fija por defecto para no confundir --}}
                        <input type="hidden" name="interest_type" value="{{ old('interest_type', 'amortized') }}">
                        <div class="col-12">
                            <div id="methodHint" class="alert alert-info d-flex align-items-start gap-2 mb-0 py-2 px-3" role="alert" style="border-left: 4px solid var(--bs-info, #0dcaf0);">
                                <i class="fa-solid fa-circle-info mt-1"></i>
                                <span id="methodHintText" class="small"></span>
                            </div>
                        </div>
                    @endunless

                    {{-- Fechas y mora --}}
                    <div class="col-12 mt-3"><span class="form-section-title">Fechas y mora</span></div>
                    <div class="col-6 col-md-3">
                        <label for="start_date" class="form-label">Fecha inicial</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $quote?->start_date?->toDateString() ?? now()->toDateString()) }}" class="form-control @error('start_date') is-invalid @enderror" required>
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="first_payment_date" class="form-label">Primer pago</label>
                        <input id="first_payment_date" name="first_payment_date" type="date" value="{{ old('first_payment_date', $quote?->first_payment_date?->toDateString() ?? now()->addMonth()->toDateString()) }}" class="form-control @error('first_payment_date') is-invalid @enderror" required>
                        @error('first_payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        @php $defLateType = isset($settings) && $settings ? $settings->default_late_fee_type : 'none'; @endphp
                        <label for="late_fee_type" class="form-label">Tipo de mora</label>
                        <select id="late_fee_type" name="late_fee_type" class="form-select @error('late_fee_type') is-invalid @enderror">
                            <option value="none" @selected(old('late_fee_type', $defLateType) === 'none')>Sin mora</option>
                            <option value="fixed" @selected(old('late_fee_type', $defLateType) === 'fixed')>Fija</option>
                            <option value="daily_percentage" @selected(old('late_fee_type', $defLateType) === 'daily_percentage')>Porcentaje diario</option>
                            <option value="daily_fixed" @selected(old('late_fee_type', $defLateType) === 'daily_fixed')>Monto diario</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="late_fee_value" class="form-label">Valor de mora</label>
                        <input id="late_fee_value" name="late_fee_value" type="number" step="0.01" min="0" value="{{ old('late_fee_value', isset($settings) && $settings ? $fmtRate($settings->default_late_fee_value) : '0') }}" class="form-control @error('late_fee_value') is-invalid @enderror">
                        @error('late_fee_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Opciones --}}
                    <div class="col-12 mt-3"><span class="form-section-title">Opciones</span></div>
                    <div class="col-12">
                        <input type="hidden" name="allows_capital_prepayment" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="allows_capital_prepayment" name="allows_capital_prepayment" value="1" @checked(old('allows_capital_prepayment', '1'))>
                            <label class="form-check-label" for="allows_capital_prepayment">Permitir abono a capital <span class="text-muted">(recalcula las cuotas restantes al recibir pagos extra)</span></label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="guarantee_description" class="form-label">Garantía <span class="text-muted small">(opcional)</span></label>
                        <textarea id="guarantee_description" name="guarantee_description" rows="2" class="form-control @error('guarantee_description') is-invalid @enderror">{{ old('guarantee_description') }}</textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="notes" class="form-label">Notas <span class="text-muted small">(opcional)</span></label>
                        <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Crear préstamo</button>
                </div>
            </form>
        </div>
        </section>
        </div>

    @unless ($quote)
        <div class="col-12 col-xl-5">
        <section class="card content-card" style="position: sticky; top: 16px;">
            <div class="card-header bg-white border-0 pb-0">
                <h2 class="h6 fw-bold mb-1">Vista previa del plan de pago</h2>
                <p class="text-muted small mb-0">Se calcula al instante con los datos del formulario; no se guarda hasta crear el préstamo.</p>
            </div>
            <div class="card-body">
                <div id="previewEmpty" class="text-muted text-center py-4">Completa monto, tasa, cuotas y primer pago para ver el plan de cuotas.</div>
                <div id="previewError" class="alert alert-warning small mb-0 d-none"></div>
                <div id="previewContent" class="d-none">
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="border rounded-3 p-2 h-100"><div class="text-muted small">Cuota</div><div class="fw-bold" id="pvCuota">—</div></div></div>
                        <div class="col-6"><div class="border rounded-3 p-2 h-100"><div class="text-muted small">Interés total</div><div class="fw-bold" id="pvInteresTotal">—</div></div></div>
                        <div class="col-6"><div class="border rounded-3 p-2 h-100"><div class="text-muted small">Total a pagar</div><div class="fw-bold" id="pvTotalPagar">—</div></div></div>
                        <div class="col-6"><div class="border rounded-3 p-2 h-100"><div class="text-muted small">Capital</div><div class="fw-bold" id="pvCapital">—</div></div></div>
                    </div>
                    <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="position-sticky top-0" style="background:#fff;">
                                <tr>
                                    <th>#</th>
                                    <th>Vence</th>
                                    <th class="text-end">Capital</th>
                                    <th class="text-end">Interés</th>
                                    <th class="text-end">Cuota</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody id="previewRows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
        </div>
    @endunless
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const hints = {
            flat_interest: 'La tasa es el % <strong>total</strong> sobre el capital (no por cuota). Ej: 10% de 10,000 = 1,000 de interés en todo el préstamo. Cuota fija.',
            fixed_installment: 'Igual que interés fijo: la tasa es el % total sobre el capital y la cuota es constante.',
            capital_plus_interest: 'La tasa es el % de interés <strong>por cuota</strong> sobre el capital. El interés se mantiene y la cuota es fija.',
            interest_only: 'Cada cuota paga solo el interés (% del capital) y el capital completo se paga en la última cuota.',
            german_amortization: 'Amortización alemana: el <strong>capital es fijo</strong> en cada cuota y el interés se calcula sobre el saldo pendiente. La cuota va bajando cada período.',
            french_amortization: 'Cuota fija; la tasa es el % <strong>por período</strong> sobre el saldo pendiente. El interés baja y el capital sube cada cuota. Ideal para préstamos formales.',
            personalized: 'Préstamo personalizado: ingresa el monto fijo de interés por cuota. La tasa porcentual equivalente se calcula automáticamente.',
        };
        const sel = document.getElementById('calculation_method');
        const hint = document.getElementById('methodHintText');
        if (!sel || !hint) return;
        const update = () => { hint.innerHTML = hints[sel.value] || ''; };
        sel.addEventListener('change', update);
        update();
    })();

    // Vista previa del plan de cuotas en vivo.
    (function () {
        const url = @json(route('loans.preview', [], false));
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const ids = ['principal_amount', 'interest_rate', 'term_quantity', 'calculation_method', 'payment_frequency', 'first_payment_date'];
        const el = {};
        ids.forEach((i) => el[i] = document.getElementById(i));
        el.currency = document.getElementById('currency');
        if (ids.some((i) => !el[i])) return; // modo conversión de cotización: sin campos editables

        const empty = document.getElementById('previewEmpty');
        const error = document.getElementById('previewError');
        const content = document.getElementById('previewContent');
        const rowsEl = document.getElementById('previewRows');
        const currencySpans = document.querySelectorAll('.js-loan-currency-symbol');
        const currentCurrency = () => el.currency?.value || @json(loan_default_currency());
        const money = (n) => `${currentCurrency()} ${Number(n || 0).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        const syncCurrencySymbol = () => {
            currencySpans.forEach((span) => {
                span.textContent = currentCurrency();
            });
        };

        const show = (which) => {
            empty.classList.toggle('d-none', which !== 'empty');
            error.classList.toggle('d-none', which !== 'error');
            content.classList.toggle('d-none', which !== 'content');
        };

        let isPersonalizedActive = false;
        const syncInterestInput = () => {
            const isPersonalized = el.calculation_method.value === 'personalized';
            const label = document.getElementById('interest_rate_label');
            const suffix = document.getElementById('interest_rate_suffix');
            const help = document.getElementById('interest_rate_help');
            if (!label || !suffix || !help) return;

            const p = parseFloat(el.principal_amount.value);
            const t = parseInt(el.term_quantity.value);
            const val = parseFloat(el.interest_rate.value);

            if (isPersonalized) {
                label.textContent = 'Interés';
                suffix.textContent = currentCurrency();
                help.classList.remove('d-none');

                if (!isPersonalizedActive) {
                    isPersonalizedActive = true;
                    // Si pasamos a personalizado, convertimos porcentaje a monto de interés por cuota
                    // I = P * (R / 100)
                    if (p > 0 && val > 0) {
                        el.interest_rate.value = (p * (val / 100)).toFixed(2);
                    }
                }

                const currentI = parseFloat(el.interest_rate.value);
                if (p > 0 && t > 0 && currentI > 0) {
                    const r = (currentI / p) * 100;
                    const totInt = currentI * t;
                    const totRate = (totInt / p) * 100;
                    help.innerHTML = `Tasa: <strong>${r.toFixed(4)}%</strong> por cuota (${totRate.toFixed(2)}% total)`;
                } else {
                    help.textContent = '';
                }
            } else {
                help.classList.add('d-none');
                if (isPersonalizedActive) {
                    isPersonalizedActive = false;
                    label.textContent = 'Tasa';
                    suffix.textContent = '%';
                    
                    // Si salimos de personalizado, convertimos el monto de interés a porcentaje
                    // R = (I / P) * 100
                    if (p > 0 && val > 0) {
                        el.interest_rate.value = ((val / p) * 100).toFixed(4);
                    }
                }
            }
        };

        let timer = null;
        const schedule = () => { clearTimeout(timer); timer = setTimeout(run, 400); };

        async function run() {
            const p = parseFloat(el.principal_amount.value);
            let r = parseFloat(el.interest_rate.value);
            const t = parseInt(el.term_quantity.value);
            
            if (el.calculation_method.value === 'personalized' && p > 0 && r > 0) {
                r = (r / p) * 100;
            }

            if (!(p > 0) || isNaN(r) || !(t > 0) || !el.first_payment_date.value) { show('empty'); return; }

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({
                        principal_amount: p, interest_rate: r, term_quantity: t,
                        calculation_method: el.calculation_method.value,
                        payment_frequency: el.payment_frequency.value,
                        first_payment_date: el.first_payment_date.value,
                    }),
                });
                if (res.status === 422) {
                    const j = await res.json();
                    const msgs = j.errors ? Object.values(j.errors).flat().join(' ') : '';
                    error.textContent = msgs || j.message || 'Revisa los datos para calcular la vista previa.';
                    show('error');
                    return;
                }
                if (res.status === 419) { error.textContent = 'Sesión expirada. Recarga la página y vuelve a intentarlo.'; show('error'); return; }
                if (!res.ok) { error.textContent = `Error del servidor (${res.status}). Revisa los logs.`; show('error'); return; }
                const data = await res.json();
                document.getElementById('pvCuota').textContent = money(data.installment_amount);
                document.getElementById('pvInteresTotal').textContent = money(data.total_interest);
                document.getElementById('pvTotalPagar').textContent = money(data.total_amount);
                document.getElementById('pvCapital').textContent = money(data.principal);
                rowsEl.innerHTML = data.installments.map((i) =>
                    `<tr><td>${i.number}</td><td>${i.due_date}</td><td class="text-end">${money(i.principal)}</td><td class="text-end">${money(i.interest)}</td><td class="text-end fw-semibold">${money(i.amount)}</td><td class="text-end">${money(i.balance)}</td></tr>`
                ).join('');
                show('content');
            } catch (e) {
                error.textContent = 'No se pudo conectar con el servidor para calcular la vista previa.';
                show('error');
            }
        }

        ids.forEach((i) => {
            el[i].addEventListener('input', () => {
                syncInterestInput();
                schedule();
            });
            el[i].addEventListener('change', () => {
                syncInterestInput();
                schedule();
            });
        });
        el.calculation_method.addEventListener('change', () => {
            syncInterestInput();
            schedule();
        });
        if (el.currency) {
            el.currency.addEventListener('change', () => {
                syncCurrencySymbol();
                syncInterestInput();
                run();
            });
        }
        
        const form = document.querySelector('form');
        form?.addEventListener('submit', function (event) {
            const p = parseFloat(el.principal_amount.value);
            const r = parseFloat(el.interest_rate.value);
            if (el.calculation_method.value === 'personalized' && p > 0 && r > 0) {
                el.interest_rate.value = ((r / p) * 100).toFixed(6);
            }
        });

        syncCurrencySymbol();
        syncInterestInput();
        run();
    })();
</script>
@endpush
