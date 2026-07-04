@php
    $loanStatusLabels = [
        'active' => ['label' => 'Activo', 'class' => 'text-bg-success'],
        'late' => ['label' => 'Atrasado', 'class' => 'text-bg-warning'],
        'paid' => ['label' => 'Saldado', 'class' => 'text-bg-primary'],
        'refinanced' => ['label' => 'Refinanciado', 'class' => 'text-bg-info'],
        'cancelled' => ['label' => 'Cancelado', 'class' => 'text-bg-secondary'],
        'legal' => ['label' => 'Legal', 'class' => 'text-bg-dark'],
        'written_off' => ['label' => 'Castigado', 'class' => 'text-bg-danger'],
    ];

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
@endphp
