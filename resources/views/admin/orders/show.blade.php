<x-app-layout>
    @php
        $formatQuantity = function (float|int|string|null $value): string {
            $number = (float) ($value ?? 0);
            $isInteger = abs($number - round($number)) < 0.00001;

            return number_format($number, $isInteger ? 0 : 2, ',', '.');
        };

        $hasTransitions = $nextStatuses->isNotEmpty();
        $statusFormId = 'order-status-form';
        $selectedStatus = old('status', $recommendedAction['value'] ?? '');
        $selectedStatusOption = $nextStatuses->firstWhere('value', $selectedStatus);
        $selectedRequiresNote = is_array($selectedStatusOption) ? (bool) ($selectedStatusOption['requires_note'] ?? false) : false;
        $primaryCtaLabel = is_array($recommendedAction) ? ($recommendedAction['cta'] ?? 'Actualizar estado') : 'Actualizar estado';
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Pedido {{ $order->oc_number }}" subtitle="Resumen operativo para validar información, actualizar estado y mantener control del proceso.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Creado: {{ $order->created_at?->format('d/m/Y H:i') ?? '-' }}</span>
                    <span class="stat-pill">Actualizado: {{ $order->updated_at?->diffForHumans() ?? '-' }}</span>
                    <span class="stat-pill">Ítems: {{ number_format($totals['items_count']) }}</span>
                    <span class="stat-pill">Unidades: {{ $formatQuantity($totals['units_total']) }}</span>
                    <span class="stat-pill">Total: ${{ number_format((float) $order->total_amount, 0, ',', '.') }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Volver al listado</a>
                <a href="{{ route('admin.orders.pdf', $order) }}" class="btn btn-primary w-full justify-center sm:w-auto">Descargar PDF</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="space-y-4 lg:flex lg:items-start lg:gap-6 lg:space-y-0">
        <div class="space-y-4 lg:min-w-0 lg:flex-1">
            <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="card-title">Resumen Comercial</h2>
                    <p class="mt-1 text-sm text-slate-600">Información principal para validar el pedido rápidamente.</p>
                </div>
                <x-ui.status-badge :status="$order->status" class="shrink-0" />
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">CTC</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $order->oc_number }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Monto total</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Ítems</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($totals['items_count']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Unidades</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $formatQuantity($totals['units_total']) }}</p>
                </div>
            </div>

            @if($hasFinancialGap)
                <div class="mt-4">
                    <x-ui.alert variant="warning" title="Diferencia detectada en el total">
                        La suma de subtotales (${{ number_format($totals['subtotals_total'], 0, ',', '.') }}) no coincide con el total registrado del pedido.
                    </x-ui.alert>
                </div>
            @endif
        </x-ui.card>

            <x-ui.card>
            <h2 class="card-title">Cliente y contacto</h2>
            <p class="mt-1 text-sm text-slate-600">Datos para validación comercial y comunicación inmediata.</p>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Cliente</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $order->company_name }}</dd>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">NIT / Cédula</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $order->company_nit ?? '-' }}</dd>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Contacto</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $order->contact_name }}</dd>
                    @if($order->contact_email)
                        <p class="text-xs text-slate-600">
                            <a href="mailto:{{ $order->contact_email }}" class="focus-ring rounded text-brand-dark underline-offset-2 hover:underline">{{ $order->contact_email }}</a>
                        </p>
                    @else
                        <p class="text-xs text-slate-500">Sin correo registrado.</p>
                    @endif
                    @if($order->phone)
                        <p class="text-xs text-slate-600">
                            <a href="tel:{{ preg_replace('/\s+/', '', $order->phone) }}" class="focus-ring rounded text-brand-dark underline-offset-2 hover:underline">{{ $order->phone }}</a>
                        </p>
                    @else
                        <p class="text-xs text-slate-500">Sin teléfono registrado.</p>
                    @endif
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Distribuidor / Usuario</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $order->distributor?->name ?? '-' }}</dd>
                    <p class="text-xs text-slate-500">{{ $order->user?->email }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Dirección, ciudad y departamento</dt>
                    <dd class="mt-1 font-semibold text-slate-900">
                        {{ $order->company_address ?? '-' }} · {{ $order->city ?? '-' }} · {{ $order->department ?? '-' }}
                    </dd>
                </div>
            </dl>
        </x-ui.card>

            <x-ui.card>
            <x-slot name="header">
                <div>
                    <h2 class="card-title">Ítems del Pedido</h2>
                    <p class="mt-1 text-xs text-slate-500">Detalle de cantidades, precio unitario y subtotal.</p>
                </div>
            </x-slot>

            <div class="p-5 pt-0">
                @if($order->items->isEmpty())
                    <x-ui.empty-state
                        title="Sin ítems registrados"
                        description="No se encontraron líneas de producto asociadas a este pedido."
                        compact
                    />
                @else
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $index => $item)
                                <tr>
                                    <td data-label="#" class="text-xs text-slate-500">{{ $index + 1 }}</td>
                                    <td data-label="SKU" class="font-medium text-slate-900">{{ $item->sku_snapshot }}</td>
                                    <td data-label="Producto" data-full="true">
                                        <p class="font-medium text-slate-900">{{ $item->product_name_snapshot }}</p>
                                        @if($item->variant_value_snapshot)
                                            <p class="text-xs text-slate-500">
                                                {{ $item->variant_attribute_snapshot ?? 'Variante' }}: {{ $item->variant_value_snapshot }}
                                            </p>
                                        @endif
                                    </td>
                                    <td data-label="Cantidad">{{ $formatQuantity($item->qty) }} {{ $item->unit_label }}</td>
                                    <td data-label="Precio">
                                        ${{ number_format((float) $item->price_each, 0, ',', '.') }}
                                        <span class="mt-1 block text-xs text-slate-500">
                                            {{ \App\Modules\Orders\Support\OrderLineVat::label((bool) $item->is_vat_excluded_snapshot) }}
                                        </span>
                                    </td>
                                    <td data-label="Subtotal" class="font-semibold text-slate-900">${{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>

                    <div class="mt-4 grid gap-2 border-t border-slate-200 pt-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Subtotal ítems</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">${{ number_format($totals['subtotals_total'], 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Promedio unitario</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">${{ number_format($totals['average_unit_price'], 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Total pedido</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </x-ui.card>

            @if($order->notes)
                <x-ui.card>
                <h2 class="card-title">Observaciones</h2>
                <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $order->notes }}</p>
                </x-ui.card>
            @endif
        </div>

        <aside class="relative space-y-4 lg:w-full lg:max-w-md lg:flex-shrink-0 lg:self-start xl:max-w-lg">
        {{-- Flujo normal en columna: evita que un panel fixed tape Documentación / Zona de peligro al hacer scroll --}}
        <div class="w-full lg:max-h-[calc(100dvh-8rem)] lg:overflow-y-auto lg:overflow-x-hidden">
        <x-ui.card>
            <h2 class="card-title">Checklist Operativo</h2>
            <p class="mt-1 text-xs text-slate-500">Ejecuta la siguiente transición de estado y deja nota cuando aplique.</p>

            @if($hasTransitions)
                <form id="{{ $statusFormId }}" action="{{ route('admin.orders.status', $order) }}" method="POST" class="mt-4 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-3" data-status-form>
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="form-label" for="order-status-next">Nuevo estado</label>
                        <x-ui.select
                            id="order-status-next"
                            name="status"
                            required
                            data-status-select
                            aria-invalid="{{ $errors->has('status') ? 'true' : 'false' }}"
                            aria-describedby="order-status-next-help{{ $errors->has('status') ? ' order-status-next-error' : '' }}"
                        >
                            <option value="">Selecciona un estado</option>
                            @foreach($nextStatuses as $statusOption)
                                <option
                                    value="{{ $statusOption['value'] }}"
                                    data-requires-note="{{ $statusOption['requires_note'] ? 'true' : 'false' }}"
                                    @selected($selectedStatus === $statusOption['value'])
                                >
                                    {{ $statusOption['label'] }}
                                </option>
                            @endforeach
                        </x-ui.select>
                        <p id="order-status-next-help" class="mt-1 text-xs text-slate-500">Te sugerimos: {{ is_array($recommendedAction) ? $recommendedAction['label'] : 'elige una transición permitida' }}.</p>
                        <x-input-error id="order-status-next-error" :messages="$errors->get('status')" />
                    </div>

                    <div>
                        <label class="form-label" for="order-status-note">Nota de trazabilidad</label>
                        <x-ui.textarea
                            id="order-status-note"
                            name="note"
                            rows="3"
                            placeholder="Ejemplo: Se confirma salida de bodega con guía #..."
                            data-status-note
                            aria-invalid="{{ $errors->has('note') ? 'true' : 'false' }}"
                            aria-describedby="order-status-note-help{{ $errors->has('note') ? ' order-status-note-error' : '' }}"
                            aria-required="{{ $selectedRequiresNote ? 'true' : 'false' }}"
                            :required="$selectedRequiresNote"
                        >{{ old('note') }}</x-ui.textarea>
                        <p id="order-status-note-help" class="mt-1 text-xs text-slate-500" data-status-note-help>
                            {{ $selectedRequiresNote ? 'Nota obligatoria para este cambio de estado.' : 'Nota opcional para dejar contexto operativo.' }}
                        </p>
                        <x-input-error id="order-status-note-error" :messages="$errors->get('note')" />
                    </div>

                    <x-ui.button
                        type="submit"
                        variant="primary"
                        class="hidden w-full justify-center sm:inline-flex sm:w-auto"
                        data-status-submit
                    >
                        {{ $primaryCtaLabel }}
                    </x-ui.button>
                </form>
            @else
                <div class="mt-4">
                    <x-ui.alert variant="info" title="Sin transiciones disponibles">
                        El pedido está en estado <strong>{{ $order->status->label() }}</strong> y no tiene cambios de estado habilitados.
                    </x-ui.alert>
                </div>
            @endif
        </x-ui.card>
        </div>

        <x-ui.card>
            <h2 class="card-title">Historial de estados</h2>
            <ol class="mt-4 space-y-3">
                @foreach($timeline as $event)
                    <li class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-medium text-slate-900">{{ $event['title'] }}</p>
                            <x-ui.status-badge :status="$event['status']" class="shrink-0" />
                        </div>
                        <p class="mt-1 text-xs text-slate-600">{{ $event['description'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $event['at']?->format('d/m/Y H:i') }}</p>
                    </li>
                @endforeach
            </ol>
        </x-ui.card>

        <x-ui.card>
            <h2 class="card-title">Documentación</h2>
            <div class="mt-3 space-y-2">
                @if($order->pdf_path)
                    <x-ui.alert variant="success" title="PDF listo para descarga">
                        Documento comercial generado y almacenado.
                    </x-ui.alert>
                @else
                    <x-ui.alert variant="warning" title="PDF pendiente">
                        El PDF aún no está disponible. Vuelve a intentarlo en unos minutos.
                    </x-ui.alert>
                @endif
            </div>

            @if($secondaryActions->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-200 pt-3">
                    @foreach($secondaryActions as $action)
                        <a href="{{ $action['href'] }}" class="btn btn-secondary w-full justify-center sm:w-auto">{{ $action['label'] }}</a>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        <x-ui.card class="danger-zone">
            <h2 class="card-title">Zona de peligro</h2>
            <p class="mt-1 text-xs text-slate-600">Acción irreversible. Úsala solo cuando sea estrictamente necesario.</p>

            <div class="mt-4 space-y-2">
                @foreach($dangerActions as $dangerAction)
                    <form action="{{ $dangerAction['action'] }}" method="POST" data-confirm="{{ $dangerAction['confirm'] }}" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-full justify-center">{{ $dangerAction['label'] }}</button>
                    </form>
                @endforeach
            </div>
        </x-ui.card>
        </aside>
    </section>

    @if($hasTransitions)
        <div class="fixed inset-x-3 bottom-3 z-50 sm:hidden">
            <x-ui.button
                type="submit"
                form="{{ $statusFormId }}"
                variant="primary"
                class="w-full justify-center shadow-panel"
                data-status-submit
                data-mobile-status-submit
                disabled
            >
                {{ $primaryCtaLabel }}
            </x-ui.button>
        </div>
    @endif
</x-app-layout>
