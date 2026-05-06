@php
    $labels = [
        'active' => ['Activo', 'success'],
        'blocked' => ['Bloqueado', 'danger'],
    ];
    [$label, $color] = $labels[$status] ?? [$status, 'secondary'];
@endphp

<span class="badge text-bg-{{ $color }}">{{ $label }}</span>
