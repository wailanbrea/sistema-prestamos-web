@extends('layouts.app')

@section('title', 'Nuevo cobro — '.config('app.name'))

@push('styles')
<style>
    /* ── Mode buttons ── */
    .mode-btn {
        border-radius: 12px; line-height: 1.3; padding: 12px; text-align: left;
        border: 1px solid var(--app-border); background: var(--app-surface);
        color: var(--app-muted); transition: all .15s ease; cursor: pointer; width: 100%;
    }
    .mode-btn .mode-title { font-size: .82rem; font-weight: 600; display: block; color: var(--app-text); }
    .mode-btn .mode-desc  { font-size: .7rem;  color: var(--app-muted); display: block; margin-top: 2px; }
    .mode-btn i           { font-size: .9rem; margin-bottom: 6px; display: block; color: var(--app-primary); }
    .mode-btn:hover       { border-color: var(--app-primary); background: rgba(0,38,83,.04); }
    .mode-btn.active {
        background: var(--app-primary); border-color: var(--app-primary); color: #fff;
    }
    .mode-btn.active .mode-title,
    .mode-btn.active .mode-desc,
    .mode-btn.active i { color: #fff !important; }

    /* ── Payment method chips ── */
    .method-chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .method-chip {
        flex: 1 1 calc(33.333% - 8px); min-width: 96px;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 14px 12px; border-radius: 12px;
        border: 2px solid var(--app-border); background: var(--app-surface);
        color: var(--app-muted); font-size: .88rem; font-weight: 600; cursor: pointer;
        transition: all .15s ease; white-space: nowrap;
    }
    .method-chip.selected {
        border-color: var(--app-primary); background: var(--app-secondary); color: var(--app-on-secondary);
    }
    .method-chip:hover:not(.selected) { border-color: var(--app-primary-tint); }
    @media (max-width: 575.98px) {
        .method-chip { flex: 1 1 calc(50% - 8px); padding: 11px 8px; font-size: .82rem; }
    }

    /* ── Amount input ── */
    .amount-input-wrap {
        position: relative; border-radius: 14px;
        border: 2px solid var(--app-border);
        background: var(--app-surface);
        transition: border-color .15s ease;
    }
    .amount-input-wrap:focus-within { border-color: var(--app-primary); }
    .amount-prefix {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
        font-size: 1.4rem; font-weight: 700; color: var(--app-primary); pointer-events: none;
        white-space: nowrap;
    }
    #amount {
        width: 100%; border: none; outline: none; background: transparent;
        font-size: 2.2rem; font-weight: 700; color: var(--app-primary);
        padding: 20px 16px 20px 80px; border-radius: 14px;
        font-variant-numeric: tabular-nums;
    }
    #amount::placeholder { color: var(--app-surface-high); }
    #amount::-webkit-inner-spin-button,
    #amount::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    @media (max-width: 575.98px) {
        .amount-prefix { left: 12px; font-size: 1.15rem; }
        #amount { font-size: 1.7rem; padding: 16px 12px 16px 62px; }
    }

    /* ── Quick amount buttons ── */
    .quick-amt {
        flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 8px 4px; border-radius: 99px; border: 1px solid var(--app-border);
        background: var(--app-surface); color: var(--app-muted);
        font-size: .7rem; font-weight: 600; cursor: pointer; transition: all .15s ease;
        min-width: 0;
    }
    .quick-amt:hover { border-color: var(--app-primary); color: var(--app-primary); background: rgba(0,38,83,.04); }
    .quick-amt.settle { background: var(--app-error-bg); color: var(--app-error); border-color: var(--app-error-bg); }

    /* ── Preview panel ── */
    .preview-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 0; border-bottom: 1px solid var(--app-border-light);
        font-size: .88rem;
    }
    .preview-row:last-child { border-bottom: none; }
    .preview-row .label { color: var(--app-muted); }
    .preview-row .val   { font-weight: 600; color: var(--app-text); }
    .preview-total .val { color: var(--app-primary); font-size: 1.1rem; }

    /* ── Confirm button ── */
    .btn-confirm {
        width: 100%; background: var(--app-primary); color: #fff;
        padding: 16px; border-radius: 14px; border: none;
        font-size: 1rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        transition: all .15s ease; box-shadow: 0 4px 14px rgba(0,38,83,.25);
    }
    .btn-confirm:hover:not(:disabled) { background: var(--app-primary-tint); }
    .btn-confirm:active { transform: scale(.99); }
    .btn-confirm:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; }

    /* ── Client summary card ── */
    .client-summary {
        background: var(--app-surface); border: 1px solid var(--app-border-light);
        border-radius: 14px; padding: 16px;
    }
    .balance-detail {
        background: var(--app-surface-low); border-radius: 10px; padding: 12px 14px;
        margin-top: 12px; display: flex; justify-content: space-between; align-items: center;
    }
</style>
@endpush

@section('content')
<section class="mb-4 anim-fade-up">
    <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
        <div>
            <h1 class="h4 fw-bold mb-1" style="color:var(--app-primary);">Registrar Pago</h1>
            <p class="text-muted mb-0" style="font-size:.88rem;">Elige el préstamo, el modo de reparto y revisa la distribución antes de confirmar.</p>
        </div>
        <a href="{{ route('payments.index') }}" class="btn btn-sm"
           style="border-radius:10px; border:1px solid var(--app-border); background:var(--app-surface); color:var(--app-text); padding:8px 16px;">
            <i class="fa-solid fa-arrow-left me-2"></i> Volver
        </a>
    </div>
</section>

<form method="POST" action="{{ route('payments.store') }}" id="paymentForm" data-no-loading
      data-installments-url="{{ route('payments.loan-installments', ['loan' => '__LOAN__'], false) }}">
    @csrf

    <div class="row g-3">
        {{-- ── Left column ── --}}
        <div class="col-12 col-xl-7">

            {{-- Loan selector --}}
            <section class="card content-card mb-3 anim-fade-up" style="animation-delay:60ms;">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h2 class="h6 fw-bold mb-0" style="color:var(--app-primary);">Seleccionar préstamo</h2>
                </div>
                <div class="card-body">
                    <select id="loan_id" name="loan_id"
                            class="form-select @error('loan_id') is-invalid @enderror"
                            style="border-radius:10px; border-color:var(--app-border);"
                            required>
                        <option value="">— Seleccione un préstamo activo —</option>
                        @foreach ($loans as $loan)
                            <option value="{{ $loan->id }}"
                                    data-currency="{{ $loan->currency ?? currency() }}"
                                    @selected((string)old('loan_id', $selectedLoan->id ?? '') === (string)$loan->id)>
                                {{ $loan->loan_number }} · {{ $loan->client->full_name }} · saldo {{ $loan->currency ?? currency() }} {{ number_format((float)$loan->remaining_balance, 2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('loan_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </section>

            {{-- Cuotas pendientes --}}
            <section class="card content-card mb-3" id="installmentsCard" style="display:none;">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h2 class="h6 fw-bold mb-0" style="color:var(--app-primary);">Cuotas pendientes</h2>
                    <p class="text-muted small mb-0">Lo adeudado por cuota a la fecha del pago.</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="installmentsTable"
                               style="font-size:.85rem;">
                            <thead style="background:var(--app-surface-low); font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:var(--app-muted);">
                                <tr>
                                    <th class="text-center custom-col px-3 py-2" style="display:none;">Pagar</th>
                                    <th class="px-3 py-2">#</th>
                                    <th class="px-3 py-2">Vence</th>
                                    <th class="text-end px-3 py-2">Capital</th>
                                    <th class="text-end px-3 py-2">Interés</th>
                                    <th class="text-end px-3 py-2">Mora</th>
                                    <th class="text-end px-3 py-2">Total</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="text-end custom-col px-3 py-2" style="display:none;">Monto a pagar</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between border-top fw-semibold px-4 py-3" id="totalPendingRow" style="display:none; font-size:.88rem;">
                        <span style="color:var(--app-muted);">Saldo pendiente actual</span>
                        <span id="totalPendingNow" style="color:var(--app-text);">{{ $selectedLoan->currency ?? currency() }} 0.00</span>
                    </div>
                    <p class="text-muted small mb-0 px-4 py-3" id="noInstallments" style="display:none;">Este préstamo no tiene cuotas pendientes.</p>
                </div>
            </section>


        </div>

        {{-- ── Right column ── --}}
        <div class="col-12 col-xl-5">

            {{-- Preview card --}}
            <section class="card content-card mb-3" id="previewCard" style="display:none;">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h2 class="h6 fw-bold mb-0" style="color:var(--app-primary);">Distribución del pago</h2>
                    <p class="text-muted small mb-0">Cálculo estimado (el servidor confirma al guardar).</p>
                </div>
                <div class="card-body">
                    <div class="preview-row">
                        <span class="label"><i class="fa-solid fa-sack-dollar me-2 opacity-50"></i>Capital</span>
                        <span class="val" id="pvPrincipal">{{ $selectedLoan->currency ?? currency() }} 0.00</span>
                    </div>
                    <div class="preview-row">
                        <span class="label"><i class="fa-solid fa-percent me-2 opacity-50"></i>Interés</span>
                        <span class="val" id="pvInterest">{{ $selectedLoan->currency ?? currency() }} 0.00</span>
                    </div>
                    <div class="preview-row">
                        <span class="label"><i class="fa-solid fa-clock me-2 opacity-50"></i>Mora</span>
                        <span class="val" id="pvLate">{{ $selectedLoan->currency ?? currency() }} 0.00</span>
                    </div>
                    <div class="preview-row" id="pvPrepayRow" style="display:none;">
                        <span class="label"><i class="fa-solid fa-arrow-trend-down me-2 opacity-50"></i>Abono a capital</span>
                        <span class="val" id="pvPrepay">{{ $selectedLoan->currency ?? currency() }} 0.00</span>
                    </div>
                    <div class="preview-row preview-total">
                        <span class="label fw-semibold" style="color:var(--app-text);">Total cobrado</span>
                        <span class="val" id="pvTotal" style="color:var(--app-primary); font-size:1.1rem;">{{ $selectedLoan->currency ?? currency() }} 0.00</span>
                    </div>
                    <div class="preview-row" id="pvChangeRow" style="display:none;">
                        <span class="label fw-semibold" style="color:#166534;">Vuelto al cliente</span>
                        <span class="val fw-bold" id="pvChange" style="color:#166534;">{{ $selectedLoan->currency ?? currency() }} 0.00</span>
                    </div>
                    <div class="preview-row">
                        <span class="label" style="font-size:.82rem;">Saldo pendiente después</span>
                        <span class="val" id="pvBalance" style="font-size:.82rem;">{{ $selectedLoan->currency ?? currency() }} 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size:.78rem; padding-top:6px;">
                        <span class="text-muted">Balance de capital después</span>
                        <span class="text-muted" id="pvCapitalBalance">{{ $selectedLoan->currency ?? currency() }} 0.00</span>
                    </div>
                    <div id="pvLeftover" class="alert alert-warning small mt-3 mb-0" style="display:none; border-radius:10px;"></div>
                </div>
            </section>

            {{-- Modo de reparto --}}
            <section class="card content-card mb-3 shadow-sm anim-fade-up" id="modeCard" style="display:none;">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h2 class="h6 fw-bold mb-0" style="color:var(--app-primary);">Modo de reparto</h2>
                    <p class="text-muted small mb-0">Cómo se aplica el dinero recibido.</p>
                </div>
                <div class="card-body">
                    @php
                        $enabledPaymentModes = enabled_payment_allocation_modes();
                        $enabledPaymentModeKeys = array_keys($enabledPaymentModes);
                        $defaultPaymentMode = $enabledPaymentModeKeys[0] ?? 'auto';
                    @endphp
                    <input type="hidden" name="allocation_mode" id="allocation_mode" value="{{ old('allocation_mode', $defaultPaymentMode) }}">
                    <div class="row g-2 mb-4" id="modeOptions">
                        @php
                            $modes = array_intersect_key([
                                'auto'                   => ['Automático',         'Paga mora, interés y capital de cuotas en orden.', 'fa-wand-magic-sparkles'],
                                'principal_and_interest' => ['Capital + Interés',  'Paga capital e interés de cuotas; no incluye mora.', 'fa-scale-balanced'],
                                'interest_only'          => ['Solo interés',       'Solo cubre interés; no baja el balance de capital.', 'fa-percent'],
                                'principal_only'         => ['Solo capital',       'Baja capital, pero deja intereses/mora pendientes.', 'fa-sack-dollar'],
                                'custom'                 => ['Personalizado',      'Elige cuotas y montos exactos a pagar.', 'fa-sliders'],
                            ], $enabledPaymentModes);
                        @endphp
                        @foreach ($modes as $value => $info)
                            <div class="col-6 col-md-4 col-xl-6">
                                <button type="button" class="mode-btn" data-mode="{{ $value }}">
                                    <i class="fa-solid {{ $info[2] }}"></i>
                                    <span class="mode-title">{{ $info[0] }}</span>
                                    <span class="mode-desc">{{ $info[1] }}</span>
                                </button>
                            </div>
                        @endforeach
                        @if (in_array('current_plus_capital', $enabledPaymentModeKeys, true))
                            <div class="col-6 col-md-4 col-xl-6">
                                <button type="button" class="mode-btn" data-mode="current_plus_capital">
                                    <i class="fa-solid fa-arrow-trend-down"></i>
                                    <span class="mode-title">Cuota + capital</span>
                                    <span class="mode-desc">Para abonar o saldar antes de tiempo.</span>
                                </button>
                            </div>
                        @endif
                    </div>
                    @if (in_array('current_plus_capital', $enabledPaymentModeKeys, true))
                    <div class="alert alert-info mb-4" style="border-radius:12px; font-size:.84rem;">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Para saldar un préstamo antes de tiempo usa <strong>Cuota + capital</strong>: primero cubre la cuota actual y luego aplica el monto indicado directamente al capital. No uses <strong>Solo capital</strong> si quieres cerrar el préstamo, porque puede dejar intereses pendientes.
                    </div>
                    @endif

                    {{-- Amount + target --}}
                    <div id="pooledControls" class="row g-3">
                        <div class="col-12">
                            <label for="amount" class="form-label" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">
                                Monto a cobrar
                            </label>
                            <div class="amount-input-wrap">
                                <span class="amount-prefix js-payment-currency-symbol">{{ $selectedLoan->currency ?? currency() }}</span>
                                <input id="amount" name="amount" type="number" step="0.01" min="0.01"
                                       value="{{ old('amount') }}"
                                       placeholder="0.00"
                                       class="@error('amount') is-invalid @enderror">
                            </div>
                            @error('amount') <div class="invalid-feedback d-block mt-1">{{ $message }}</div> @enderror
                            {{-- Quick amount chips --}}
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                <button type="button" class="quick-amt" onclick="quickAmt(1000)">1,000</button>
                                <button type="button" class="quick-amt" onclick="quickAmt(2500)">2,500</button>
                                <button type="button" class="quick-amt" onclick="quickAmt(5000)">5,000</button>
                                <button type="button" class="quick-amt settle" onclick="quickSettle()">Saldar todo</button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="target_installment_id" class="form-label" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">
                                Aplicar a
                            </label>
                            <select id="target_installment_id" name="target_installment_id"
                                    class="form-select" style="border-radius:10px; border-color:var(--app-border);">
                                <option value="">Todas las cuotas pendientes (en orden)</option>
                            </select>
                        </div>
                    </div>

                    <div id="excessPanel" class="mt-3" style="display:none;">
                        <div class="alert mb-0" style="background:var(--app-surface-low); border:1px solid var(--app-border-light); border-radius:12px;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold" style="font-size:.88rem;">Excedente sobre lo adeudado</span>
                                <strong id="excessAmount" style="color:var(--app-primary);">{{ $selectedLoan->currency ?? currency() }} 0.00</strong>
                            </div>
                            <div id="capitalPrepaymentAmountWrap" class="mb-2" style="display:none;">
                                <label for="capital_prepayment_amount" class="form-label mb-1" style="font-size:.75rem; font-weight:700; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">
                                    Cuanto se abonara al capital
                                </label>
                                <div class="amount-input-wrap" style="max-width:280px;">
                                    <span class="amount-prefix js-payment-currency-symbol">{{ $selectedLoan->currency ?? currency() }}</span>
                                    <input id="capital_prepayment_amount" name="capital_prepayment_amount" type="number" step="0.01" min="0"
                                           value="{{ old('capital_prepayment_amount') }}"
                                           placeholder="0.00"
                                           class="@error('capital_prepayment_amount') is-invalid @enderror">
                                </div>
                                <div class="small text-muted mt-1">No puede exceder el sobrante disponible del pago.</div>
                                @error('capital_prepayment_amount') <div class="invalid-feedback d-block mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-check" id="prepaymentOption">
                                <input class="form-check-input" type="radio" name="excess_action" id="excessPrepay" value="prepayment">
                                <label class="form-check-label" for="excessPrepay" style="font-size:.85rem;">
                                    Abonar a capital <span class="text-muted">(recalcula las cuotas restantes)</span>
                                </label>
                            </div>
                            <div class="form-check" id="changeOption">
                                <input class="form-check-input" type="radio" name="excess_action" id="excessChange" value="change">
                                <label class="form-check-label" for="excessChange" style="font-size:.85rem;">
                                    Entregar como vuelto al cliente
                                </label>
                            </div>
                            <div id="prepayDisabledNote" class="small text-muted mt-1" style="display:none;">
                                <i class="fa-solid fa-circle-info me-1"></i> Este préstamo no permite abono a capital.
                            </div>
                        </div>
                    </div>

                    <div id="customHint" class="alert mb-0 mt-3" style="display:none; background:rgba(0,38,83,.06); border:1px solid rgba(0,38,83,.15); border-radius:12px; font-size:.83rem; color:var(--app-primary);">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Marca las cuotas y escribe cuánto pagar en cada una. El monto se distribuye mora → interés → capital dentro de cada cuota.
                    </div>

                    <div id="modeWarning" class="alert alert-warning mb-0 mt-3" style="display:none; border-radius:12px; font-size:.83rem;"></div>
                </div>
            </section>

            {{-- Date / method / collector + submit --}}
            <section class="card content-card anim-fade-up" style="animation-delay:100ms;">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h2 class="h6 fw-bold mb-0" style="color:var(--app-primary);">Detalles del cobro</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="payment_date" class="form-label" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Fecha de pago</label>
                            <input id="payment_date" name="payment_date" type="date"
                                   value="{{ old('payment_date', now()->toDateString()) }}"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   style="border-radius:10px;" required>
                            @error('payment_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label d-block" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Método de pago</label>
                            @php
                                $methodIcons = [
                                    'cash'     => 'fa-money-bill-wave',
                                    'transfer' => 'fa-building-columns',
                                    'card'     => 'fa-credit-card',
                                    'check'    => 'fa-money-check-dollar',
                                    'other'    => 'fa-ellipsis',
                                ];
                            @endphp
                            <div class="method-chips">
                                @foreach (config('loan_labels.payment_methods') as $value => $label)
                                    <button type="button" onclick="selectMethod('{{ $value }}')"
                                            class="method-chip {{ old('payment_method', 'cash') === $value ? 'selected' : '' }}"
                                            id="chip_{{ $value }}">
                                        <i class="fa-solid {{ $methodIcons[$value] ?? 'fa-building-columns' }}"></i>
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            <select id="payment_method" name="payment_method"
                                    class="form-select @error('payment_method') is-invalid @enderror"
                                    style="display:none;" required>
                                @foreach (config('loan_labels.payment_methods') as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        @if (\App\Support\MenuAccess::planAllowsMenu(auth()->user(), 'collectors.index'))
                        <div class="col-12">
                            <label for="collector_id" class="form-label" style="font-size:.8rem; font-weight:600; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Cobrador</label>
                            <select id="collector_id" name="collector_id"
                                    class="form-select @error('collector_id') is-invalid @enderror"
                                    style="border-radius:10px;">
                                <option value="">Usar cobrador asignado al préstamo</option>
                                @foreach ($collectors as $collector)
                                    <option value="{{ $collector->id }}" @selected((string)old('collector_id', $selectedLoan->collector_id ?? '') === (string)$collector->id)>
                                        {{ $collector->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('collector_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        @endif
                    </div>

                    <div class="mt-4 d-flex flex-column gap-2">
                        <button type="submit" class="btn-confirm" id="submitBtn" disabled>
                            <i class="fa-solid fa-cash-register"></i>
                            CONFIRMAR COBRO
                        </button>
                        <a href="{{ route('payments.index') }}" class="btn btn-sm text-center"
                           style="border-radius:10px; color:var(--app-muted); font-size:.85rem; padding:10px;">
                            Cancelar
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
/* ── Quick amount chips ── */
function quickAmt(val) {
    const inp = document.getElementById('amount');
    if (!inp) return;
    inp.value = val.toFixed(2);
    inp.dispatchEvent(new Event('input'));
}
function quickSettle() {
    const el = document.getElementById('totalPendingNow');
    if (!el) return;
    const val = parseFloat(el.textContent.replace(/[^\d.]/g, '')) || 0;
    if (val > 0) quickAmt(val);
}

/* ── Payment method chip sync ── */
function selectMethod(val) {
    document.querySelectorAll('.method-chip').forEach(c => c.classList.remove('selected'));
    const chip = document.getElementById('chip_' + val);
    if (chip) chip.classList.add('selected');
    document.getElementById('payment_method').value = val;
}

/* ── Original payment JS (unchanged) ── */
(function () {
    const form = document.getElementById('paymentForm');
    const urlTpl = form.dataset.installmentsUrl;
    const loanSelect = document.getElementById('loan_id');
    const installmentsCard = document.getElementById('installmentsCard');
    const tableBody = document.querySelector('#installmentsTable tbody');
    const noInstallments = document.getElementById('noInstallments');
    const modeCard = document.getElementById('modeCard');
    const previewCard = document.getElementById('previewCard');
    const modeInput = document.getElementById('allocation_mode');
    const pooledControls = document.getElementById('pooledControls');
    const customHint = document.getElementById('customHint');
    const modeWarning = document.getElementById('modeWarning');
    const amountInput = document.getElementById('amount');
    const targetSelect = document.getElementById('target_installment_id');
    const submitBtn = document.getElementById('submitBtn');
    const customCols = document.querySelectorAll('.custom-col');

    const excessPanel = document.getElementById('excessPanel');
    const excessAmount = document.getElementById('excessAmount');
    const excessPrepay = document.getElementById('excessPrepay');
    const excessChange = document.getElementById('excessChange');
    const prepaymentOption = document.getElementById('prepaymentOption');
    const changeOption = document.getElementById('changeOption');
    const prepayDisabledNote = document.getElementById('prepayDisabledNote');
    const capitalPrepaymentAmountWrap = document.getElementById('capitalPrepaymentAmountWrap');
    const capitalPrepaymentInput = document.getElementById('capital_prepayment_amount');

    let installments = [];
    let balance = 0;
    let allowsPrepayment = false;
    let loadError = '';

    const currencySpans = document.querySelectorAll('.js-payment-currency-symbol');
    const currentCurrency = () => loanSelect.selectedOptions[0]?.dataset.currency || @json($selectedLoan->currency ?? currency());
    const money = (n) => `${currentCurrency()} ${Number(n || 0).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const syncCurrencySymbol = () => { currencySpans.forEach((span) => { span.textContent = currentCurrency(); }); };
    const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

    async function loadInstallments(loanId) {
        installments = [];
        loadError = '';
        syncCurrencySymbol();
        if (!loanId) { render(); return; }
        try {
            const res = await fetch(urlTpl.replace('__LOAN__', loanId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            balance = Number(data.remaining_balance || 0);
            allowsPrepayment = !!data.allows_capital_prepayment;
            installments = data.installments || [];
        } catch (e) {
            installments = [];
            loadError = 'No se pudieron cargar las cuotas (' + (e && e.message ? e.message : 'error de conexión') + ').';
        }
        buildTargetOptions();
        render();
        suggestAmount();
        recompute();
    }

    function suggestAmount() {
        if (modeInput.value === 'custom') return;
        const mode = modeInput.value;
        const pick = (i) => mode === 'interest_only' ? i.interest_due : (mode === 'principal_only' ? i.principal_due : (mode === 'principal_and_interest' ? round2(i.interest_due + i.principal_due) : i.total_due));
        const targetId = targetSelect.value;
        let val = 0;
        if (targetId) {
            const inst = installments.find((x) => x.id == targetId);
            if (inst) val = pick(inst);
        } else if (mode === 'current_plus_capital') {
            const inst = installments[0];
            if (inst) val = pick(inst);
        } else {
            // Sugerir solo la próxima cuota pendiente (no el balance completo) para
            // evitar cobrar de más por accidente. Para saldar el préstamo está "Saldar todo".
            const inst = installments[0];
            if (inst) val = pick(inst);
        }
        amountInput.value = round2(val).toFixed(2);
    }

    function buildTargetOptions() {
        targetSelect.innerHTML = '<option value="">Todas las cuotas pendientes (en orden)</option>';
        installments.forEach((i) => {
            const o = document.createElement('option');
            o.value = i.id;
            o.textContent = `Cuota #${i.number} · vence ${i.due_date} · adeuda ${money(i.total_due)}`;
            targetSelect.appendChild(o);
        });
    }

    const statusBadge = (s) => {
        const map = { pending:['Pendiente','text-bg-secondary'], partial:['Parcial','text-bg-info'], late:['Atrasada','text-bg-warning'], paid:['Pagada','text-bg-success'], cancelled:['Cancelada','text-bg-dark'] };
        const d = map[s] || [s, 'text-bg-secondary'];
        return `<span class="badge ${d[1]}">${d[0]}</span>`;
    };

    function render() {
        const mode = modeInput.value;
        const isCustom = mode === 'custom';
        const hasInstallments = installments.length > 0;

        installmentsCard.style.display = loanSelect.value ? '' : 'none';
        modeCard.style.display = (loanSelect.value && hasInstallments) ? '' : 'none';
        previewCard.style.display = (loanSelect.value && hasInstallments) ? '' : 'none';
        if (loadError) {
            noInstallments.style.display = loanSelect.value ? '' : 'none';
            noInstallments.innerHTML = `<span class="text-danger">${loadError}</span>
                <button type="button" id="retryInstallments" class="btn btn-sm btn-link p-0 ms-1">Reintentar</button>`;
            const retry = document.getElementById('retryInstallments');
            if (retry) retry.addEventListener('click', () => loadInstallments(loanSelect.value));
        } else {
            noInstallments.style.display = (loanSelect.value && !hasInstallments) ? '' : 'none';
            noInstallments.textContent = 'Este préstamo no tiene cuotas pendientes.';
        }
        customCols.forEach((c) => c.style.display = isCustom ? '' : 'none');

        const totalPending = installments.reduce((s, i) => s + Number(i.total_due || 0), 0);
        document.getElementById('totalPendingRow').style.display = hasInstallments ? '' : 'none';
        document.getElementById('totalPendingNow').textContent = money(totalPending);

        tableBody.innerHTML = '';
        installments.forEach((i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center custom-col px-3" style="${isCustom ? '' : 'display:none'}">
                    <input type="checkbox" class="form-check-input custom-check" data-id="${i.id}">
                </td>
                <td class="px-3">${i.number}</td>
                <td class="px-3">${i.due_date}</td>
                <td class="text-end px-3">${money(i.principal_due)}</td>
                <td class="text-end px-3">${money(i.interest_due)}</td>
                <td class="text-end px-3">${money(i.late_due)}</td>
                <td class="text-end px-3 fw-semibold">${money(i.total_due)}</td>
                <td class="px-3">${statusBadge(i.status)}</td>
                <td class="text-end custom-col px-3" style="${isCustom ? '' : 'display:none'}">
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end custom-amount"
                           data-id="${i.id}" style="max-width:120px; margin-left:auto; border-radius:8px;" disabled placeholder="0.00">
                </td>`;
            tableBody.appendChild(tr);
        });

        if (isCustom) {
            tableBody.querySelectorAll('.custom-check').forEach((chk) => {
                chk.addEventListener('change', (e) => {
                    const row = e.target.closest('tr');
                    const amt = row.querySelector('.custom-amount');
                    const inst = installments.find((x) => x.id == e.target.dataset.id);
                    amt.disabled = !e.target.checked;
                    if (e.target.checked && !amt.value) amt.value = inst.total_due.toFixed(2);
                    if (!e.target.checked) amt.value = '';
                    recompute();
                });
            });
            tableBody.querySelectorAll('.custom-amount').forEach((inp) => inp.addEventListener('input', recompute));
        }

        pooledControls.style.display = isCustom ? 'none' : '';
        customHint.style.display = isCustom ? '' : 'none';

        document.querySelectorAll('.mode-btn').forEach((b) => {
            b.classList.toggle('active', b.dataset.mode === mode);
        });
    }

    function recompute() {
        const mode = modeInput.value;
        const isCurrentPlusCapital = mode === 'current_plus_capital';
        let principal = 0, interest = 0, late = 0, leftover = 0;

        const applyBuckets = (inst, buckets, avail) => {
            let p = 0, it = 0, l = 0;
            for (const b of buckets) {
                if (avail <= 0) break;
                const due = b === 'late' ? inst.late_due : (b === 'interest' ? inst.interest_due : inst.principal_due);
                const used = Math.min(avail, due);
                if (b === 'late') l = used; else if (b === 'interest') it = used; else p = used;
                avail = round2(avail - used);
            }
            return { p, it, l, used: round2(p + it + l), avail };
        };

        if (mode === 'custom') {
            tableBody.querySelectorAll('.custom-amount').forEach((inp) => {
                if (inp.disabled) return;
                const inst = installments.find((x) => x.id == inp.dataset.id);
                let amt = round2(inp.value || 0);
                if (amt <= 0) return;
                const r = applyBuckets(inst, ['late','interest','principal'], amt);
                principal += r.p; interest += r.it; late += r.l;
                if (r.avail > 0) leftover += r.avail;
            });
        } else {
            const buckets = mode === 'interest_only' ? ['interest'] : (mode === 'principal_only' ? ['principal'] : (mode === 'principal_and_interest' ? ['interest','principal'] : ['late','interest','principal']));
            let avail = round2(amountInput.value || 0);
            const targetId = targetSelect.value;
            const queue = targetId
                ? installments.filter((i) => i.id == targetId)
                : (mode === 'current_plus_capital' ? installments.slice(0, 1) : installments);
            for (const inst of queue) {
                if (avail <= 0) break;
                const r = applyBuckets(inst, buckets, avail);
                principal += r.p; interest += r.it; late += r.l;
                avail = r.avail;
            }
            leftover = avail;
        }

        principal = round2(principal); interest = round2(interest); late = round2(late);
        leftover = round2(leftover);

        let prepay = 0, change = 0, currentPlusCapitalError = '';
        capitalPrepaymentAmountWrap.style.display = isCurrentPlusCapital ? '' : 'none';
        capitalPrepaymentInput.required = isCurrentPlusCapital;
        capitalPrepaymentInput.max = '';
        if (!isCurrentPlusCapital) {
            capitalPrepaymentInput.value = '';
        }

        if (leftover > 0.001) {
            excessPanel.style.display = '';
            excessAmount.textContent = money(leftover);

            if (isCurrentPlusCapital) {
                capitalPrepaymentInput.max = leftover.toFixed(2);
                prepaymentOption.style.display = 'none';
                changeOption.style.display = 'none';
                prepayDisabledNote.style.display = allowsPrepayment ? 'none' : '';
                excessPrepay.checked = true;
                excessChange.checked = false;

                const outstandingAfter = Math.max(0, round2(balance - principal));
                const hasCapitalInput = capitalPrepaymentInput.value !== '';
                const requestedPrepay = round2(capitalPrepaymentInput.value || 0);

                if (!allowsPrepayment) {
                    currentPlusCapitalError = 'Este prestamo no permite abono a capital.';
                } else if (!hasCapitalInput || requestedPrepay <= 0) {
                    currentPlusCapitalError = 'Indica cuanto se abonara al capital.';
                } else if (requestedPrepay - leftover > 0.01) {
                    currentPlusCapitalError = 'El abono a capital no puede ser mayor que el sobrante disponible.';
                } else if (requestedPrepay - outstandingAfter > 0.01) {
                    currentPlusCapitalError = 'El abono a capital no puede ser mayor que el capital pendiente.';
                } else if (Math.abs(requestedPrepay - leftover) > 0.01) {
                    currentPlusCapitalError = 'El monto a cobrar debe ser la cuota mas el abono a capital indicado.';
                }

                prepay = round2(Math.min(Math.max(requestedPrepay, 0), leftover, outstandingAfter));
                change = round2(leftover - prepay);
            } else {
                prepaymentOption.style.display = allowsPrepayment ? '' : 'none';
                changeOption.style.display = '';
                prepayDisabledNote.style.display = allowsPrepayment ? 'none' : '';
                if (!allowsPrepayment && excessPrepay.checked) { excessPrepay.checked = false; excessChange.checked = true; }
                if (!excessPrepay.checked && !excessChange.checked) {
                    if (allowsPrepayment) excessPrepay.checked = true; else excessChange.checked = true;
                }
                if (excessPrepay.checked) {
                    const outstandingAfter = Math.max(0, round2(balance - principal));
                    prepay = round2(Math.min(leftover, outstandingAfter));
                    change = round2(leftover - prepay);
                } else { change = leftover; }
            }
        } else {
            excessPanel.style.display = isCurrentPlusCapital ? '' : 'none';
            excessAmount.textContent = money(0);
            prepaymentOption.style.display = isCurrentPlusCapital ? 'none' : (allowsPrepayment ? '' : 'none');
            changeOption.style.display = isCurrentPlusCapital ? 'none' : '';
            prepayDisabledNote.style.display = isCurrentPlusCapital && !allowsPrepayment ? '' : 'none';
            excessPrepay.checked = false;
            excessChange.checked = false;
            if (isCurrentPlusCapital) {
                currentPlusCapitalError = amountInput.value
                    ? 'El monto a cobrar debe incluir una cuota mas el abono a capital.'
                    : 'Indica el monto a cobrar y cuanto se abonara al capital.';
            }
        }

        const charged = round2(principal + interest + late + prepay);
        const applied = round2(principal + interest + late);
        const totalPending = installments.reduce((s, i) => s + Number(i.total_due || 0), 0);
        const pendingAfter = Math.max(0, round2(totalPending - applied - prepay));
        const capitalAfter = Math.max(0, round2(balance - principal - prepay));

        document.getElementById('pvPrincipal').textContent = money(principal);
        document.getElementById('pvInterest').textContent  = money(interest);
        document.getElementById('pvLate').textContent      = money(late);
        document.getElementById('pvTotal').textContent     = money(charged);
        document.getElementById('pvBalance').textContent   = money(pendingAfter);
        document.getElementById('pvCapitalBalance').textContent = money(capitalAfter);

        const prepayRow = document.getElementById('pvPrepayRow');
        prepayRow.style.display = prepay > 0 ? '' : 'none';
        document.getElementById('pvPrepay').textContent = money(prepay);
        const changeRow = document.getElementById('pvChangeRow');
        changeRow.style.display = change > 0 ? '' : 'none';
        document.getElementById('pvChange').textContent = money(change);
        document.getElementById('pvLeftover').style.display = 'none';

        let warn = '';
        if (currentPlusCapitalError) {
            warn = currentPlusCapitalError;
        } else if (mode === 'current_plus_capital' && prepay > 0) {
            warn = 'Cuota + capital: solo se cubre una cuota; el abono indicado baja directamente el balance de capital.';
        } else if (mode === 'principal_only' && (principal + prepay) > 0) {
            warn = 'Pago a capital: el interés y la mora de esas cuotas quedarán pendientes y la cuota no se marcará como Pagada.';
        } else if (mode === 'interest_only' && interest > 0 && prepay === 0) {
            warn = 'Pago solo a interés: el balance de capital no baja.';
        } else if (mode === 'principal_and_interest' && (principal + interest) > 0 && late === 0) {
            warn = 'Capital + Interés: la mora acumulada queda pendiente y no se incluye en este pago.';
        }
        modeWarning.style.display = warn ? '' : 'none';
        modeWarning.textContent = warn;

        submitBtn.disabled = applied <= 0 || !!currentPlusCapitalError;
    }

    document.querySelectorAll('.mode-btn').forEach((b) => b.addEventListener('click', () => {
        modeInput.value = b.dataset.mode;
        render();
        suggestAmount();
        recompute();
    }));
    loanSelect.addEventListener('change', () => loadInstallments(loanSelect.value));
    amountInput.addEventListener('input', recompute);
    capitalPrepaymentInput.addEventListener('input', recompute);
    targetSelect.addEventListener('change', () => { suggestAmount(); recompute(); });
    excessPrepay.addEventListener('change', recompute);
    excessChange.addEventListener('change', recompute);

    form.addEventListener('submit', (e) => {
        form.querySelectorAll('.alloc-hidden').forEach((n) => n.remove());
        if (modeInput.value === 'custom') {
            let idx = 0;
            tableBody.querySelectorAll('.custom-amount').forEach((inp) => {
                if (inp.disabled || round2(inp.value || 0) <= 0) return;
                const mk = (name, val) => {
                    const h = document.createElement('input');
                    h.type = 'hidden'; h.name = name; h.value = val; h.className = 'alloc-hidden';
                    form.appendChild(h);
                };
                mk(`allocations[${idx}][installment_id]`, inp.dataset.id);
                mk(`allocations[${idx}][amount]`, round2(inp.value).toFixed(2));
                idx++;
            });
        }
    });

    syncCurrencySymbol();
    if (loanSelect.value) loadInstallments(loanSelect.value);
})();
</script>
@endpush
