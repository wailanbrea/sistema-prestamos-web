@extends('layouts.app')

@section('title', 'Nuevo cobrador - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Nuevo cobrador</h1>
        <p class="text-muted mb-0">Registra el cobrador y su esquema de comisión.</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('collectors.store') }}">
                @include('collectors._form')
            </form>
        </div>
    </section>
@endsection
