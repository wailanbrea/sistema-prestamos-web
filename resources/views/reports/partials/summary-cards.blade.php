{{-- Tarjetas de totales destacados. Espera: $items (array de {label,value,money}). --}}
@if (! empty($items))
    <section class="row g-3 mb-4">
        @foreach ($items as $item)
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card content-card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small text-uppercase">{{ $item['label'] }}</div>
                        <div class="fs-5 fw-bold text-dark">
                            @if ($item['money'])
                                @include('reports.partials.money', ['amount' => (float) $item['value']])
                            @else
                                {{ is_numeric($item['value']) ? number_format((float) $item['value'], (floor((float) $item['value']) == (float) $item['value']) ? 0 : 2) : $item['value'] }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>
@endif
