@extends('layouts.guest')

@section('title', 'Iniciar sesion - '.config('app.name'))

@section('content')
<main class="w-full max-w-md px-4 flex-grow flex flex-col items-center justify-center py-10">
    <div class="flex flex-col items-center mb-10 animate-slide-up">
        <div class="w-20 h-20 bg-primary-container rounded-2xl flex items-center justify-center shadow-sm mb-4">
            <span class="material-symbols-outlined text-on-primary-container" style="font-size:40px; font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">account_balance</span>
        </div>
        <h1 class="text-2xl font-semibold text-primary tracking-tight mt-2">{{ config('app.name') }}</h1>
        <p class="text-sm text-on-surface-variant mt-1">Gestion financiera profesional</p>
    </div>

    <div class="w-full bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-outline-variant/30 animate-slide-up">
        @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 bg-error-container text-on-error-container px-4 py-3 rounded-xl text-sm">
                <span class="material-symbols-outlined shrink-0" style="font-size:20px;">error</span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" novalidate class="space-y-5">
            @csrf

            <div class="space-y-1">
                <label class="block text-sm font-medium text-on-surface-variant ml-1" for="email">Correo electronico</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                        <span class="material-symbols-outlined" style="font-size:20px;">mail</span>
                    </div>
                    <input
                        id="email" name="email" type="email"
                        value="{{ old('email') }}"
                        autocomplete="email" autofocus required
                        placeholder="usuario@dominio.do"
                        class="block w-full pl-11 pr-4 py-3.5 bg-transparent border {{ $errors->has('email') ? 'border-error' : 'border-outline-variant' }} rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all text-sm text-on-surface placeholder:text-outline outline-none">
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-on-surface-variant ml-1" for="password">Contrasena</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                        <span class="material-symbols-outlined" style="font-size:20px;">lock</span>
                    </div>
                    <input
                        id="password" name="password" type="password"
                        autocomplete="current-password" required
                        placeholder="********"
                        class="block w-full pl-11 pr-12 py-3.5 bg-transparent border {{ $errors->has('password') ? 'border-error' : 'border-outline-variant' }} rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all text-sm text-on-surface placeholder:text-outline outline-none">
                    <button type="button" data-toggle-password="#password" class="absolute inset-y-0 right-0 pr-4 flex items-center text-outline-variant hover:text-on-surface-variant transition-colors">
                        <span class="material-symbols-outlined" data-password-icon style="font-size:20px;">visibility</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" id="remember" value="1"
                        class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary">
                    <span class="text-sm text-on-surface-variant">Recordarme</span>
                </label>
                <a href="#" class="text-sm font-medium text-primary hover:underline underline-offset-4">
                    Olvidaste tu contrasena?
                </a>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full bg-primary text-on-primary py-4 rounded-xl text-sm font-bold shadow-md active:scale-[0.98] transition-all flex items-center justify-center gap-2 group hover:bg-on-primary-fixed-variant">
                    <span>Iniciar sesion</span>
                    <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform" style="font-size:20px;">arrow_forward</span>
                </button>
            </div>
        </form>
    </div>
</main>

<footer class="w-full pb-8 flex flex-col items-center text-outline px-4">
    <p class="text-xs mb-2">{{ config('app.name') }} &middot; Version {{ config('app.version', '2.0') }}</p>
    <div class="flex gap-4 text-xs">
        <a href="#" class="hover:text-primary transition-colors">Soporte</a>
        <span class="text-outline-variant">&bull;</span>
        <a href="#" class="hover:text-primary transition-colors">Privacidad</a>
    </div>
</footer>
@endsection
