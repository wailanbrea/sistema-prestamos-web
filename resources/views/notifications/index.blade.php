@extends('layouts.app')

@section('title', 'Notificaciones - '.config('app.name'))

@section('content')
    @php
        $canViewRouteMap = auth()->user()->can('routes.manage');
        $canViewPayments = auth()->user()->can('payments.create');
        $missingCoordinates = (int)($operationAlerts['missing_coordinates'] ?? 0);
        $lateInstallments = (int)($operationAlerts['late_installments'] ?? 0);
        $hasOperationalAlerts = ($canViewRouteMap && $missingCoordinates > 0)
            || ($canViewPayments && $lateInstallments > 0);
    @endphp

    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Notificaciones</h1>
                <p class="text-muted mb-0">
                    @if ($unreadCount > 0)
                        Tienes <strong>{{ $unreadCount }}</strong> sin leer.
                    @else
                        Estás al día. No tienes notificaciones sin leer.
                    @endif
                </p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fa-solid fa-check-double me-1"></i> Marcar todas como leídas
                    </button>
                </form>
            @endif
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 fw-bold mb-0">Alertas operativas</h2>
                <p class="text-muted small mb-0">Indicadores dinámicos calculados con la data actual.</p>
            </div>
        </div>
        <div class="list-group list-group-flush">
            @if ($canViewRouteMap)
                <a href="{{ route('routes.map') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="fs-4 text-primary"><i class="fa-solid fa-map-location-dot"></i></div>
                        <div>
                            <div class="fw-semibold">Clientes sin coordenadas</div>
                            <div class="text-muted small">Clientes que no se pueden ubicar correctamente en el mapa.</div>
                        </div>
                    </div>
                    <span class="badge rounded-pill {{ $missingCoordinates > 0 ? 'text-bg-danger' : 'text-bg-secondary' }}">
                        {{ number_format($missingCoordinates) }}
                    </span>
                </a>
            @endif

            @if ($canViewPayments)
                <a href="{{ route('payments.index') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="fs-4 text-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div>
                            <div class="fw-semibold">Cuotas en mora</div>
                            <div class="text-muted small">Cuotas pendientes o parciales con fecha de vencimiento pasada.</div>
                        </div>
                    </div>
                    <span class="badge rounded-pill {{ $lateInstallments > 0 ? 'text-bg-danger' : 'text-bg-secondary' }}">
                        {{ number_format($lateInstallments) }}
                    </span>
                </a>
            @endif

            @if (!$canViewRouteMap && !$canViewPayments)
                <div class="list-group-item text-center text-muted py-4">
                    No tienes alertas operativas disponibles para tu rol.
                </div>
            @elseif (!$hasOperationalAlerts)
                <div class="list-group-item text-center text-muted py-4">
                    No hay alertas operativas pendientes.
                </div>
            @endif
        </div>
    </section>

    <section class="card content-card">
        <div class="card-header bg-white">
            <h2 class="h6 fw-bold mb-0">Notificaciones del sistema</h2>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @php $data = $notification->data; @endphp
                <div class="list-group-item d-flex align-items-start gap-3 {{ $notification->read_at ? '' : 'bg-light' }}">
                    <div class="fs-4 text-primary">
                        <i class="fa-solid {{ $data['icon'] ?? 'fa-bell' }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="fw-semibold">
                                {{ $data['title'] ?? 'Notificación' }}
                                @unless ($notification->read_at)
                                    <span class="badge text-bg-danger ms-1">Nueva</span>
                                @endunless
                            </div>
                            <span class="small text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-muted">{{ $data['message'] ?? '' }}</div>
                        @if (! empty($data['url']))
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none">
                                    Ver detalle <i class="fa-solid fa-arrow-right ms-1"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-5">
                    <i class="fa-regular fa-bell-slash fs-2 d-block mb-2"></i>
                    No tienes notificaciones del sistema.
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="card-body">
                {{ $notifications->links() }}
            </div>
        @endif
    </section>
@endsection
