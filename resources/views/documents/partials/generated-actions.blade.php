<div class="alert alert-success d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div>
        <div class="fw-semibold">{{ session('status', 'Documento generado correctamente.') }}</div>
        <div class="small">Seleccione como desea entregar el documento.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('documents.download', $documentId) }}" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-download me-2"></i>Descargar
        </a>
        <a href="{{ route('documents.whatsapp', $documentId) }}" class="btn btn-sm btn-success" target="_blank" rel="noopener">
            <i class="fa-brands fa-whatsapp me-2"></i>Enviar por WS
        </a>
    </div>
</div>
