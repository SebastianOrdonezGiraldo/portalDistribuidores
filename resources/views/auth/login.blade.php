<x-guest-layout>
    <div class="mb-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-primary">Portal mayorista</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Iniciar sesión</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">Ingresa tus credenciales para acceder al portal.</p>
    </div>

    <x-auth-session-status :status="session('status')" class="mb-4" />

    <form method="POST" action="{{ route('login') }}" data-loading-form class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" placeholder="ejemplo@distribuidor.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Contraseña')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">¿Olvidaste tu contraseña?</a>
                @endif
            </div>
            <div x-data="{ visible: false }" class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-11" type="password" x-bind:type="visible ? 'text' : 'password'" name="password" placeholder="Ingresa tu contraseña" required autocomplete="current-password" />
                <button type="button" @click="visible = !visible" x-bind:aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" x-bind:aria-pressed="visible" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-slate-400 transition hover:text-brand-dark focus-ring">
                    <svg x-show="!visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    <svg x-cloak x-show="visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18.5 18.5 0 0 1-3.3 3.8"/><path d="M6.2 6.2C3.6 8 2 12 2 12s3.5 6 10 6a10.7 10.7 0 0 0 3.4-.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" class="form-checkbox" name="remember">
            <span>Mantener sesión activa</span>
        </label>

        <x-ui.button type="submit" variant="primary" class="w-full justify-center !py-3" data-loading-label="Ingresando...">Iniciar sesión</x-ui.button>

        @if(config('auth.allow_public_registration') && Route::has('register'))
            <p class="border-t border-slate-200 pt-5 text-center text-sm text-slate-600">
                ¿Aún no tienes una cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-brand-dark hover:underline">Crea tu cuenta de distribuidor</a>
            </p>
        @endif
    </form>
</x-guest-layout>
