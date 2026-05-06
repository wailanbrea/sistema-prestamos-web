@php
    $labels = [
        'cash' => 'Efectivo',
        'transfer' => 'Transferencia',
        'card' => 'Tarjeta',
        'check' => 'Cheque',
        'other' => 'Otro',
    ];
@endphp

{{ $labels[$method] ?? $method }}
