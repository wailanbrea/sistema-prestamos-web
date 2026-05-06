@extends('layouts.app')

@section('title', $title.' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $description }}</p>
            </div>
            <button type="button" class="btn btn-primary" disabled>
                <i class="fa-solid fa-plus me-2"></i> Próximamente
            </button>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-8">
            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Base del módulo</h2>
                    <p class="text-muted small mb-0">Esta pantalla reserva la ruta, permiso y navegación para el desarrollo funcional.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Elemento</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold">{{ $title }}</td>
                                    <td><span class="badge text-bg-secondary">Pendiente de implementación</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-link text-dark text-decoration-none" disabled>Editar</button>
                                        <button class="btn btn-link text-danger text-decoration-none" disabled>Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-4">
            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Siguiente implementación</h2>
                    <p class="text-muted small mb-0">Entregables que reemplazarán este placeholder.</p>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach ($nextSteps as $step)
                            <li class="list-group-item px-0 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-primary"></i>
                                <span>{{ $step }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </article>
        </div>
    </section>
@endsection
