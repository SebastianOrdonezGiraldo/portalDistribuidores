<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-aubergine">Solicitud comercial</p>
        <h1 class="mt-1 font-display text-2xl font-semibold text-brand-ink">Registro de distribuidores</h1>
        <p class="mt-1 text-sm text-slate-600">
            Crea tu solicitud de acceso. El equipo comercial validará tu empresa antes de habilitar la cuenta.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" data-loading-form class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Nombre completo')" required />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="company_name" :value="__('Nombre de empresa')" required />
            <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required autocomplete="organization" />
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nit" :value="__('NIT')" required />
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

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="city" :value="__('Ciudad')" required />
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
                <x-input-label for="phone" :value="__('Teléfono')" required />
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
        </div>

        <div>
            <x-input-label for="address" :value="__('Dirección')" optional />
            <x-text-input id="address" class="block mt-1 w-full" type="text" name="address" :value="old('address')" autocomplete="street-address" />
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" required />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Contraseña')" required />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" required />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a class="rounded-md text-sm font-medium text-slate-600 underline-offset-4 hover:text-brand-ink hover:underline focus-ring" href="{{ route('login') }}">
                {{ __('¿Ya tienes cuenta? Inicia sesión') }}
            </a>

            <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto" data-loading-label="Enviando solicitud...">
                {{ __('Enviar solicitud') }}
            </x-ui.button>
        </div>
    </form>
</x-guest-layout>
