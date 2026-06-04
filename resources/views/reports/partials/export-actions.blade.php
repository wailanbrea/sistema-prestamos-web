{{-- Acciones: imprimir, PDF, Excel, WhatsApp. Espera: $type, $filters. --}}
@php
    $params = array_merge(['type' => $type], $filters->toArray());
    $shareText = rawurlencode($title.' — '.$filters->periodLabel().' '.url()->current());
@endphp
<div class="d-flex flex-wrap gap-2 no-print">
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
        <i class="fa-solid fa-print me-2"></i> Imprimir
    </button>
    <a href="{{ route('reports.export.pdf', $params) }}" class="btn btn-outline-danger">
        <i class="fa-solid fa-file-pdf me-2"></i> PDF
    </a>
    <a href="{{ route('reports.export.excel', $params) }}" class="btn btn-outline-success">
        <i class="fa-solid fa-file-excel me-2"></i> Excel
    </a>
    <a href="https://wa.me/?text={{ $shareText }}" target="_blank" rel="noopener" class="btn btn-outline-success">
        <i class="fa-brands fa-whatsapp me-2"></i> WhatsApp
    </a>
</div>
