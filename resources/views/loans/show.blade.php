@extends('layouts.app')

@include('loans.partials.labels')

@section('title', $loan->loan_number.' - '.config('app.name'))

@section('content')
    @php
        $loanCurrency = $loan->currency ?? currency();
    @endphp
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $loan->loan_number }}</h1>
                <p class="text-muted mb-0">{{ $loan->client->full_name }} - {{ $frequencyLabels[$loan->payment_frequency] ?? $loan->payment_frequency }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('loans.approve')
                    @if ($loan->status === 'pending')
                        <form action="{{ route('loans.approve', $loan) }}" method="POST" onsubmit="return confirm('Aprobar este prestamo? Se desembolsara en caja.');">
                            @csrf
                            <button type="submit" class="btn btn-success"><i class="fa-solid fa-check me-2"></i>Aprobar</button>
                        </form>
                    @endif
                @endcan
                @can('payments.create')
                    @if (in_array($loan->status, ['active', 'late'], true))
                        <a href="{{ route('payments.create', ['loan_id' => $loan->id]) }}" class="btn btn-outline-primary"><i class="fa-solid fa-cash-register me-2"></i>Cobrar</a>
                    @endif
                @endcan
                @can('loans.update')
                    <a href="{{ route('loans.edit', $loan) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-pen me-2"></i>Editar</a>
                @endcan
                @can('loans.delete')
                    <form action="{{ route('loans.destroy', $loan) }}" method="POST" onsubmit="return confirm('Eliminar este prestamo? Solo es posible si no tiene pagos registrados.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-trash me-2"></i>Eliminar</button>
                    </form>
                @endcan
                <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </section>

    @if ($errors->has('loan'))
        <div class="alert alert-danger">{{ $errors->first('loan') }}</div>
    @endif

    @if ($errors->has('loan_document'))
        <div class="alert alert-danger">{{ $errors->first('loan_document') }}</div>
    @endif

    @if ($errors->has('document_share'))
        <div class="alert alert-danger">{{ $errors->first('document_share') }}</div>
    @endif

    @if (session('generatedDocumentId'))
        @include('documents.partials.generated-actions', ['documentId' => session('generatedDocumentId')])
    @elseif (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <section class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-2"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Principal</div><div class="h4 fw-bold mb-0">{{ $loanCurrency }} {{ number_format((float) $loan->principal_amount, 2) }}</div></div></article></div>
        <div class="col-12 col-md-6 col-xl-2"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Ganancia esperada</div><div class="h4 fw-bold mb-0 text-success">{{ $loanCurrency }} {{ number_format((float) $loan->total_interest, 2) }}</div></div></article></div>
        <div class="col-12 col-md-6 col-xl-2"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Total a cobrar</div><div class="h4 fw-bold mb-0">{{ $loanCurrency }} {{ number_format((float) $loan->total_amount, 2) }}</div></div></article></div>
        <div class="col-12 col-md-6 col-xl-2"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Balance</div><div class="h4 fw-bold mb-0">{{ $loanCurrency }} {{ number_format((float) $loan->remaining_balance, 2) }}</div></div></article></div>
        <div class="col-12 col-md-6 col-xl-2"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Cuota</div><div class="h4 fw-bold mb-0">{{ $loanCurrency }} {{ number_format((float) $loan->installment_amount, 2) }}</div></div></article></div>
        <div class="col-12 col-md-6 col-xl-2"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Estado</div><div><span class="badge {{ $loanStatusLabels[$loan->status]['class'] ?? 'text-bg-secondary' }}">{{ $loanStatusLabels[$loan->status]['label'] ?? $loan->status }}</span></div></div></article></div>
    </section>

    @if (($financialSummary['overdue_count'] ?? 0) > 0)
        <section class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <article class="card metric-card border-danger" style="border-width: 1px;">
                    <div class="card-body">
                        <div class="text-danger small fw-semibold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Cuotas vencidas</div>
                        <div class="h3 fw-bold mb-0 text-danger">{{ $loanCurrency }} {{ number_format((float) $financialSummary['overdue_total'], 2) }}</div>
                        <div class="text-muted small">{{ $financialSummary['overdue_count'] }} {{ $financialSummary['overdue_count'] == 1 ? 'cuota sin saldar' : 'cuotas sin saldar' }}</div>
                    </div>
                </article>
            </div>
            <div class="col-12 col-md-4">
                <article class="card metric-card border-warning" style="border-width: 1px;">
                    <div class="card-body">
                        <div class="text-warning small fw-semibold"><i class="fa-solid fa-clock me-2"></i>Mora</div>
                        <div class="h3 fw-bold mb-0 text-warning">{{ $loanCurrency }} {{ number_format((float) $financialSummary['overdue_late_fee'], 2) }}</div>
                        <div class="text-muted small">Mora pendiente acumulada</div>
                    </div>
                </article>
            </div>
            <div class="col-12 col-md-4">
                <article class="card metric-card text-bg-danger">
                    <div class="card-body">
                        <div class="small fw-semibold"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Total a pagar hoy</div>
                        <div class="h3 fw-bold mb-0">{{ $loanCurrency }} {{ number_format((float) $financialSummary['total_due_today'], 2) }}</div>
                        <div class="small opacity-75">Cuotas vencidas + mora</div>
                    </div>
                </article>
            </div>
        </section>
    @endif

    <section class="row g-3 mb-4">
        <div class="col-12 col-md-4"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Capital cobrado</div><div class="h4 fw-bold mb-0">{{ $loanCurrency }} {{ number_format((float) $financialSummary['principal_collected'], 2) }}</div></div></article></div>
        <div class="col-12 col-md-4"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Capital pendiente</div><div class="h4 fw-bold mb-0 text-warning">{{ $loanCurrency }} {{ number_format((float) $financialSummary['principal_pending'], 2) }}</div></div></article></div>
        <div class="col-12 col-md-4"><article class="card metric-card"><div class="card-body"><div class="text-muted small">% recuperado del capital</div><div class="h4 fw-bold mb-0">{{ number_format((float) $financialSummary['principal_recovery_rate'], 2) }}%</div></div></article></div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-12 col-md-4"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Ganancia ya cobrada</div><div class="h4 fw-bold mb-0 text-success">{{ $loanCurrency }} {{ number_format((float) $financialSummary['interest_collected'], 2) }}</div></div></article></div>
        <div class="col-12 col-md-4"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Ganancia pendiente</div><div class="h4 fw-bold mb-0 text-warning">{{ $loanCurrency }} {{ number_format((float) $financialSummary['interest_pending'], 2) }}</div></div></article></div>
        <div class="col-12 col-md-4"><article class="card metric-card"><div class="card-body"><div class="text-muted small">Mora cobrada</div><div class="h4 fw-bold mb-0">{{ $loanCurrency }} {{ number_format((float) $financialSummary['late_fee_collected'], 2) }}</div></div></article></div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-12">
            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Documentos e impresion</h2>
                    <p class="text-muted small mb-0">Genera o reutiliza los PDFs oficiales del prestamo sin salir del expediente.</p>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach ([
                            'promissory_note',
                            'loan_contract',
                            'disbursement_receipt',
                            'account_statement',
                        ] as $type)
                            <div class="col-12 col-md-6 col-xl-3">
                                <form action="{{ route('loans.documents.generate', $loan) }}" method="POST" class="d-grid">
                                    @csrf
                                    <input type="hidden" name="document_type" value="{{ $type }}">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fa-solid fa-file-pdf me-2"></i>@include('documents.partials.type-label', ['type' => $type])
                                    </button>
                                </form>
                            </div>
                        @endforeach

                        @if ($loan->status === 'paid')
                            <div class="col-12 col-md-6 col-xl-3">
                                <form action="{{ route('loans.documents.generate', $loan) }}" method="POST" class="d-grid">
                                    @csrf
                                    <input type="hidden" name="document_type" value="balance_letter">
                                    <button type="submit" class="btn btn-outline-success">
                                        <i class="fa-solid fa-file-signature me-2"></i>@include('documents.partials.type-label', ['type' => 'balance_letter'])
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        </div>
    </section>

    @can('legal.manage')
        @php
            $latestContract = $loan->contracts()->latest('id')->first();
        @endphp
        <section class="row g-3 mb-3">
            <div class="col-12">
                <article class="card content-card">
                    <div class="card-header bg-white border-0">
                        <h2 class="h5 fw-bold mb-1"><i class="fa-solid fa-file-contract me-2"></i>Contrato digital</h2>
                        <p class="text-muted small mb-0">Genera el contrato, envíalo por WhatsApp y permite la firma electrónica del cliente desde su celular.</p>
                    </div>
                    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                        @if ($loan->contract_required && ! $loan->contract_signed)
                            <span class="badge bg-warning text-dark">Requiere contrato firmado para desembolsar</span>
                        @elseif ($loan->contract_signed)
                            <span class="badge bg-success">Contrato firmado</span>
                        @endif

                        @if ($latestContract)
                            <a href="{{ route('contracts.show', $latestContract->uuid) }}" class="btn btn-primary">
                                Ver contrato {{ $latestContract->contract_number }}
                            </a>
                        @else
                            <form action="{{ route('contracts.generate', $loan) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="contract_type" value="loan_contract">
                                <button type="submit" class="btn btn-outline-primary">Generar contrato digital</button>
                            </form>
                        @endif
                    </div>
                </article>
            </div>
        </section>
    @endcan

    <section class="row g-3">
        <div class="col-12 col-xl-8">
            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Cuotas generadas</h2>
                    <p class="text-muted small mb-0">Plan de pago oficial del prestamo.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Vencimiento</th>
                                    <th class="text-end">Capital pendiente</th>
                                    <th class="text-end">Interes pendiente</th>
                                    <th class="text-end">Mora</th>
                                    <th class="text-end">Pendiente</th>
                                    <th>Estado</th>
                                    @can('loans.update')
                                        <th class="text-end">Acciones</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($loan->installments as $installment)
                                    @php
                                        // Estado efectivo: si ya venció y no está saldada, se muestra "Vencida"
                                        // aunque la BD aún tenga "pending" (la mora la marca un proceso aparte).
                                        $effectiveStatus = (! in_array($installment->status, ['paid', 'cancelled'], true) && $installment->due_date->lt(now()->startOfDay()))
                                            ? 'late'
                                            : $installment->status;
                                        $pendingPrincipal = max(0, (float) $installment->principal_amount - (float) $installment->paid_principal);
                                        $pendingInterest = max(0, (float) $installment->interest_amount - (float) $installment->paid_interest);
                                        $pendingLateFee = max(0, (float) $installment->late_fee - (float) $installment->paid_late_fee);
                                        $pendingInstallmentTotal = $pendingPrincipal + $pendingInterest + $pendingLateFee;
                                    @endphp
                                    <tr>
                                        <td>{{ $installment->installment_number }}</td>
                                        <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ $loanCurrency }} {{ number_format($pendingPrincipal, 2) }}</td>
                                        <td class="text-end">{{ $loanCurrency }} {{ number_format($pendingInterest, 2) }}</td>
                                        <td class="text-end">{{ $loanCurrency }} {{ number_format($pendingLateFee, 2) }}</td>
                                        <td class="text-end fw-semibold">{{ $loanCurrency }} {{ number_format($pendingInstallmentTotal, 2) }}</td>
                                        <td>@include('partials.status-badge', ['map' => 'installment_statuses', 'value' => $effectiveStatus])</td>
                                        @can('loans.update')
                                            <td class="text-end">
                                                @if ($pendingLateFee > 0 && ! in_array($installment->status, ['paid', 'cancelled'], true))
                                                    <form method="POST" action="{{ route('loans.installments.late-fee.destroy', [$loan, $installment]) }}" onsubmit="return confirm('Eliminar la mora pendiente de la cuota #{{ $installment->installment_number }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fa-solid fa-ban me-1"></i>Quitar mora
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>
        <div class="col-12 col-xl-4">
            @can('loans.update')
                <article class="card content-card mb-3">
                    <div class="card-header bg-white border-0 pb-0">
                        <h2 class="h6 fw-bold mb-1">Gestionar mora</h2>
                        <p class="text-muted small mb-0">Modifica la mora del prestamo y recalcula las cuotas pendientes.</p>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                            <span class="text-muted">Mora pendiente</span>
                            <strong class="text-warning">{{ $loanCurrency }} {{ number_format((float) $financialSummary['overdue_late_fee'], 2) }}</strong>
                        </div>

                        <form method="POST" action="{{ route('loans.late-fee.update', $loan) }}" class="row g-2">
                            @csrf
                            @method('PATCH')
                            <div class="col-12">
                                <label for="late_fee_type" class="form-label small text-muted mb-1">Tipo de mora</label>
                                <select id="late_fee_type" name="late_fee_type" class="form-select @error('late_fee_type') is-invalid @enderror">
                                    <option value="none" @selected(old('late_fee_type', $loan->late_fee_type) === 'none')>Sin mora</option>
                                    <option value="fixed" @selected(old('late_fee_type', $loan->late_fee_type) === 'fixed')>Fija</option>
                                    <option value="daily_percentage" @selected(old('late_fee_type', $loan->late_fee_type) === 'daily_percentage')>Porcentaje diario</option>
                                    <option value="daily_fixed" @selected(old('late_fee_type', $loan->late_fee_type) === 'daily_fixed')>Monto diario</option>
                                </select>
                                @error('late_fee_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="late_fee_value" class="form-label small text-muted mb-1">Valor de mora</label>
                                <input id="late_fee_value" name="late_fee_value" type="number" step="0.01" min="0" value="{{ old('late_fee_value', rtrim(rtrim(number_format((float) $loan->late_fee_value, 2, '.', ''), '0'), '.')) }}" class="form-control @error('late_fee_value') is-invalid @enderror">
                                @error('late_fee_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fa-solid fa-rotate me-2"></i>Actualizar mora
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('loans.late-fee.update', $loan) }}" class="d-grid mt-2" onsubmit="return confirm('Quitar la mora de este prestamo? La mora pendiente quedara en cero y no se generara mora futura.');">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="late_fee_type" value="none">
                            <input type="hidden" name="late_fee_value" value="0">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fa-solid fa-ban me-2"></i>Quitar mora
                            </button>
                        </form>
                    </div>
                </article>
            @endcan

            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Condiciones</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Cobrador</span><strong>{{ $loan->collector?->name ?? 'Sin cobrador' }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Moneda</span><strong>{{ $loanCurrency }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Metodo</span><strong>{{ $methodLabels[$loan->calculation_method] ?? $loan->calculation_method }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Tasa</span><strong>{{ rtrim(rtrim(number_format((float) $loan->interest_rate, 4, '.', ''), '0'), '.') ?: '0' }}%</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Ganancia esperada</span><strong class="text-success">{{ $loanCurrency }} {{ number_format((float) $loan->total_interest, 2) }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Total contratado</span><strong>{{ $loanCurrency }} {{ number_format((float) $loan->total_amount, 2) }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Inicio</span><strong>{{ $loan->start_date->format('d/m/Y') }}</strong></div>
                    <div class="d-flex justify-content-between py-3"><span class="text-muted">Primer pago</span><strong>{{ $loan->first_payment_date->format('d/m/Y') }}</strong></div>
                </div>
            </article>
        </div>
    </section>
@endsection
