@extends('layouts.app')

@section('title', 'Movimiento de caja - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Movimiento manual de caja</h1>
        <p class="text-muted mb-0">Registra inyecciones, retiros o ajustes de capital con auditoría.</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('cash-movements.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="type" class="form-label">Tipo</label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                            @foreach (['capital_injection' => 'Inyección de capital', 'capital_withdrawal' => 'Retiro de capital', 'adjustment' => 'Ajuste'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="direction" class="form-label">Dirección para ajustes</label>
                        <select id="direction" name="direction" class="form-select @error('direction') is-invalid @enderror">
                            <option value="">No aplica salvo ajuste</option>
                            <option value="in" @selected(old('direction') === 'in')>Entrada</option>
                            <option value="out" @selected(old('direction') === 'out')>Salida</option>
                        </select>
                        @error('direction') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="amount" class="form-label">Monto</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="movement_date" class="form-label">Fecha</label>
                        <input id="movement_date" name="movement_date" type="date" value="{{ old('movement_date', now()->toDateString()) }}" class="form-control @error('movement_date') is-invalid @enderror" required>
                        @error('movement_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Descripción / justificación</label>
                        <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('cash-movements.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-vault me-2"></i> Registrar movimiento
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
