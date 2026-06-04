@php
    $labels = [
        'loan_disbursement' => 'Desembolso',
        'payment_received' => 'Cobro recibido',
        'expense' => 'Gasto',
        'accounts_payable_disbursement' => 'Prestamo tomado',
        'accounts_payable_payment' => 'Pago cuenta por pagar',
        'collector_commission' => 'Comision cobrador',
        'capital_injection' => 'Inyeccion de capital',
        'capital_withdrawal' => 'Retiro de capital',
        'adjustment' => 'Ajuste',
    ];
@endphp

{{ $labels[$type] ?? $type }}
