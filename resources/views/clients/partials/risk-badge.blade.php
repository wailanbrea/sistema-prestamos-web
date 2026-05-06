@php
    $risks = [
        'low' => ['label' => 'Bajo', 'class' => 'text-bg-success'],
        'medium' => ['label' => 'Medio', 'class' => 'text-bg-info'],
        'high' => ['label' => 'Alto', 'class' => 'text-bg-warning'],
        'critical' => ['label' => 'Crítico', 'class' => 'text-bg-danger'],
    ];
    $riskData = $risks[$risk] ?? ['label' => $risk, 'class' => 'text-bg-secondary'];
@endphp

<span class="badge {{ $riskData['class'] }}">{{ $riskData['label'] }}</span>
