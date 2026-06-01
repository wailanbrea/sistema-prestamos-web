@extends('layouts.app')

@include('loan-quotes.partials.labels')

@section('title', 'Cotizaciones - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Cotizaciones</h1>
                <p class="text-muted mb-0">Simula préstamos y valida cuotas antes de crear obligaciones reales.</p>
            </div>
            <a href="{{ route('loan-quotes.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i> Nueva cotización
            </a>
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('loan-quotes.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="client_id" class="form-label">Cliente</label>
                    <select id="client_id" name="client_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) ($filters['client_id'] ?? '') === (string) $client->id)>{{ $client->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($statusLabels as $value => $data)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $data['label'] }}</option>
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
                            <th>Cotización</th>
                            <th>Cliente</th>
                            <th>Frecuencia</th>
                            <th>Método</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">Cuota</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotes as $quote)
                            <tr>
                                <td>
                                    <a href="{{ route('loan-quotes.show', $quote) }}" class="fw-semibold text-decoration-none">{{ company_setting('quote_prefix', 'COT') }}-{{ str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT) }}</a>
                                    <div class="text-muted small">{{ $quote->created_at->format('d/m/Y') }}</div>
                                </td>
                                <td>{{ $quote->client?->full_name ?? 'Sin cliente' }}</td>
                                <td>{{ $frequencyLabels[$quote->payment_frequency] ?? $quote->payment_frequency }}</td>
                                <td>{{ $methodLabels[$quote->calculation_method] ?? $quote->calculation_method }}</td>
                                <td class="text-end">{{ currency() }} {{ number_format((float) $quote->amount, 2) }}</td>
                                <td class="text-end">{{ currency() }} {{ number_format((float) $quote->installment_amount, 2) }}</td>
                                <td><span class="badge {{ $statusLabels[$quote->status]['class'] ?? 'text-bg-secondary' }}">{{ $statusLabels[$quote->status]['label'] ?? $quote->status }}</span></td>
                                <td class="text-end">
                                    @can('quotes.delete')
                                        @if ($quote->status !== 'converted')
                                            <form method="POST" action="{{ route('loan-quotes.destroy', $quote) }}" onsubmit="return confirm('Eliminar esta cotizacion?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">Convertida</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">Sin permiso</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">No hay cotizaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $quotes->links() }}
            </div>
        </div>
    </section>
@endsection
