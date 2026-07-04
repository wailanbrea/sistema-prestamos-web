@php
    $mode = $mode ?? ($payment->allocation_mode ?? null);

    if (! $mode && isset($payment)) {
        $principal = (float) ($payment->principal_paid ?? 0);
        $interest = (float) ($payment->interest_paid ?? 0);
        $late = (float) ($payment->late_fee_paid ?? 0);
        $capitalPrepaid = (float) ($payment->capital_prepaid ?? 0);

        $mode = match (true) {
            $capitalPrepaid > 0 => 'current_plus_capital',
            $principal > 0 && $interest <= 0 && $late <= 0 => 'principal_only',
            $interest > 0 && $principal <= 0 && $late <= 0 => 'interest_only',
            $principal > 0 && $interest > 0 && $late <= 0 => 'principal_and_interest',
            default => null,
        };
    }

    $labels = [
        'auto' => 'Automático',
        'principal_and_interest' => 'Capital + interés',
        'interest_only' => 'Solo interés',
        'principal_only' => 'Solo capital',
        'current_plus_capital' => 'Cuota + capital',
        'custom' => 'Personalizado',
    ];
@endphp

{{ $labels[$mode ?? ''] ?? ($mode ?: 'No registrado') }}
