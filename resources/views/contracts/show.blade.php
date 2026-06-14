@extends('layouts.app')

@section('title', $contract->contract_number.' - '.config('app.name'))

@php
    $badgeMap = [
        'draft' => 'secondary', 'generated' => 'info', 'sent' => 'primary', 'viewed' => 'warning',
        'signed' => 'success', 'rejected' => 'danger', 'cancelled' => 'dark', 'expired' => 'secondary',
    ];
@endphp

@section('content')
    <section class="mb-4 d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Contrato {{ $contract->contract_number }}</h1>
            <p class="text-muted mb-0">
                {{ $contract->client->full_name }} · Préstamo {{ $contract->loan->loan_number }} ·
                <span class="badge bg-{{ $badgeMap[$contract->status] ?? 'secondary' }}">{{ $statuses[$contract->status] ?? $contract->status }}</span>
                · v{{ $contract->version }}
            </p>
        </div>
        <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">Volver</a>
    </section>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <section class="card content-card mb-4">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Acciones</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($contract->document)
                            <a href="{{ route('contracts.download', $contract->uuid) }}" class="btn btn-outline-primary">Descargar PDF</a>
                        @endif

                        @unless ($contract->isFinalized())
                            <form method="POST" action="{{ route('contracts.send', $contract->uuid) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Marcar como enviado</button>
                            </form>
                            @if ($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-success">Enviar por WhatsApp</a>
                            @endif
                            <form method="POST" action="{{ route('contracts.regenerate', $contract->uuid) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary">Regenerar</button>
                            </form>
                            <form method="POST" action="{{ route('contracts.cancel', $contract->uuid) }}" onsubmit="return confirm('¿Anular este contrato?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger">Anular</button>
                            </form>
                        @endunless
                    </div>

                    @if ($signingUrl)
                        <div class="mt-3">
                            <label class="form-label small text-muted">Enlace de firma (válido temporalmente)</label>
                            <input type="text" class="form-control form-control-sm" value="{{ $signingUrl }}" readonly onclick="this.select()">
                        </div>
                    @endif
                </div>
            </section>

            <section class="card content-card">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Historial de eventos</h2>
                    <ul class="list-unstyled mb-0">
                        @forelse ($contract->events as $event)
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span><strong>{{ $event->event_type }}</strong> — {{ $event->description }}</span>
                                <span class="text-muted small">{{ $event->created_at?->format('d/m/Y H:i') }}</span>
                            </li>
                        @empty
                            <li class="text-muted">Sin eventos.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-5">
            <section class="card content-card mb-4">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Verificación</h2>
                    <div class="small text-muted mb-1">Código</div>
                    <div class="mb-2">{{ $contract->uuid }}</div>
                    <div class="small text-muted mb-1">Hash SHA-256</div>
                    <div class="mb-2" style="font-family: monospace; font-size: 11px; word-break: break-all;">{{ $contract->hash_sha256 }}</div>
                    <a href="{{ route('contracts.verify', $contract->uuid) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Página de verificación</a>
                </div>
            </section>

            @if ($contract->signatures->isNotEmpty())
                <section class="card content-card">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted mb-3">Evidencia de firma</h2>
                        @foreach ($contract->signatures as $sig)
                            <div class="border rounded-3 p-3 mb-2">
                                <div><strong>{{ $sig->signer_name }}</strong></div>
                                <div class="small text-muted">Firmado: {{ $sig->signed_at?->format('d/m/Y H:i') }}</div>
                                <div class="small text-muted">IP: {{ $sig->ip_address ?: 'N/D' }}</div>
                                <div class="small text-muted">Dispositivo: {{ $sig->device_type ?: 'N/D' }} · {{ $sig->platform ?: 'N/D' }} · {{ $sig->browser ?: 'N/D' }}</div>
                                @if ($sig->latitude && $sig->longitude)
                                    <div class="small text-muted">GPS: {{ $sig->latitude }}, {{ $sig->longitude }}</div>
                                @endif
                                <div class="small text-muted">Términos: {{ $sig->accepted_terms ? 'Sí' : 'No' }} · Legal: {{ $sig->accepted_legal ? 'Sí' : 'No' }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
