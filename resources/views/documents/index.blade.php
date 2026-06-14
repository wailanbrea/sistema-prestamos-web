@extends('layouts.app')

@section('title', 'Documentos - '.config('app.name'))

@section('content')
    @if (session('generatedDocumentId'))
        @include('documents.partials.generated-actions', ['documentId' => session('generatedDocumentId')])
    @elseif (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->has('document_share'))
        <div class="alert alert-danger">{{ $errors->first('document_share') }}</div>
    @endif

    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Documentos</h1>
        <p class="text-muted mb-0">Generacion y control de contratos, pagares, recibos, estados de cuenta y cartas de saldo.</p>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Documento de prestamo</h2>
                    <form method="POST" action="{{ route('documents.loan.generate') }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="loan_client_id" class="form-label">Cliente</label>
                            <select id="loan_client_id" class="form-select">
                                <option value="">Seleccione un cliente</option>
                                @foreach ($loans->groupBy('client_id') as $clientLoans)
                                    @php($client = $clientLoans->first()?->client)
                                    @if ($client)
                                        <option
                                            value="{{ $client->id }}"
                                            @selected((string) old('loan_client_id', old('selected_loan_client_id')) === (string) $client->id)
                                        >
                                            {{ $client->full_name }} ({{ $clientLoans->count() }} prestamos)
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="loan_id" class="form-label">Prestamo</label>
                            <select id="loan_id" name="loan_id" class="form-select @error('loan_id') is-invalid @enderror" required>
                                <option value="">Seleccione primero un cliente</option>
                                @foreach ($loans as $loan)
                                    <option
                                        value="{{ $loan->id }}"
                                        data-client-id="{{ $loan->client_id }}"
                                        data-label="{{ $loan->loan_number }} - {{ config('loan_labels.loan_statuses.'.$loan->status.'.label', $loan->status) }}"
                                        @selected((string) old('loan_id') === (string) $loan->id)
                                    >
                                        {{ $loan->loan_number }} - {{ $loan->client->full_name }} - {{ config('loan_labels.loan_statuses.'.$loan->status.'.label', $loan->status) }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="selected_loan_client_id" id="selected_loan_client_id" value="{{ old('loan_client_id', old('selected_loan_client_id')) }}">
                            @error('loan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label for="document_type" class="form-label">Tipo</label>
                            <select id="document_type" name="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                                @foreach ($loanDocumentTypes as $type)
                                    <option value="{{ $type }}" @selected(old('document_type') === $type)>@include('documents.partials.type-label', ['type' => $type])</option>
                                @endforeach
                            </select>
                            @error('document_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-file-pdf me-2"></i> Generar documento
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Recibo de pago</h2>
                    <form method="POST" action="{{ route('documents.payment-receipt.generate') }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="payment_client_id" class="form-label">Cliente</label>
                            <select id="payment_client_id" class="form-select">
                                <option value="">Seleccione un cliente</option>
                                @foreach ($payments->groupBy('client_id') as $clientPayments)
                                    @php($client = $clientPayments->first()?->client)
                                    @if ($client)
                                        <option
                                            value="{{ $client->id }}"
                                            @selected((string) old('payment_client_id', old('selected_payment_client_id')) === (string) $client->id)
                                        >
                                            {{ $client->full_name }} ({{ $clientPayments->count() }} cobros)
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="payment_id" class="form-label">Cobro</label>
                            <select id="payment_id" name="payment_id" class="form-select @error('payment_id') is-invalid @enderror" required>
                                <option value="">Seleccione primero un cliente</option>
                                @foreach ($payments as $payment)
                                    <option
                                        value="{{ $payment->id }}"
                                        data-client-id="{{ $payment->client_id }}"
                                        data-label="{{ $payment->receipt_number }} - {{ $payment->payment_date?->format('d/m/Y') }} - {{ currency() }} {{ number_format((float) $payment->amount, 2) }}"
                                        @selected((string) old('payment_id') === (string) $payment->id)
                                    >
                                        {{ $payment->receipt_number }} - {{ $payment->client->full_name }} - {{ currency() }} {{ number_format((float) $payment->amount, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="selected_payment_client_id" id="selected_payment_client_id" value="{{ old('payment_client_id', old('selected_payment_client_id')) }}">
                            @error('payment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fa-solid fa-receipt me-2"></i> Generar recibo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('documents.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="search" class="form-label">Buscar</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Titulo, cliente o prestamo">
                </div>
                <div class="col-12 col-md-4">
                    <label for="document_type_filter" class="form-label">Tipo</label>
                    <select id="document_type_filter" name="document_type" class="form-select">
                        <option value="">Todos</option>
                        @foreach (array_merge($loanDocumentTypes, ['payment_receipt']) as $type)
                            <option value="{{ $type }}" @selected(($filters['document_type'] ?? '') === $type)>@include('documents.partials.type-label', ['type' => $type])</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Tipo</th>
                            <th>Cliente</th>
                            <th>Prestamo</th>
                            <th>Generado por</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $document)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $document->title }}</div>
                                    <div class="text-muted small">{{ $document->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>@include('documents.partials.type-label', ['type' => $document->document_type])</td>
                                <td>{{ $document->client?->full_name ?: 'N/A' }}</td>
                                <td>{{ $document->loan?->loan_number ?: 'N/A' }}</td>
                                <td>{{ $document->createdBy?->name ?: 'Sistema' }}</td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-download me-2"></i>Descargar
                                        </a>
                                        <a href="{{ route('documents.whatsapp', $document) }}" class="btn btn-sm btn-outline-success" target="_blank" rel="noopener">
                                            <i class="fa-brands fa-whatsapp me-2"></i>Enviar por WS
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">No hay documentos generados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $documents->links() }}
            </div>
        </div>
    </section>

    <script>
        (() => {
            const wireDependentSelect = (clientSelectId, itemSelectId, hiddenInputId, emptyText, selectedText) => {
                const clientSelect = document.getElementById(clientSelectId);
                const itemSelect = document.getElementById(itemSelectId);
                const hiddenClientInput = document.getElementById(hiddenInputId);

                if (!clientSelect || !itemSelect || !hiddenClientInput) {
                    return;
                }

                const itemOptions = Array.from(itemSelect.querySelectorAll('option'))
                    .filter((option) => option.value !== '')
                    .map((option) => ({
                        value: option.value,
                        clientId: option.dataset.clientId || '',
                        label: option.dataset.label || option.textContent.trim(),
                    }));

                const rebuild = () => {
                    const selectedClientId = clientSelect.value;
                    const previousItemId = itemSelect.value;

                    itemSelect.innerHTML = '';

                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = selectedClientId === '' ? emptyText : selectedText;
                    itemSelect.appendChild(placeholder);

                    itemOptions
                        .filter((option) => option.clientId === selectedClientId)
                        .forEach((option) => {
                            const node = document.createElement('option');
                            node.value = option.value;
                            node.textContent = option.label;
                            if (option.value === previousItemId) {
                                node.selected = true;
                            }
                            itemSelect.appendChild(node);
                        });

                    if (itemSelect.value === '' && previousItemId !== '') {
                        const fallback = itemOptions.find((option) => option.value === previousItemId && option.clientId === selectedClientId);
                        if (fallback) {
                            itemSelect.value = fallback.value;
                        }
                    }

                    hiddenClientInput.value = selectedClientId;
                };

                clientSelect.addEventListener('change', rebuild);

                const selectedItem = itemOptions.find((option) => option.value === itemSelect.value);
                if (selectedItem && clientSelect.value === '') {
                    clientSelect.value = selectedItem.clientId;
                }

                rebuild();
            };

            wireDependentSelect(
                'loan_client_id',
                'loan_id',
                'selected_loan_client_id',
                'Seleccione primero un cliente',
                'Seleccione un prestamo'
            );

            wireDependentSelect(
                'payment_client_id',
                'payment_id',
                'selected_payment_client_id',
                'Seleccione primero un cliente',
                'Seleccione un cobro'
            );
        })();
    </script>
@endsection
