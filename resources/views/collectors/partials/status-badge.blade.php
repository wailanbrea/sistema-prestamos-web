@php
    $labels = [
        'active' => ['Activo', 'success'],
        'inactive' => ['Inactivo', 'secondary'],
    ];
    [$label, $color] = $labels[$status] ?? [$status, 'secondary'];
@endphp

<span class="badge text-bg-{{ $color }}">{{ $label }}</span>
