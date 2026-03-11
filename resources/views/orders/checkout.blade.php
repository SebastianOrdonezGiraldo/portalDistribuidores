<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Confirmar Pedido" subtitle="Verifica datos de contacto, dirección y observaciones antes de enviar la CTC.">
            <x-slot name="actions">
                <a href="{{ route('cart.index') }}" class="btn btn-secondary">Volver al carrito</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form action="{{ route('orders.store') }}" method="POST" data-loading-form class="grid gap-4 lg:grid-cols-[1.7fr_1fr]">
        @csrf

        <x-ui.card class="p-5">
            <h2 class="card-title">Datos comerciales</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="company_name">Razón social *</label>
                    <x-ui.input id="company_name" name="company_name" :value="old('company_name', auth()->user()?->distributor?->name)" required />
                    <x-input-error :messages="$errors->get('company_name')" />
                </div>
                <div>
                    <label class="form-label" for="company_nit">NIT / Cédula *</label>
                    <x-ui.input id="company_nit" name="company_nit" :value="old('company_nit')" required />
                    <x-input-error :messages="$errors->get('company_nit')" />
                </div>
                <div>
                    <label class="form-label" for="contact_name">Numero de contacto *</label>
                    <x-ui.input id="contact_name" name="contact_name" :value="old('contact_name', auth()->user()?->name)" required />
                    <x-input-error :messages="$errors->get('contact_name')" />
                </div>
                <div>
                    <label class="form-label" for="contact_email">Correo de contacto *</label>
                    <x-ui.input id="contact_email" type="email" name="contact_email" :value="old('contact_email', auth()->user()?->email)" required />
                    <x-input-error :messages="$errors->get('contact_email')" />
                </div>
                <div>
                    <label class="form-label" for="company_address">Dirección *</label>
                    <x-ui.input id="company_address" name="company_address" :value="old('company_address')" required />
                    <x-input-error :messages="$errors->get('company_address')" />
                </div>
                <div>
                    <label class="form-label" for="city">Ciudad *</label>
                    <x-ui.input id="city" name="city" :value="old('city')" required />
                    <x-input-error :messages="$errors->get('city')" />
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="notes">Observaciones operativas</label>
                    <x-ui.textarea id="notes" name="notes" rows="4">{{ old('notes') }}</x-ui.textarea>
                    <p class="form-help">Incluye referencias de entrega, horarios o datos de recepción.</p>
                    <x-input-error :messages="$errors->get('notes')" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="p-5">
            <h2 class="card-title">Resumen del pedido</h2>
            <div class="mt-4 space-y-2">
                @foreach($items as $item)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                        <p class="font-medium text-slate-900">{{ $item['product']->name }}</p>
                        @if($item['variant_label'])
                            <p class="text-xs text-slate-500">{{ $item['variant_label'] }}</p>
                        @endif
                        <p class="text-xs text-slate-500">{{ (int) $item['qty'] }} {{ $item['unit_label'] }} x ${{ number_format((float) $item['unit_price'], 0, ',', '.') }}</p>
                        <p class="mt-1 font-semibold text-slate-900">Subtotal: ${{ number_format((float) $item['subtotal'], 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Total estimado</span>
                    <span class="text-2xl font-semibold text-slate-900">${{ number_format((float) $total, 0, ',', '.') }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-500">La disponibilidad final se confirma con el equipo comercial.</p>
            </div>

            <div class="mt-4 flex flex-col gap-2">
                <x-ui.button type="submit" variant="primary" class="w-full justify-center" data-loading-label="Enviando pedido...">Confirmar pedido</x-ui.button>
                <a href="{{ route('cart.index') }}" class="btn btn-secondary w-full justify-center">Editar carrito</a>
            </div>
        </x-ui.card>
    </form>
</x-app-layout>
