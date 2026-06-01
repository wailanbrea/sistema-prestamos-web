@php
    /**
     * Badge de estado traducido y centralizado.
     * Uso: @include('partials.status-badge', ['map' => 'installment_statuses', 'value' => $installment->status])
     * El mapa vive en config/loan_labels.php. Fallback seguro si la clave no existe.
     */
    $statusMap = config('loan_labels.'.$map, []);
    $statusData = $statusMap[$value] ?? ['label' => $value, 'class' => 'text-bg-secondary'];
@endphp

<span class="badge {{ $statusData['class'] }}">{{ $statusData['label'] }}</span>
