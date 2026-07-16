<x-guest-layout>
    <div class="space-y-6">
        <div class="text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-dark" aria-hidden="true">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h5M8 16h8"/><path d="m15.5 16 1.3 1.3 2.7-3"/></svg>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-brand-primary">Confirmación de correo</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Ingresa tu código</h1>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">
                Enviamos un código de 4 dígitos a <strong class="font-semibold text-slate-800">{{ $email }}</strong>. Ingrésalo para continuar con tu solicitud.
            </p>
        </div>

        <x-auth-session-status :status="session('status')" class="mb-2" />

        @if ($errors->has('code'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('code') }}
            </div>
        @endif

        @if ($errors->has('resend'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('resend') }}
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('register.verify-email.store') }}"
            x-data="otpForm()"
            @paste.window="onPaste($event)"
            class="space-y-6"
        >
            @csrf

            <div>
                <x-input-label :value="__('Código de 4 dígitos')" class="mb-3 text-center" />

                <div class="flex justify-center gap-3" x-ref="container">
                    @for ($i = 0; $i < 4; $i++)
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            x-ref="digit{{ $i }}"
                            @input="onInput($event, {{ $i }})"
                            @keydown="onKeydown($event, {{ $i }})"
                            @focus="$event.target.select()"
                            class="h-16 w-14 rounded-xl border border-slate-300 bg-slate-50 text-center text-2xl font-bold text-slate-900 shadow-sm transition focus:border-brand-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-primary/15"
                            autocomplete="one-time-code"
                        />
                    @endfor
                </div>

                <input type="hidden" name="code" x-model="combined" />
            </div>

            <x-ui.button type="submit" variant="primary" class="w-full justify-center" :disabled="false">
                Confirmar código
            </x-ui.button>
        </form>

        <div class="border-t border-slate-200 pt-4">
            <p class="text-center text-sm text-slate-500">
                ¿No recibiste el código?
            </p>
            <form
                method="POST"
                action="{{ route('register.verify-email.resend') }}"
                class="mt-2"
                x-data="resendCooldown(@js($resendCooldown))"
                @submit="if (remaining > 0) $event.preventDefault()"
            >
                @csrf
                <button
                    type="submit"
                    :disabled="remaining > 0"
                    class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-500/20 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-100 disabled:text-slate-400"
                >
                    <span x-show="remaining === 0">Reenviar código</span>
                    <span x-cloak x-show="remaining > 0">Reenviar en <span x-text="formatRemaining()"></span></span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function otpForm() {
            return {
                digits: ['', '', '', ''],

                get combined() {
                    return this.digits.join('');
                },

                onInput(event, index) {
                    const value = event.target.value.replace(/\D/g, '');
                    event.target.value = value.slice(-1);
                    this.digits[index] = event.target.value;

                    if (event.target.value && index < 3) {
                        this.$refs['digit' + (index + 1)].focus();
                    }
                },

                onKeydown(event, index) {
                    if (event.key === 'Backspace' && !event.target.value && index > 0) {
                        this.$refs['digit' + (index - 1)].focus();
                    }
                    if (event.key === 'ArrowLeft' && index > 0) {
                        this.$refs['digit' + (index - 1)].focus();
                    }
                    if (event.key === 'ArrowRight' && index < 3) {
                        this.$refs['digit' + (index + 1)].focus();
                    }
                },

                onPaste(event) {
                    const pasted = (event.clipboardData || window.clipboardData)
                        .getData('text')
                        .replace(/\D/g, '')
                        .slice(0, 4);

                    if (!pasted) return;

                    pasted.split('').forEach((char, i) => {
                        if (i < 4) {
                            this.digits[i] = char;
                            this.$refs['digit' + i].value = char;
                        }
                    });

                    const nextEmpty = Math.min(pasted.length, 3);
                    this.$refs['digit' + nextEmpty].focus();
                },
            };
        }

        function resendCooldown(initialSeconds) {
            return {
                remaining: Math.max(0, Number(initialSeconds) || 0),

                init() {
                    if (this.remaining === 0) return;

                    window.setInterval(() => {
                        this.remaining = Math.max(0, this.remaining - 1);
                    }, 1000);
                },

                formatRemaining() {
                    const minutes = Math.floor(this.remaining / 60);
                    const seconds = String(this.remaining % 60).padStart(2, '0');

                    return `${minutes}:${seconds}`;
                },
            };
        }
    </script>
</x-guest-layout>
