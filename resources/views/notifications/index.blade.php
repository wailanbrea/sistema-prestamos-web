@extends('layouts.app')

@section('title', 'Notificaciones - '.config('app.name'))

@section('content')
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

    <section class="card content-card">
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
                    No tienes notificaciones.
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
