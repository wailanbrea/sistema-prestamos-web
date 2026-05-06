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
        'french_amortization' => 'Amortización francesa',
    ],
    'quote_statuses' => [
        'pending' => ['label' => 'Pendiente', 'class' => 'text-bg-warning'],
        'approved' => ['label' => 'Aprobada', 'class' => 'text-bg-success'],
        'rejected' => ['label' => 'Rechazada', 'class' => 'text-bg-danger'],
        'converted' => ['label' => 'Convertida', 'class' => 'text-bg-primary'],
    ],
    'loan_statuses' => [
        'active' => ['label' => 'Activo', 'class' => 'text-bg-success'],
        'late' => ['label' => 'Atrasado', 'class' => 'text-bg-warning'],
        'paid' => ['label' => 'Saldado', 'class' => 'text-bg-primary'],
        'refinanced' => ['label' => 'Refinanciado', 'class' => 'text-bg-info'],
        'cancelled' => ['label' => 'Cancelado', 'class' => 'text-bg-secondary'],
        'legal' => ['label' => 'Legal', 'class' => 'text-bg-dark'],
        'written_off' => ['label' => 'Castigado', 'class' => 'text-bg-danger'],
    ],
];
