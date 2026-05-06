@php
    $statuses = [
        'active' => ['label' => 'Activo', 'class' => 'text-bg-success'],
        'inactive' => ['label' => 'Inactivo', 'class' => 'text-bg-secondary'],
        'moroso' => ['label' => 'Moroso', 'class' => 'text-bg-warning'],
        'blocked' => ['label' => 'Bloqueado', 'class' => 'text-bg-danger'],
    ];
    $statusData = $statuses[$status] ?? ['label' => $status, 'class' => 'text-bg-secondary'];
@endphp

<span class="badge {{ $statusData['class'] }}">{{ $statusData['label'] }}</span>
