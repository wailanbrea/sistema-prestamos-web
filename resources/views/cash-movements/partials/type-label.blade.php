@php
    $labels = [
        'loan_disbursement' => 'Desembolso',
        'payment_received' => 'Cobro recibido',
        'expense' => 'Gasto',
        'collector_commission' => 'Comisión cobrador',
        'capital_injection' => 'Inyección de capital',
        'capital_withdrawal' => 'Retiro de capital',
        'adjustment' => 'Ajuste',
    ];
@endphp

{{ $labels[$type] ?? $type }}
