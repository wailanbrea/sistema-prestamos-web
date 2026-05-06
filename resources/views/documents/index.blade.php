@extends('layouts.app')

@section('title', 'Documentos - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Documentos</h1>
        <p class="text-muted mb-0">Generación y control de pagarés, comprobantes, recibos y cartas de saldo.</p>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Documento de préstamo</h2>
                    <form method="POST" action="{{ route('documents.loan.generate') }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="loan_id" class="form-label">Préstamo</label>
                            <select id="loan_id" name="loan_id" class="form-select @error('loan_id') is-invalid @enderror" required>
                                <option value="">Seleccione un préstamo</option>
                                @foreach ($loans as $loan)
                                    <option value="{{ $loan->id }}" @selected((string) old('loan_id') === (string) $loan->id)>
                                        {{ $loan->loan_number }} · {{ $loan->client->full_name }} · {{ $loan->status }}
                                    </option>
                                @endforeach
                            </select>
                            @error('loan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label for="document_type" class="form-label">Tipo</label>
                            <select id="document_type" name="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                                @foreach (['promissory_note', 'disbursement_receipt', 'balance_letter'] as $type)
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
                            <label for="payment_id" class="form-label">Cobro</label>
                            <select id="payment_id" name="payment_id" class="form-select @error('payment_id') is-invalid @enderror" required>
                                <option value="">Seleccione un cobro</option>
                                @foreach ($payments as $payment)
                                    <option value="{{ $payment->id }}" @selected((string) old('payment_id') === (string) $payment->id)>
                                        {{ $payment->receipt_number }} · {{ $payment->client->full_name }} · RD$ {{ number_format((float) $payment->amount, 2) }}
                                    </option>
                                @endforeach
                            </select>
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
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Título, cliente o préstamo">
                </div>
                <div class="col-12 col-md-4">
                    <label for="document_type_filter" class="form-label">Tipo</label>
                    <select id="document_type_filter" name="document_type" class="form-select">
                        <option value="">Todos</option>
                        @foreach (['promissory_note', 'disbursement_receipt', 'payment_receipt', 'balance_letter'] as $type)
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
                            <th>Préstamo</th>
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
                                    <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-download me-2"></i> Descargar
                                    </a>
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
@endsection
