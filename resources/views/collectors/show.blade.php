@extends('layouts.app')

@section('title', $collector->name.' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $collector->name }}</h1>
                <div class="d-flex flex-wrap gap-2">
                    @include('collectors.partials.status-badge', ['status' => $collector->status])
                    @include('collectors.partials.commission-badge', ['type' => $collector->commission_type])
                </div>
            </div>
            <a href="{{ route('collectors.edit', $collector) }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-pen me-2"></i> Editar
            </a>
        </div>
    </section>

    @if (session('collector_credentials'))
        <section class="alert alert-success border mb-4">
            <div class="d-flex flex-column gap-2">
                <div class="fw-semibold">Credenciales del cobrador creadas correctamente</div>
                <div class="small text-muted">Este mensaje se muestra una sola vez para que operacion copie el acceso.</div>
                <div><strong>Usuario:</strong> {{ session('collector_credentials.user_name') }}</div>
                <div><strong>Correo:</strong> {{ session('collector_credentials.email') }}</div>
                <div><strong>Contrasena temporal:</strong> {{ session('collector_credentials.password') }}</div>
            </div>
        </section>
    @endif

    <section class="row g-4 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Cobrado con este cobrador</div>
                    <div class="fs-4 fw-bold">{{ currency() }} {{ number_format((float) $commissionSummary['total_collected'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Comision generada</div>
                    <div class="fs-4 fw-bold">{{ currency() }} {{ number_format((float) $commissionSummary['total_generated'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Pendiente por pagar</div>
                    <div class="fs-4 fw-bold text-warning">{{ currency() }} {{ number_format((float) $commissionSummary['total_pending'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Ya pagado</div>
                    <div class="fs-4 fw-bold text-success">{{ currency() }} {{ number_format((float) $commissionSummary['total_paid'], 2) }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Información</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Teléfono</dt>
                        <dd class="col-sm-7">{{ $collector->phone ?: 'Sin teléfono' }}</dd>
                        <dt class="col-sm-5">Usuario vinculado</dt>
                        <dd class="col-sm-7">
                            @if ($collector->user)
                                {{ $collector->user->name }}<br>
                                <span class="text-muted small">{{ $collector->user->email }}</span>
                            @else
                                Sin usuario vinculado
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Comisión</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Tipo</dt>
                        <dd class="col-sm-7">
                            @include('collectors.partials.commission-badge', ['type' => $collector->commission_type])
                        </dd>
                        <dt class="col-sm-5">Valor</dt>
                        <dd class="col-sm-7">
                            @if ($collector->commission_type === 'percentage')
                                {{ number_format((float) $collector->commission_value, 2) }}%
                            @elseif ($collector->commission_type === 'fixed')
                                {{ currency() }} {{ number_format((float) $collector->commission_value, 2) }}
                            @else
                                No aplica
                            @endif
                        </dd>
                        <dt class="col-sm-5">Regla</dt>
                        <dd class="col-sm-7">{{ $collector->commission_base === 'principal_only' ? 'Solo sobre capital cobrado' : 'Sobre el total cobrado' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h6 text-uppercase text-muted mb-1">Cobros y comisiones</h2>
                    <p class="text-muted small mb-0">Cada cobro genera una comision segun el esquema del cobrador.</p>
                </div>
                @error('commission')
                    <div class="alert alert-danger py-2 px-3 mb-0">{{ $message }}</div>
                @enderror
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Recibo</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th class="text-end">Cobrado</th>
                            <th class="text-end">Comision</th>
                            <th>Estado</th>
                            <th class="text-end">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($collector->commissions as $commission)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $commission->payment?->receipt_number ?: 'Sin recibo' }}</div>
                                    <div class="small text-muted">
                                        @if ($commission->commission_type === 'percentage')
                                            {{ number_format((float) $commission->commission_value, 2) }}%
                                        @else
                                            {{ currency() }} {{ number_format((float) $commission->commission_value, 2) }}
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $commission->payment?->client?->full_name ?: 'Sin cliente' }}</td>
                                <td>{{ $commission->payment?->payment_date?->format('d/m/Y') ?: '-' }}</td>
                                <td class="text-end">{{ currency() }} {{ number_format((float) $commission->base_amount, 2) }}</td>
                                <td class="text-end fw-semibold">{{ currency() }} {{ number_format((float) $commission->commission_amount, 2) }}</td>
                                <td>
                                    @php
                                        $statusMap = [
                                            'pending' => ['Pendiente', 'text-bg-warning'],
                                            'paid' => ['Pagada', 'text-bg-success'],
                                            'cancelled' => ['Cancelada', 'text-bg-secondary'],
                                        ];
                                        [$statusLabel, $statusClass] = $statusMap[$commission->status] ?? [$commission->status, 'text-bg-secondary'];
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    @if ($commission->paid_at)
                                        <div class="small text-muted mt-1">{{ $commission->paid_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($commission->status === 'pending')
                                        <form method="POST" action="{{ route('collectors.commissions.pay', [$collector, $commission]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('¿Marcar esta comision como pagada?');">
                                                Pagar
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Sin accion</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay comisiones registradas para este cobrador.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
