<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Recuperar acceso</h1>
        <p class="mt-1 text-sm text-slate-600">Te enviaremos un enlace para restablecer tu contraseña en minutos.</p>
    </div>

    <x-auth-session-status :status="session('status')" class="mb-4" />

    <form method="POST" action="{{ route('password.email') }}" data-loading-form class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-turnstile />

        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Volver al login</a>
            <x-ui.button type="submit" variant="primary" data-loading-label="Enviando...">Enviar enlace</x-ui.button>
        </div>
    </form>
</x-guest-layout>
