@extends('layouts.app')

@include('loans.partials.labels')

@section('title', 'Prestamos eliminados - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Prestamos eliminados</h1>
                <p class="text-muted mb-0">Recupera prestamos enviados a la papelera.</p>
            </div>
            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </section>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <section class="card content-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Prestamo</th>
                            <th>Cliente</th>
                            <th class="text-end">Monto</th>
                            <th>Estado previo</th>
                            <th>Eliminado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loans as $loan)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $loan->loan_number }}</span>
                                    <div class="text-muted small">{{ $loan->currency ?? currency() }} {{ number_format((float) $loan->remaining_balance, 2) }} pendiente</div>
                                </td>
                                <td>{{ $loan->client?->full_name ?: 'Cliente no disponible' }}</td>
                                <td class="text-end">{{ $loan->currency ?? currency() }} {{ number_format((float) $loan->principal_amount, 2) }}</td>
                                <td><span class="badge {{ $loanStatusLabels[$loan->status]['class'] ?? 'text-bg-secondary' }}">{{ $loanStatusLabels[$loan->status]['label'] ?? $loan->status }}</span></td>
                                <td>{{ $loan->deleted_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <form action="{{ route('loans.restore', $loan->id) }}" method="POST" onsubmit="return confirm('Recuperar este prestamo? Volvera a aparecer con sus cuotas, pagos y documentos.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-rotate-left me-2"></i>Recuperar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">No hay prestamos eliminados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $loans->links() }}</div>
        </div>
    </section>
@endsection
