@php
    $frequencyLabels = [
        'daily' => 'Diario',
        'weekly' => 'Semanal',
        'biweekly' => 'Quincenal',
        'monthly' => 'Mensual',
    ];

    $methodLabels = [
        'flat_interest' => 'Interés fijo',
        'fixed_installment' => 'Cuota fija',
        'capital_plus_interest' => 'Capital + interés',
        'interest_only' => 'Solo interés',
        'german_amortization' => 'Amortización alemana',
        'french_amortization' => 'Amortización francesa',
    ];

    $statusLabels = [
        'pending' => ['label' => 'Pendiente', 'class' => 'text-bg-warning'],
        'approved' => ['label' => 'Aprobada', 'class' => 'text-bg-success'],
        'rejected' => ['label' => 'Rechazada', 'class' => 'text-bg-danger'],
        'converted' => ['label' => 'Convertida', 'class' => 'text-bg-primary'],
    ];
@endphp
