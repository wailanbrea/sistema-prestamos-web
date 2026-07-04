<?php

declare(strict_types=1);

return [
    'frequencies' => [
        'daily' => 'Diario',
        'weekly' => 'Semanal',
        'biweekly' => 'Quincenal',
        'monthly' => 'Mensual',
    ],
    'methods' => [
        'flat_interest' => 'Interés fijo',
        'fixed_installment' => 'Cuota fija',
        'capital_plus_interest' => 'Capital + interés',
        'interest_only' => 'Solo interés',
        'german_amortization' => 'Amortización alemana',
        'french_amortization' => 'Amortización francesa',
    ],
    'quote_statuses' => [
        'pending' => ['label' => 'Pendiente', 'class' => 'text-bg-warning'],
        'approved' => ['label' => 'Aprobada', 'class' => 'text-bg-success'],
        'rejected' => ['label' => 'Rechazada', 'class' => 'text-bg-danger'],
        'converted' => ['label' => 'Convertida', 'class' => 'text-bg-primary'],
    ],
    'loan_statuses' => [
        'pending' => ['label' => 'Pendiente de aprobación', 'class' => 'text-bg-warning'],
        'active' => ['label' => 'Activo', 'class' => 'text-bg-success'],
        'late' => ['label' => 'Atrasado', 'class' => 'text-bg-warning'],
        'paid' => ['label' => 'Saldado', 'class' => 'text-bg-primary'],
        'refinanced' => ['label' => 'Refinanciado', 'class' => 'text-bg-info'],
        'cancelled' => ['label' => 'Cancelado', 'class' => 'text-bg-secondary'],
        'legal' => ['label' => 'Legal', 'class' => 'text-bg-dark'],
        'written_off' => ['label' => 'Castigado', 'class' => 'text-bg-danger'],
    ],
    'account_payable_statuses' => [
        'active' => ['label' => 'Activa', 'class' => 'text-bg-success'],
        'late' => ['label' => 'Atrasada', 'class' => 'text-bg-warning'],
        'paid' => ['label' => 'Pagada', 'class' => 'text-bg-primary'],
        'cancelled' => ['label' => 'Cancelada', 'class' => 'text-bg-secondary'],
    ],
    'installment_statuses' => [
        'pending' => ['label' => 'Pendiente', 'class' => 'text-bg-secondary'],
        'partial' => ['label' => 'Parcial', 'class' => 'text-bg-info'],
        'paid' => ['label' => 'Pagada', 'class' => 'text-bg-success'],
        'late' => ['label' => 'Vencida', 'class' => 'text-bg-danger'],
        'cancelled' => ['label' => 'Cancelada', 'class' => 'text-bg-dark'],
    ],
    'payment_methods' => [
        'cash' => 'Efectivo',
        'transfer' => 'Transferencia',
        'card' => 'Tarjeta',
        'check' => 'Cheque',
        'other' => 'Otro',
    ],
    'payment_allocation_modes' => [
        'auto' => 'Automático',
        'principal_and_interest' => 'Capital + interés',
        'interest_only' => 'Solo interés',
        'principal_only' => 'Solo capital',
        'current_plus_capital' => 'Cuota + capital',
        'custom' => 'Personalizado',
    ],
    'payment_statuses' => [
        'valid' => ['label' => 'Válido', 'class' => 'text-bg-success'],
        'cancelled' => ['label' => 'Anulado', 'class' => 'text-bg-danger'],
    ],
    'currencies' => [
        'RD$' => 'Peso dominicano (RD$)',
        'US$' => 'Dólar (US$)',
    ],
];
