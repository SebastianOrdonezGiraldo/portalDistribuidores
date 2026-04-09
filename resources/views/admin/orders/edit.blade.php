<x-app-layout>
    @php
        $formatQty = function (float|int|string|null $value): string {
            $n = (float) ($value ?? 0);
            $isInt = abs($n - round($n)) < 0.00001;

            return number_format($n, $isInt ? 0 : 2, ',', '.');
        };
    @endphp

    <x-slot name="header">
        <x-ui.page-header
            title="Editar Pedido {{ $order->oc_number }}"
            subtitle="Ajusta datos comerciales e ítems para actualizar la cotización."
        >
            <x-slot name="actions">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary w-full justify-center sm:w-auto">
                    Volver al detalle
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form action="{{ route('admin.orders.update', $order) }}" method="POST" class="grid gap-4 lg:grid-cols-[1.7fr_1fr]">
        @csrf
        @method('PUT')

        <x-ui.card class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="card-title">Datos comerciales</h2>
                    <p class="mt-1 text-xs text-slate-500">Estado actual: {{ $order->status->label() }}</p>
                </div>
                <x-ui.status-badge :status="$order->status" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="company_name">Razón social *</label>
                    <x-ui.input id="company_name" name="company_name" :value="old('company_name', $order->company_name)" required />
                    <x-input-error :messages="$errors->get('company_name')" />
                </div>
                <div>
                    <label class="form-label" for="company_nit">NIT / Cédula *</label>
                    <x-ui.input
                        id="company_nit"
                        name="company_nit"
                        :value="old('company_nit', $order->company_nit)"
                        inputmode="numeric"
                        pattern="[0-9]+"
                        required
                    />
                    <x-input-error :messages="$errors->get('company_nit')" />
                </div>
                <div>
                    <label class="form-label" for="contact_name">Nombre de contacto *</label>
                    <x-ui.input id="contact_name" name="contact_name" :value="old('contact_name', $order->contact_name)" required />
                    <x-input-error :messages="$errors->get('contact_name')" />
                </div>
                <div>
                    <label class="form-label" for="contact_email">Correo de contacto *</label>
                    <x-ui.input id="contact_email" type="email" name="contact_email" :value="old('contact_email', $order->contact_email)" required />
                    <x-input-error :messages="$errors->get('contact_email')" />
                </div>
                <div>
                    <label class="form-label" for="phone">Teléfono *</label>
                    <x-ui.input id="phone" name="phone" :value="old('phone', $order->phone)" required />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>
                <div>
                    <label class="form-label" for="company_address">Dirección *</label>
                    <x-ui.input id="company_address" name="company_address" :value="old('company_address', $order->company_address)" required />
                    <x-input-error :messages="$errors->get('company_address')" />
                </div>
                <div>
                    <label class="form-label" for="city">Ciudad *</label>
                    <x-ui.input id="city" name="city" :value="old('city', $order->city)" required />
                    <x-input-error :messages="$errors->get('city')" />
                </div>
                <div>
                    <label class="form-label" for="department">Departamento *</label>
                    <x-ui.select id="department" name="department" required>
                        <option value="">Selecciona un departamento</option>
                        @foreach($departments as $department)
                            <option value="{{ $department }}" @selected(old('department', $order->department) === $department)>{{ $department }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-input-error :messages="$errors->get('department')" />
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="notes">Observaciones</label>
                    <x-ui.textarea id="notes" name="notes" rows="4">{{ old('notes', $order->notes) }}</x-ui.textarea>
                    <x-input-error :messages="$errors->get('notes')" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="p-5 lg:row-span-2">
            <h2 class="card-title">Ítems y cantidades</h2>
            <p class="mt-1 text-xs text-slate-500">Si colocas una cantidad en 0, el ítem se elimina de la cotización.</p>

            <div class="mt-4 space-y-3">
                @foreach($order->items as $item)
                    @php
                        $index = $loop->index;
                        $qtyInputName = "items.{$index}.qty";
                        $unitInputName = "items.{$index}.unit_label";
                        $qtyValue = (int) old($qtyInputName, (int) $item->qty);
                        $unitValue = old($unitInputName, $item->unit_label);
                    @endphp

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">

                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $item->product_name_snapshot }}</p>
                                <p class="text-xs text-slate-500">SKU: {{ $item->sku_snapshot }}</p>
                                @if($item->variant_value_snapshot)
                                    <p class="text-xs text-slate-500">{{ $item->variant_attribute_snapshot ?? 'Variante' }}: {{ $item->variant_value_snapshot }}</p>
                                @endif
                            </div>
                            <p class="text-xs text-slate-600">Precio unitario: <strong>${{ number_format((float) $item->price_each, 0, ',', '.') }}</strong></p>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Cantidad</label>
                                <x-ui.input
                                    type="number"
                                    min="0"
                                    step="1"
                                    name="items[{{ $index }}][qty]"
                                    :value="$qtyValue"
                                    required
                                    data-line-qty
                                    data-line-price="{{ (float) $item->price_each }}"
                                    data-line-subtotal-id="line-subtotal-{{ $item->id }}"
                                />
                                <x-input-error :messages="$errors->get($qtyInputName)" />
                            </div>
                            <div>
                                <label class="form-label">Unidad</label>
                                <x-ui.input
                                    name="items[{{ $index }}][unit_label]"
                                    :value="$unitValue"
                                    maxlength="40"
                                    required
                                />
                                <x-input-error :messages="$errors->get($unitInputName)" />
                            </div>
                        </div>

                        <p class="mt-2 text-xs text-slate-600">
                            Subtotal estimado:
                            <strong id="line-subtotal-{{ $item->id }}">
                                ${{ number_format(max(0, $qtyValue) * (float) $item->price_each, 0, ',', '.') }}
                            </strong>
                        </p>
                    </div>
                @endforeach
            </div>

            <x-input-error :messages="$errors->get('items')" class="mt-3" />

            <div class="mt-4 border-t border-slate-200 pt-4">
                <p class="text-sm text-slate-600">Total actual</p>
                <p class="text-2xl font-semibold text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">El total final se recalcula al guardar y el PDF se regenera con estos cambios.</p>
            </div>

            <div class="mt-4 flex flex-col gap-2">
                <x-ui.button type="submit" variant="primary" class="w-full justify-center">Guardar cambios</x-ui.button>
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary w-full justify-center">Cancelar</a>
            </div>
        </x-ui.card>
    </form>

    @push('scripts')
        <script>
            (function () {
                const formatter = new Intl.NumberFormat('es-CO');

                const recalcLine = (input) => {
                    const price = Number(input.dataset.linePrice || 0);
                    const qty = Math.max(0, Number(input.value || 0));
                    const subtotal = qty * price;
                    const subtotalEl = document.getElementById(input.dataset.lineSubtotalId || '');

                    if (subtotalEl) {
                        subtotalEl.textContent = `$${formatter.format(Math.round(subtotal))}`;
                    }
                };

                document.querySelectorAll('[data-line-qty]').forEach((input) => {
                    recalcLine(input);
                    input.addEventListener('input', () => recalcLine(input));
                });
            })();
        </script>
    @endpush
</x-app-layout>

