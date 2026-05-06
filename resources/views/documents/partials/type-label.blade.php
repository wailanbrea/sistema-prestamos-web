@php
    $labels = [
        'promissory_note' => 'Pagaré notarial',
        'loan_contract' => 'Contrato de préstamo',
        'disbursement_receipt' => 'Comprobante de desembolso',
        'payment_receipt' => 'Recibo de pago',
        'balance_letter' => 'Carta de saldo',
        'account_statement' => 'Estado de cuenta',
    ];
@endphp

{{ $labels[$type] ?? $type }}
