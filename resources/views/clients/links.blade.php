@extends('layouts.app')

@section('title', 'Links de registro - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Links de registro por WhatsApp</h1>
                <p class="text-muted mb-0">Genera un enlace de uso único para que el cliente complete su formulario sin iniciar sesión.</p>
            </div>
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Volver a clientes</a>
        </div>
    </section>

    @if ($generatedLink)
        <section class="alert alert-success mb-4">
            <div class="fw-semibold mb-2">Enlace generado</div>
            <div class="small mb-2">{{ $generatedLink }}</div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-success" data-copy-text="{{ $generatedLink }}">Copiar link</button>
                @if ($generatedWhatsappUrl)
                    <a href="{{ $generatedWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-success">
                        <i class="fa-brands fa-whatsapp me-1"></i> Abrir WhatsApp
                    </a>
                @endif
            </div>
        </section>
    @endif

    <section class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Generar nuevo link</h2>
                    <form method="POST" action="{{ route('clients.links.store') }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="recipient_name" class="form-label">Nombre de referencia</label>
                            <input id="recipient_name" name="recipient_name" type="text" value="{{ old('recipient_name') }}" class="form-control @error('recipient_name') is-invalid @enderror" maxlength="180" placeholder="Ej. María Rodríguez">
                            @error('recipient_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label for="recipient_phone" class="form-label">WhatsApp / teléfono</label>
                            <input id="recipient_phone" name="recipient_phone" type="text" value="{{ old('recipient_phone') }}" class="form-control @error('recipient_phone') is-invalid @enderror" maxlength="50" placeholder="8095551234">
                            @error('recipient_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Si generas otro link para el mismo teléfono, el anterior pendiente se invalida.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-link me-2"></i> Generar link único
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card content-card">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Links recientes</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Destinatario</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th>Cliente creado</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($links as $link)
                                    @php
                                        $status = $link->used_at ? ['Usado', 'text-bg-success'] : ($link->revoked_at ? ['Revocado', 'text-bg-secondary'] : ['Pendiente', 'text-bg-warning']);
                                        $publicUrl = route('client-registration.show', $link->token);
                                        $waUrl = $link->recipient_phone
                                            ? 'https://wa.me/'.preg_replace('/\D+/', '', $link->recipient_phone).'?text='.rawurlencode('Hola'.($link->recipient_name ? ' '.$link->recipient_name : '').', completa tu formulario de registro aquí: '.$publicUrl)
                                            : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $link->recipient_name ?: 'Sin nombre' }}</div>
                                            <div class="text-muted small">{{ $link->recipient_phone ?: 'Sin teléfono' }}</div>
                                        </td>
                                        <td><span class="badge {{ $status[1] }}">{{ $status[0] }}</span></td>
                                        <td>
                                            <div>{{ $link->created_at?->format('d/m/Y H:i') }}</div>
                                            <div class="text-muted small">{{ $link->createdBy?->name ?: 'Sistema' }}</div>
                                        </td>
                                        <td>{{ $link->usedClient?->full_name ?: 'Sin uso' }}</td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-text="{{ $publicUrl }}">Copiar</button>
                                                @if ($waUrl && ! $link->used_at && ! $link->revoked_at)
                                                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-success">
                                                        <i class="fa-brands fa-whatsapp"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">No hay links generados todavía.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-copy-text]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copyText || '');
                button.textContent = 'Copiado';
                setTimeout(() => { button.textContent = 'Copiar'; }, 1600);
            } catch (error) {
                console.warn('No se pudo copiar el link.', error);
            }
        });
    });
</script>
@endpush
