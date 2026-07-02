<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-aubergine">Acceso seguro</p>
        <h1 class="mt-1 font-display text-2xl font-semibold text-brand-ink">Portal B2B médico</h1>
        <p class="mt-1 text-sm text-slate-600">Ingresa para operar catálogo, pedidos CTC y documentos comerciales.</p>
    </div>

    <x-auth-session-status :status="session('status')" class="mb-4" />

    <form method="POST" action="{{ route('login') }}" data-loading-form class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" required />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Contraseña')" required />
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

        @if(config('auth.allow_public_registration') && Route::has('register'))
            <p class="text-center text-sm text-slate-600">
                ¿No tienes una cuenta?
                <a href="{{ route('register') }}" class="font-medium text-slate-900 underline">Crea una cuenta</a>
            </p>
        @endif
    </form>
</x-guest-layout>
