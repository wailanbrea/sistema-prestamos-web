@extends('layouts.app')

@section('title', 'Cuentas por pagar - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Cuentas por pagar</h1>
                <p class="text-muted mb-0">Prestamos que toma la empresa y calendario de pago a acreedores.</p>
            </div>
            <a href="{{ route('accounts-payable.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i> Nueva cuenta
            </a>
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('accounts-payable.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label for="search" class="form-label">Buscar</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Referencia o acreedor">
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="creditor_id" class="form-label">Acreedor</label>
                    <select id="creditor_id" name="creditor_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($creditors as $creditor)
                            <option value="{{ $creditor->id }}" @selected((string) ($filters['creditor_id'] ?? '') === (string) $creditor->id)>{{ $creditor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach (['active' => 'Activa', 'late' => 'Atrasada', 'paid' => 'Pagada', 'cancelled' => 'Cancelada'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) ($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2 d-grid">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-filter me-2"></i> Filtrar
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
                            <th>Cuenta</th>
                            <th>Acreedor</th>
                            <th>Condiciones</th>
                            <th>Fechas</th>
                            <th class="text-end">Saldo</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            @php($accountCurrency = $account->currency ?? currency())
                            <tr>
                                <td>
                                    <a href="{{ route('accounts-payable.show', $account) }}" class="fw-semibold text-decoration-none">{{ $account->reference }}</a>
                                    <div class="small text-muted">{{ $accountCurrency }} {{ number_format((float) $account->principal_amount, 2) }} tomados</div>
                                </td>
                                <td>
                                    <div>{{ $account->creditor?->name ?: 'Sin acreedor' }}</div>
                                    <div class="small text-muted">{{ $account->creditor?->phone ?: 'Sin telefono' }}</div>
                                </td>
                                <td>
                                    <div>{{ (int) $account->term_quantity }} cuotas</div>
                                    <div class="small text-muted">
                                        {{ config('loan_labels.frequencies.'.$account->payment_frequency, $account->payment_frequency) }}
                                        · {{ rtrim(rtrim(number_format((float) $account->interest_rate, 4, '.', ''), '0'), '.') }}%
                                    </div>
                                </td>
                                <td>
                                    <div>Entrega: {{ $account->disbursement_date?->format('d/m/Y') }}</div>
                                    <div class="small text-muted">Primer pago: {{ $account->first_payment_date?->format('d/m/Y') }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="fw-semibold">{{ $accountCurrency }} {{ number_format((float) $account->remaining_balance, 2) }}</div>
                                    <div class="small text-muted">Pagado capital: {{ $accountCurrency }} {{ number_format((float) $account->paid_principal, 2) }}</div>
                                </td>
                                <td>@include('partials.status-badge', ['map' => 'account_payable_statuses', 'value' => $account->status])</td>
                                <td class="text-end">
                                    @if (($account->payments_count ?? 0) === 0)
                                        <a href="{{ route('accounts-payable.edit', $account) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <form action="{{ route('accounts-payable.destroy', $account) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta cuenta por pagar? Solo es posible si no tiene pagos registrados.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Con pagos</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">No hay cuentas por pagar registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $accounts->links() }}
            </div>
        </div>
    </section>
@endsection
