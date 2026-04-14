<x-guest-layout>
    <div class="space-y-5">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Verifica tu correo</h1>
            <p class="mt-1 text-sm text-slate-600">
                Enviamos un código de 6 dígitos a <strong>{{ $email }}</strong>. Ingrésalo para confirmar tu correo y continuar.
            </p>
        </div>

        <x-auth-session-status :status="session('status')" class="mb-2" />

        @if ($errors->has('code'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('code') }}
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
                <x-input-label :value="__('Código de verificación')" class="mb-3" />

                <div class="flex justify-center gap-2" x-ref="container">
                    @for ($i = 0; $i < 6; $i++)
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            x-ref="digit{{ $i }}"
                            @input="onInput($event, {{ $i }})"
                            @keydown="onKeydown($event, {{ $i }})"
                            @focus="$event.target.select()"
                            class="h-14 w-12 rounded-lg border border-slate-300 bg-white text-center text-xl font-bold text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                            autocomplete="off"
                        />
                    @endfor
                </div>

                <input type="hidden" name="code" x-model="combined" />
            </div>

            <x-ui.button type="submit" variant="primary" class="w-full justify-center" :disabled="false">
                Verificar correo
            </x-ui.button>
        </form>

        <div class="border-t border-slate-200 pt-4">
            <p class="text-center text-sm text-slate-500">
                ¿No recibiste el código?
            </p>
            <form method="POST" action="{{ route('register.verify-email.resend') }}" class="mt-2">
                @csrf
                <button
                    type="submit"
                    class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                >
                    Reenviar código
                </button>
            </form>
        </div>
    </div>

    <script>
        function otpForm() {
            return {
                digits: ['', '', '', '', '', ''],

                get combined() {
                    return this.digits.join('');
                },

                onInput(event, index) {
                    const value = event.target.value.replace(/\D/g, '');
                    event.target.value = value.slice(-1);
                    this.digits[index] = event.target.value;

                    if (event.target.value && index < 5) {
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
                    if (event.key === 'ArrowRight' && index < 5) {
                        this.$refs['digit' + (index + 1)].focus();
                    }
                },

                onPaste(event) {
                    const pasted = (event.clipboardData || window.clipboardData)
                        .getData('text')
                        .replace(/\D/g, '')
                        .slice(0, 6);

                    if (!pasted) return;

                    pasted.split('').forEach((char, i) => {
                        if (i < 6) {
                            this.digits[i] = char;
                            this.$refs['digit' + i].value = char;
                        }
                    });

                    const nextEmpty = Math.min(pasted.length, 5);
                    this.$refs['digit' + nextEmpty].focus();
                },
            };
        }
    </script>
</x-guest-layout>
