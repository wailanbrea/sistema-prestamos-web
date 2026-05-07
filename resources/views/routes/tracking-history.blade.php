@extends('layouts.app')

@section('title', 'Historial de rutas - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Historial de rutas</h1>
                <p class="text-muted mb-0">Rutas finalizadas, visitas realizadas y puntos omitidos.</p>
            </div>
            <a href="{{ route('routes.tracking') }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-location-crosshairs me-2"></i> Seguimiento en vivo
            </a>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <div class="vstack gap-3">
                @forelse ($sessions as $session)
                    @php
                        $visited = collect($session['stops'])->where('visited', true)->count();
                        $total = count($session['stops']);
                        $missed = max(0, $total - $visited);
                    @endphp
                    <article class="border rounded-3 p-3">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                            <div>
                                <h2 class="h6 fw-bold mb-1">{{ $session['route']['name'] ?? 'Ruta' }}</h2>
                                <div class="text-muted small">
                                    {{ $session['collector']['name'] ?? 'Cobrador' }} ·
                                    Inicio {{ $session['started_at'] ? \Carbon\CarbonImmutable::parse($session['started_at'])->format('d/m/Y H:i') : 'N/D' }} ·
                                    Fin {{ $session['ended_at'] ? \Carbon\CarbonImmutable::parse($session['ended_at'])->format('d/m/Y H:i') : 'N/D' }}
                                </div>
                            </div>
                            <div class="d-flex gap-2 align-items-start">
                                <span class="badge text-bg-success">Visitados {{ $visited }}</span>
                                <span class="badge {{ $missed > 0 ? 'text-bg-danger' : 'text-bg-light' }}">Omitidos {{ $missed }}</span>
                            </div>
                        </div>

                        <div class="progress mt-3" role="progressbar" aria-label="Progreso de visitas">
                            <div class="progress-bar" style="width: {{ $total > 0 ? round(($visited / $total) * 100) : 0 }}%"></div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th>Estado</th>
                                        <th>Hora</th>
                                        <th class="text-end">Distancia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($session['stops'] as $stop)
                                        <tr>
                                            <td>{{ $stop['expected_order'] }}</td>
                                            <td>{{ $stop['client_name'] }}</td>
                                            <td>
                                                @if ($stop['visited'])
                                                    <span class="badge {{ $stop['visit_status'] === 'visited_out_of_order' ? 'text-bg-warning' : 'text-bg-success' }}">
                                                        {{ $stop['visit_status'] === 'visited_out_of_order' ? 'Fuera de orden' : 'Visitado' }}
                                                    </span>
                                                @else
                                                    <span class="badge text-bg-danger">No visitado</span>
                                                @endif
                                            </td>
                                            <td>{{ $stop['visited_at'] ? \Carbon\CarbonImmutable::parse($stop['visited_at'])->format('H:i') : 'N/D' }}</td>
                                            <td class="text-end">{{ $stop['distance_meters'] !== null ? $stop['distance_meters'].' m' : 'N/D' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>
                @empty
                    <div class="text-center text-muted py-5">Todavia no hay rutas finalizadas.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
