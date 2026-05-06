@php
    $labels = [
        'active' => ['Activa', 'success'],
        'inactive' => ['Inactiva', 'secondary'],
    ];
    [$label, $color] = $labels[$status] ?? [$status, 'secondary'];
@endphp

<span class="badge text-bg-{{ $color }}">{{ $label }}</span>
