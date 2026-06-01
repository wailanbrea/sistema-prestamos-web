@extends('layouts.app')

@section('title', 'Gastos - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Gastos</h1>
                <p class="text-muted mb-0">Registro de gastos operativos con salida automática de caja.</p>
            </div>
            <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i> Nuevo gasto
            </a>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card content-card h-100">
                <div class="card-body">
                    <form method="GET" action="{{ route('expenses.index') }}" class="row g-3 align-items-end">
                        <div class="col-12 col-lg-4">
                            <label for="search" class="form-label">Buscar</label>
                            <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Descripción">
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label for="category_id" class="form-label">Categoría</label>
                            <select id="category_id" name="category_id" class="form-select">
                                <option value="">Todas</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <label for="date_from" class="form-label">Desde</label>
                            <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <label for="date_to" class="form-label">Hasta</label>
                            <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                        </div>
                        <div class="col-12 col-lg-1 d-grid">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Nueva categoría</h2>
                    <form method="POST" action="{{ route('expense-categories.store') }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="category_name" class="form-label">Nombre</label>
                            <input id="category_name" name="name" type="text" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" maxlength="150">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fa-solid fa-tags me-2"></i> Crear categoría
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card content-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Gasto</th>
                                    <th>Categoría</th>
                                    <th>Método</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expenses as $expense)
                                    <tr>
                                        <td>
                                            <a href="{{ route('expenses.show', $expense) }}" class="fw-semibold text-decoration-none">{{ $expense->description }}</a>
                                        </td>
                                        <td>{{ $expense->category?->name ?: 'Sin categoría' }}</td>
                                        <td>@include('expenses.partials.method-label', ['method' => $expense->payment_method])</td>
                                        <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ currency() }} {{ number_format((float) $expense->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">No hay gastos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $expenses->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card content-card">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Categorías</h2>
                    <div class="vstack gap-3">
                        @forelse ($categories as $category)
                            <div class="d-flex align-items-start justify-content-between gap-3 border-bottom pb-3">
                                <div>
                                    <div class="fw-semibold">{{ $category->name }}</div>
                                    <div class="text-muted small">{{ $category->expenses_count }} gastos</div>
                                </div>
                                <form action="{{ route('expense-categories.destroy', $category) }}" method="POST" onsubmit="return confirm('¿Eliminar esta categoría?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none">Eliminar</button>
                                </form>
                            </div>
                        @empty
                            <div class="text-muted">No hay categorías registradas.</div>
                        @endforelse
                    </div>
                    @error('category') <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </section>
@endsection
