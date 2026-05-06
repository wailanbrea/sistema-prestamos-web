@extends('layouts.app')

@section('title', 'Editar usuario - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Editar usuario</h1>
        <p class="text-muted mb-0">{{ $managedUser->email }}</p>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $managedUser) }}">
                @include('users._form', ['method' => 'PUT'])
            </form>
        </div>
    </section>
@endsection
