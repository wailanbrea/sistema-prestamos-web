@extends('layouts.app')

@section('title', 'Nuevo cobro - '.config('app.name'))

@section('content')
    <style>
        .mode-btn { border-radius: 10px; line-height: 1.2; }
        .mode-btn .mode-title { font-size: .82rem; }
        .mode-btn .mode-desc { font-size: .68rem; color: var(--app-muted); display: block; }
        .mode-btn i { font-size: 1rem; }
        .mode-btn.active { background: var(--app-primary); border-color: var(--app-primary); }
        .mode-btn.active, .mode-btn.active * { color: #fff !important; }
    </style>

    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Nuevo cobro</h1>
                <p class="text-muted mb-0">Elige el préstamo, el modo de reparto y revisa la distribución antes de confirmar.</p>
            </div>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </section>

    <form method="POST" action="{{ route('payments.store') }}" id="paymentForm"
          data-installments-url="{{ route('payments.loan-installments', ['loan' => '__LOAN__']) }}">
        @csrf

        <div class="row g-3">
            <div class="col-12 col-xl-7">
                <section class="card content-card mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="loan_id" class="form-label">Préstamo</label>
                                <select id="loan_id" name="loan_id" class="form-select @error('loan_id') is-invalid @enderror" required>
                                    <option value="">Seleccione un préstamo activo</option>
                                    @foreach ($loans as $loan)
                                        <option value="{{ $loan->id }}" @selected((string) old('loan_id', $selectedLoan->id ?? '') === (string) $loan->id)>
                                            {{ $loan->loan_number }} · {{ $loan->client->full_name }} · balance {{ currency() }} {{ number_format((float) $loan->remaining_balance, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('loan_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Cuotas pendientes --}}
                <section class="card content-card mb-3" id="installmentsCard" style="display:none;">
                    <div class="card-header bg-white border-0 pb-0">
                        <h2 class="h6 fw-bold mb-1">Cuotas pendientes</h2>
                        <p class="text-muted small mb-0">Lo adeudado por cuota a la fecha del pago.</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="installmentsTable">
                                <thead>
                                    <tr>
                                        <th class="text-center custom-col" style="display:none;">Pagar</th>
                                        <th>#</th>
                                        <th>Vence</th>
                                        <th class="text-end">Capital</th>
                                        <th class="text-end">Interés</th>
                                        <th class="text-end">Mora</th>
                                        <th class="text-end">Total</th>
                                        <th>Estado</th>
                                        <th class="text-end custom-col" style="display:none;">Monto a pagar</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 mt-2 fw-semibold" id="totalPendingRow" style="display:none;">
                            <span>Saldo pendiente actual</span><span id="totalPendingNow">{{ currency() }} 0.00</span>
                        </div>
                        <p class="text-muted small mb-0 mt-2" id="noInstallments" style="display:none;">Este préstamo no tiene cuotas pendientes.</p>
                    </div>
                </section>

                {{-- Modo de reparto --}}
                <section class="card content-card mb-3" id="modeCard" style="display:none;">
                    <div class="card-header bg-white border-0 pb-0">
                        <h2 class="h6 fw-bold mb-1">Modo de reparto</h2>
                        <p class="text-muted small mb-0">Cómo se aplica el dinero recibido.</p>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="allocation_mode" id="allocation_mode" value="{{ old('allocation_mode', 'auto') }}">
                        <div class="row g-2 mb-3" id="modeOptions">
                            @php
                                $modes = [
                                    'auto' => ['Automático', 'Mora → interés → capital, en orden.', 'fa-wand-magic-sparkles'],
                                    'interest_only' => ['Solo interés', 'Aplica únicamente al interés. El balance no baja.', 'fa-percent'],
                                    'principal_only' => ['Solo capital', 'Aplica únicamente al capital. Baja el balance; interés/mora siguen pendientes.', 'fa-sack-dollar'],
                                    'custom' => ['Personalizado', 'Elige cuánto pagar en cada cuota.', 'fa-sliders'],
                                ];
                            @endphp
                            @foreach ($modes as $value => $info)
                                <div class="col-6 col-lg-3">
                                    <button type="button" class="btn btn-outline-secondary w-100 h-100 text-start mode-btn p-2" data-mode="{{ $value }}">
                                        <i class="fa-solid {{ $info[2] }} mb-1 d-block"></i>
                                        <span class="fw-semibold d-block mode-title">{{ $info[0] }}</span>
                                        <span class="mode-desc">{{ $info[1] }}</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        {{-- Monto + cuota destino (modos no personalizados) --}}
                        <div id="pooledControls" class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="amount" class="form-label">Monto a pagar</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ currency() }}</span>
                                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror">
                                </div>
                                @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="target_installment_id" class="form-label">Aplicar a</label>
                                <select id="target_installment_id" name="target_installment_id" class="form-select">
                                    <option value="">Todas las cuotas pendientes (en orden)</option>
                                </select>
                            </div>
                        </div>

                        <div id="excessPanel" class="mt-3" style="display:none;">
                            <div class="alert alert-secondary mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">Excedente sobre lo adeudado</span>
                                    <strong id="excessAmount">{{ currency() }} 0.00</strong>
                                </div>
                                <div class="form-check" id="prepaymentOption">
                                    <input class="form-check-input" type="radio" name="excess_action" id="excessPrepay" value="prepayment">
                                    <label class="form-check-label" for="excessPrepay">Abonar a capital <span class="text-muted">(recalcula las cuotas restantes)</span></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="excess_action" id="excessChange" value="change">
                                    <label class="form-check-label" for="excessChange">Entregar como vuelto al cliente</label>
                                </div>
                                <div id="prepayDisabledNote" class="small text-muted mt-1" style="display:none;">
                                    <i class="fa-solid fa-circle-info me-1"></i> Este préstamo no permite abono a capital.
                                </div>
                            </div>
                        </div>

                        <div id="customHint" class="alert alert-info small mb-0 mt-2" style="display:none;">
                            <i class="fa-solid fa-circle-info me-1"></i> Marca las cuotas y escribe cuánto pagar en cada una. El monto se distribuye mora → interés → capital dentro de cada cuota.
                        </div>

                        <div id="modeWarning" class="alert alert-warning small mb-0 mt-2" style="display:none;"></div>
                    </div>
                </section>
            </div>

            {{-- Resumen lateral --}}
            <div class="col-12 col-xl-5">
                <section class="card content-card mb-3" id="previewCard" style="display:none;">
                    <div class="card-header bg-white border-0 pb-0">
                        <h2 class="h6 fw-bold mb-1">Distribución del pago</h2>
                        <p class="text-muted small mb-0">Cálculo estimado (el servidor confirma al guardar).</p>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Capital</span><strong id="pvPrincipal">{{ currency() }} 0.00</strong></div>
                        <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Interés</span><strong id="pvInterest">{{ currency() }} 0.00</strong></div>
                        <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Mora</span><strong id="pvLate">{{ currency() }} 0.00</strong></div>
                        <div class="d-flex justify-content-between border-bottom py-2" id="pvPrepayRow" style="display:none;"><span class="text-muted">Abono a capital</span><strong id="pvPrepay">{{ currency() }} 0.00</strong></div>
                        <div class="d-flex justify-content-between py-2 fs-5"><span>Total cobrado</span><strong id="pvTotal" class="text-primary">{{ currency() }} 0.00</strong></div>
                        <div class="d-flex justify-content-between py-2" id="pvChangeRow" style="display:none;"><span class="text-success fw-semibold">Vuelto al cliente</span><strong id="pvChange" class="text-success">{{ currency() }} 0.00</strong></div>
                        <div class="d-flex justify-content-between border-top pt-2 mt-1"><span class="text-muted">Saldo pendiente después</span><strong id="pvBalance">{{ currency() }} 0.00</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted small">Balance de capital después</span><span class="text-muted small" id="pvCapitalBalance">{{ currency() }} 0.00</span></div>
                        <div id="pvLeftover" class="alert alert-warning small mt-3 mb-0" style="display:none;"></div>
                    </div>
                </section>

                <section class="card content-card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="payment_date" class="form-label">Fecha de pago</label>
                                <input id="payment_date" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control @error('payment_date') is-invalid @enderror" required>
                                @error('payment_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="payment_method" class="form-label">Método</label>
                                <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                    @foreach (config('loan_labels.payment_methods') as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            @if (\App\Support\MenuAccess::planAllowsMenu(auth()->user(), 'collectors.index'))
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
                                @error('collector_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="fa-solid fa-cash-register me-2"></i> Registrar cobro
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
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
    const prepayDisabledNote = document.getElementById('prepayDisabledNote');

    let installments = [];
    let balance = 0;
    let allowsPrepayment = false;

    const money = (n) => @json(currency().' ') + Number(n || 0).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

    async function loadInstallments(loanId) {
        installments = [];
        if (!loanId) { render(); return; }
        try {
            const res = await fetch(urlTpl.replace('__LOAN__', loanId), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('fetch');
            const data = await res.json();
            balance = Number(data.remaining_balance || 0);
            allowsPrepayment = !!data.allows_capital_prepayment;
            installments = data.installments || [];
        } catch (e) {
            installments = [];
        }
        buildTargetOptions();
        render();
        suggestAmount();
        recompute();
    }

    // Rellena el monto sugerido según el modo y la cuota destino (editable por el usuario).
    function suggestAmount() {
        if (modeInput.value === 'custom') return;
        const mode = modeInput.value;
        const pick = (i) => mode === 'interest_only' ? i.interest_due : (mode === 'principal_only' ? i.principal_due : i.total_due);
        const targetId = targetSelect.value;
        let val = 0;
        if (targetId) {
            const inst = installments.find((x) => x.id == targetId);
            if (inst) val = pick(inst);
        } else {
            val = installments.reduce((s, i) => s + pick(i), 0);
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
        const map = { pending: ['Pendiente','text-bg-secondary'], partial:['Parcial','text-bg-info'], late:['Atrasada','text-bg-warning'], paid:['Pagada','text-bg-success'], cancelled:['Cancelada','text-bg-dark'] };
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
        noInstallments.style.display = (loanSelect.value && !hasInstallments) ? '' : 'none';
        customCols.forEach((c) => c.style.display = isCustom ? '' : 'none');

        const totalPending = installments.reduce((s, i) => s + Number(i.total_due || 0), 0);
        document.getElementById('totalPendingRow').style.display = hasInstallments ? '' : 'none';
        document.getElementById('totalPendingNow').textContent = money(totalPending);

        tableBody.innerHTML = '';
        installments.forEach((i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center custom-col" style="${isCustom ? '' : 'display:none'}">
                    <input type="checkbox" class="form-check-input custom-check" data-id="${i.id}">
                </td>
                <td>${i.number}</td>
                <td>${i.due_date}</td>
                <td class="text-end">${money(i.principal_due)}</td>
                <td class="text-end">${money(i.interest_due)}</td>
                <td class="text-end">${money(i.late_due)}</td>
                <td class="text-end fw-semibold">${money(i.total_due)}</td>
                <td>${statusBadge(i.status)}</td>
                <td class="text-end custom-col" style="${isCustom ? '' : 'display:none'}">
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end custom-amount"
                           data-id="${i.id}" style="max-width:130px; margin-left:auto;" disabled placeholder="0.00">
                </td>`;
            tableBody.appendChild(tr);
        });

        // wire custom inputs
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

        // mode buttons active state
        document.querySelectorAll('.mode-btn').forEach((b) => {
            b.classList.toggle('active', b.dataset.mode === mode);
        });
    }

    // Simula el reparto para el preview (el servidor es la verdad).
    function recompute() {
        const mode = modeInput.value;
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
            const buckets = mode === 'interest_only' ? ['interest'] : (mode === 'principal_only' ? ['principal'] : ['late','interest','principal']);
            let avail = round2(amountInput.value || 0);
            const targetId = targetSelect.value;
            const queue = targetId ? installments.filter((i) => i.id == targetId) : installments;
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

        // Excedente: abono a capital o vuelto.
        let prepay = 0, change = 0;
        if (leftover > 0.001) {
            excessPanel.style.display = '';
            excessAmount.textContent = money(leftover);
            prepaymentOption.style.display = allowsPrepayment ? '' : 'none';
            prepayDisabledNote.style.display = allowsPrepayment ? 'none' : '';
            if (!allowsPrepayment && excessPrepay.checked) { excessPrepay.checked = false; excessChange.checked = true; }
            if (!excessPrepay.checked && !excessChange.checked) {
                if (allowsPrepayment) excessPrepay.checked = true; else excessChange.checked = true;
            }
            if (excessPrepay.checked) {
                const outstandingAfter = Math.max(0, round2(balance - principal));
                prepay = round2(Math.min(leftover, outstandingAfter));
                change = round2(leftover - prepay);
            } else {
                change = leftover;
            }
        } else {
            excessPanel.style.display = 'none';
            excessPrepay.checked = false;
            excessChange.checked = false;
        }

        const charged = round2(principal + interest + late + prepay);
        const applied = round2(principal + interest + late);
        const totalPending = installments.reduce((s, i) => s + Number(i.total_due || 0), 0);
        // Saldo total pendiente después = lo que el cliente aún debe (capital + interés + mora) menos lo aplicado y el abono.
        const pendingAfter = Math.max(0, round2(totalPending - applied - prepay));
        const capitalAfter = Math.max(0, round2(balance - principal - prepay));

        document.getElementById('pvPrincipal').textContent = money(principal);
        document.getElementById('pvInterest').textContent = money(interest);
        document.getElementById('pvLate').textContent = money(late);
        document.getElementById('pvTotal').textContent = money(charged);
        document.getElementById('pvBalance').textContent = money(pendingAfter);
        document.getElementById('pvCapitalBalance').textContent = money(capitalAfter);

        const prepayRow = document.getElementById('pvPrepayRow');
        prepayRow.style.display = prepay > 0 ? '' : 'none';
        document.getElementById('pvPrepay').textContent = money(prepay);
        const changeRow = document.getElementById('pvChangeRow');
        changeRow.style.display = change > 0 ? '' : 'none';
        document.getElementById('pvChange').textContent = money(change);

        document.getElementById('pvLeftover').style.display = 'none';

        // warnings
        let warn = '';
        if (mode === 'principal_only' && (principal + prepay) > 0) {
            warn = 'Pago a capital: el interés y la mora de esas cuotas quedarán pendientes y la cuota no se marcará como Pagada.';
        } else if (mode === 'interest_only' && interest > 0 && prepay === 0) {
            warn = 'Pago solo a interés: el balance de capital no baja.';
        }
        modeWarning.style.display = warn ? '' : 'none';
        modeWarning.textContent = warn;

        submitBtn.disabled = applied <= 0;
    }

    document.querySelectorAll('.mode-btn').forEach((b) => b.addEventListener('click', () => {
        modeInput.value = b.dataset.mode;
        render();
        suggestAmount();
        recompute();
    }));
    loanSelect.addEventListener('change', () => loadInstallments(loanSelect.value));
    amountInput.addEventListener('input', recompute);
    targetSelect.addEventListener('change', () => { suggestAmount(); recompute(); });
    excessPrepay.addEventListener('change', recompute);
    excessChange.addEventListener('change', recompute);

    // On submit: inject allocations[] hidden inputs for custom mode.
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

    // Preselect loan if provided
    if (loanSelect.value) loadInstallments(loanSelect.value);
})();
</script>
@endpush
