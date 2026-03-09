<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Acceso al Portal B2B</h1>
        <p class="mt-1 text-sm text-slate-600">Ingresa con tu cuenta para operar catálogo, pedidos y documentos.</p>
    </div>

    <x-auth-session-status :status="session('status')" class="mb-4" />

    <form method="POST" action="{{ route('login') }}" data-loading-form class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Contraseña')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">¿Olvidaste tu contraseña?</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" class="form-checkbox" name="remember">
            <span>Mantener sesión activa</span>
        </label>

        <x-ui.button type="submit" variant="primary" class="w-full justify-center" data-loading-label="Ingresando...">Iniciar sesión</x-ui.button>
    </form>
</x-guest-layout>
