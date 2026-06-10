@extends('layouts.app')

@section('title', 'Acreedores - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Acreedores</h1>
                <p class="text-muted mb-0">Administra las personas o entidades que prestan dinero a la empresa.</p>
            </div>
            <a href="{{ route('creditors.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i> Nuevo acreedor
            </a>
        </div>
    </section>

    @if ($errors->has('creditor'))
        <div class="alert alert-danger">{{ $errors->first('creditor') }}</div>
    @endif

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('creditors.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-6">
                    <label for="search" class="form-label">Buscar</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Nombre, documento, telefono o correo">
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach (['active' => 'Activo', 'inactive' => 'Inactivo'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
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
                            <th>Acreedor</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th class="text-end">Cuentas</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($creditors as $creditor)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $creditor->name }}</div>
                                    <div class="small text-muted">{{ $creditor->document ?: 'Sin documento' }}</div>
                                </td>
                                <td>
                                    <div>{{ $creditor->phone ?: 'Sin telefono' }}</div>
                                    <div class="small text-muted">{{ $creditor->email ?: 'Sin correo' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $creditor->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $creditor->status === 'active' ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $creditor->accounts_payable_count }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('creditors.edit', $creditor) }}" class="btn btn-sm btn-link text-dark text-decoration-none">Editar</a>
                                    <form action="{{ route('creditors.destroy', $creditor) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este acreedor?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">No hay acreedores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $creditors->links() }}
            </div>
        </div>
    </section>
@endsection
