@php
    $labels = [
        'percentage' => 'Porcentaje',
        'fixed' => 'Monto fijo',
        'none' => 'Sin comisión',
    ];
@endphp

<span class="badge text-bg-light border text-dark">{{ $labels[$type] ?? $type }}</span>
