<x-guest-layout>
    <div class="mb-4">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-primary">Portal mayorista</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Crea tu cuenta</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">
            Crea tu solicitud de acceso. El equipo comercial validará tu empresa antes de habilitar la cuenta.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" data-loading-form class="space-y-5">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('Nombre completo')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="company_name" :value="__('Nombre de empresa')" />
                <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required autocomplete="organization" />
                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="nit" :value="__('NIT')" />
                <x-text-input
                    id="nit"
                    class="block mt-1 w-full"
                    type="text"
                    name="nit"
                    :value="old('nit')"
                    required
                    inputmode="numeric"
                    pattern="[0-9]+"
                    title="Solo números"
                    oninput="this.value=this.value.replace(/\D/g, '')"
                />
                <x-input-error :messages="$errors->get('nit')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="address" :value="__('Dirección (opcional)')" />
                <x-text-input id="address" class="block mt-1 w-full" type="text" name="address" :value="old('address')" autocomplete="street-address" />
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="city" :value="__('Ciudad')" />
                <x-text-input
                    id="city"
                    class="block mt-1 w-full"
                    type="text"
                    name="city"
                    :value="old('city')"
                    required
                    pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü ]+"
                    title="Solo letras"
                    oninput="this.value=this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, '')"
                />
                <x-input-error :messages="$errors->get('city')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" :value="__('Teléfono')" />
                <x-text-input
                    id="phone"
                    class="block mt-1 w-full"
                    type="text"
                    name="phone"
                    :value="old('phone')"
                    required
                    autocomplete="tel"
                    inputmode="numeric"
                    pattern="[0-9]+"
                    title="Solo números"
                    oninput="this.value=this.value.replace(/\D/g, '')"
                />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="email" :value="__('Correo electrónico')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div x-data="{ visible: false }">
                <x-input-label for="password" :value="__('Contraseña')" />
                <div class="relative mt-1">
                    <x-text-input id="password" class="block w-full pr-11" type="password" x-bind:type="visible ? 'text' : 'password'" name="password" required autocomplete="new-password" />
                    <button type="button" @click="visible = !visible" x-bind:aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" x-bind:aria-pressed="visible" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-slate-400 transition hover:text-brand-dark focus-ring">
                        <svg x-show="!visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        <svg x-cloak x-show="visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18.5 18.5 0 0 1-3.3 3.8"/><path d="M6.2 6.2C3.6 8 2 12 2 12s3.5 6 10 6a10.7 10.7 0 0 0 3.4-.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div x-data="{ visible: false }">
                <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                <div class="relative mt-1">
                    <x-text-input id="password_confirmation" class="block w-full pr-11" type="password" x-bind:type="visible ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" />
                    <button type="button" @click="visible = !visible" x-bind:aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" x-bind:aria-pressed="visible" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-slate-400 transition hover:text-brand-dark focus-ring">
                        <svg x-show="!visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        <svg x-cloak x-show="visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18.5 18.5 0 0 1-3.3 3.8"/><path d="M6.2 6.2C3.6 8 2 12 2 12s3.5 6 10 6a10.7 10.7 0 0 0 3.4-.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <a class="rounded text-sm font-medium text-slate-600 hover:text-brand-dark hover:underline focus-ring" href="{{ route('login') }}">
                {{ __('¿Ya tienes cuenta? Inicia sesión') }}
            </a>

            <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto" data-loading-label="Enviando solicitud...">
                {{ __('Enviar solicitud') }}
            </x-ui.button>
        </div>
    </form>
</x-guest-layout>
