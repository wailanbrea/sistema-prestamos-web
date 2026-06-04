{{--
    Formulario de filtros globales (GET). Adaptativo según $type:
    - año: solo en resumen anual
    - búsqueda: solo en préstamos entregados
    Espera: $filters (ReportFilters), $options (zones/routes/collectors), $type, $data.
--}}
@php
    $availableYears = $data['meta']['available_years'] ?? range((int) now()->year, (int) now()->year - 5);
@endphp

<section class="card content-card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label for="preset" class="form-label">Período</label>
                <select id="preset" name="preset" class="form-select">
                    @foreach (\App\Support\Reports\ReportFilters::PRESETS as $value => $label)
                        <option value="{{ $value }}" @selected($filters->preset === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($type === 'resumen-anual')
                <div class="col-6 col-md-3">
                    <label for="year" class="form-label">Año</label>
                    <select id="year" name="year" class="form-select">
                        @foreach ($availableYears as $year)
                            <option value="{{ $year }}" @selected(($filters->year ?? (int) now()->year) === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="col-6 col-md-3">
                    <label for="date_from" class="form-label">Desde</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters->dateFrom->toDateString() }}" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label for="date_to" class="form-label">Hasta</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters->dateTo->toDateString() }}" class="form-control">
                </div>
            @endif

            @if ($options['zones']->isNotEmpty())
                <div class="col-6 col-md-3">
                    <label for="zone_id" class="form-label">Sucursal / Zona</label>
                    <select id="zone_id" name="zone_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($options['zones'] as $zone)
                            <option value="{{ $zone->id }}" @selected($filters->zoneId === $zone->id)>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-6 col-md-3">
                <label for="route_id" class="form-label">Ruta</label>
                <select id="route_id" name="route_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($options['routes'] as $route)
                        <option value="{{ $route->id }}" @selected($filters->routeId === $route->id)>{{ $route->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label for="collector_id" class="form-label">Cobrador</label>
                <select id="collector_id" name="collector_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($options['collectors'] as $collector)
                        <option value="{{ $collector->id }}" @selected($filters->collectorId === $collector->id)>{{ $collector->name }}</option>
                    @endforeach
                </select>
            </div>

            @if ($type === 'prestamos-entregados')
                <div class="col-12 col-md-6">
                    <label for="search" class="form-label">Buscar (nombre, código o teléfono)</label>
                    <input id="search" name="search" type="text" value="{{ $filters->search }}" class="form-control" placeholder="Ej. Juan, C-102, 809...">
                </div>
            @endif

            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter me-2"></i> Filtrar
                </button>
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-eraser me-2"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</section>
