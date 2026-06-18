<x-app-layout>
    @php
        $defaultContactName = $distributor?->contact_name ?: auth()->user()?->name;
        $defaultContactEmail = $distributor?->contact_email ?: auth()->user()?->email;
    @endphp

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

            {{-- Selector de sucursal (solo si la empresa tiene sucursales registradas) --}}
            @if(isset($branches) && $branches->isNotEmpty())
                <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3">
                    <label class="form-label mb-1" for="branch_select">Dirección de entrega (sucursal)</label>
                    <select id="branch_select" class="form-input"
                        onchange="applyBranch(this)">
                        <option value="">— Ingresar dirección manualmente —</option>
                        @foreach($branches as $branch)
                            <option
                                value="{{ $branch->id }}"
                                data-address="{{ $branch->address }}"
                                data-city="{{ $branch->city }}"
                                {{ $branch->is_default ? 'selected' : '' }}
                            >
                                {{ $branch->name }}{{ $branch->is_default ? ' (predeterminada)' : '' }}
                                @if($branch->city) — {{ $branch->city }} @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-blue-600">Selecciona una sucursal para prellenar la dirección automáticamente.</p>
                </div>
            @endif

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="company_name">Razón social *</label>
                    <x-ui.input id="company_name" name="company_name" :value="old('company_name', $distributor?->name)" required />
                    <x-input-error :messages="$errors->get('company_name')" />
                </div>
                <div>
                    <label class="form-label" for="company_nit">NIT / Cédula *</label>
                    <x-ui.input
                        id="company_nit"
                        name="company_nit"
                        :value="old('company_nit', $distributor?->nit)"
                        inputmode="numeric"
                        pattern="[0-9]+"
                        placeholder="9001234567"
                        required
                    />
                    <p class="form-help">Ingresa solo números, sin puntos, espacios ni guiones.</p>
                    <x-input-error :messages="$errors->get('company_nit')" />
                </div>
                <div>
                    <label class="form-label" for="contact_name">Nombre de contacto *</label>
                    <x-ui.input id="contact_name" name="contact_name" :value="old('contact_name', $defaultContactName)" required />
                    <x-input-error :messages="$errors->get('contact_name')" />
                </div>
                <div>
                    <label class="form-label" for="contact_email">Correo de contacto *</label>
                    <x-ui.input id="contact_email" type="email" name="contact_email" :value="old('contact_email', $defaultContactEmail)" required />
                    <x-input-error :messages="$errors->get('contact_email')" />
                </div>
                <div>
                    <label class="form-label" for="phone">Teléfono *</label>
                    <x-ui.input id="phone" type="tel" name="phone" :value="old('phone', $distributor?->phone)" placeholder="300 000 0000" required />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>
                <div>
                    <label class="form-label" for="company_address">Dirección *</label>
                    <x-ui.input id="company_address" name="company_address"
                        :value="old('company_address', $distributor?->address)"
                        required />
                    <x-input-error :messages="$errors->get('company_address')" />
                </div>
                <div>
                    <label class="form-label" for="city">Ciudad *</label>
                    <x-ui.input id="city" name="city"
                        :value="old('city', $distributor?->city)"
                        required />
                    <x-input-error :messages="$errors->get('city')" />
                </div>
                <div>
                    <label class="form-label" for="department">Departamento *</label>
                    <x-ui.select id="department" name="department" required>
                        <option value="">Selecciona un departamento</option>
                        @foreach($departments as $department)
                            <option value="{{ $department }}" @selected(old('department') === $department)>{{ $department }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-input-error :messages="$errors->get('department')" />
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
                        <p class="text-xs text-slate-500">{{ (int) $item['qty'] }} {{ $item['unit_label'] }} x ${{ number_format((float) $item['unit_price'], 0, ',', '.') }} · {{ $item['vat_label'] }}</p>
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

    @push('scripts')
        <script>
            function applyBranch(select) {
                const opt = select.options[select.selectedIndex];
                if (!opt || !opt.value) return;
                const addr = opt.dataset.address || '';
                const city = opt.dataset.city || '';
                if (addr) document.getElementById('company_address').value = addr;
                if (city) document.getElementById('city').value = city;
            }

            // Aplicar la sucursal predeterminada al cargar si no hay old() values
            document.addEventListener('DOMContentLoaded', function () {
                const sel = document.getElementById('branch_select');
                if (sel && sel.value) {
                    applyBranch(sel);
                }
            });
        </script>
    @endpush
</x-app-layout>
