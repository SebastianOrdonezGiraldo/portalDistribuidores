<x-app-layout>
    @php
        $formatQuantity = function (float|int|string|null $value): string {
            $number = (float) ($value ?? 0);
            $isInteger = abs($number - round($number)) < 0.00001;

            return number_format($number, $isInteger ? 0 : 2, ',', '.');
        };

        $timeline = $order->statusHistory
            ->map(function ($event) {
                $status = \App\Modules\Shared\Enums\OrderStatus::tryFrom((string) $event->to_status);
                $actor = $event->actor?->name ? ' por '.$event->actor->name : ' por sistema';

                return [
                    'title' => 'Estado: '.($status?->label() ?? strtoupper((string) $event->to_status)),
                    'description' => $event->note ?: 'Cambio registrado'.$actor.'.',
                    'status' => $event->to_status,
                    'at' => $event->created_at,
                ];
            })
            ->values();

        if ($timeline->isEmpty()) {
            $timeline = collect([
                [
                    'title' => 'Pedido creado',
                    'description' => 'Registro inicial de la CTC en el portal.',
                    'status' => $order->status,
                    'at' => $order->created_at,
                ],
            ]);
        }

        $timeline->push(
            $order->pdf_path
                ? [
                    'title' => 'PDF disponible',
                    'description' => 'Documento generado y listo para descarga.',
                    'status' => 'info',
                    'at' => $order->updated_at,
                ]
                : [
                    'title' => 'PDF pendiente',
                    'description' => 'Aún no se encuentra un documento generado.',
                    'status' => 'pending',
                    'at' => $order->updated_at,
                ]
        );

        $timeline = $timeline->sortByDesc('at')->values();
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Pedido {{ $order->oc_number }}" subtitle="Detalle integral para validación comercial, documentación y trazabilidad operativa.">
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

    <section class="grid gap-4 xl:grid-cols-[1.7fr_1fr]">
        <div class="space-y-4">
            <x-ui.card class="p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="card-title">Resumen Comercial</h2>
                        <p class="mt-1 text-sm text-slate-600">Información principal para revisión rápida del pedido.</p>
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
                        <p class="text-xs text-slate-500">{{ $order->contact_email ?? '-' }}</p>
                        <p class="text-xs text-slate-500">{{ $order->phone ?? '-' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Distribuidor / Usuario</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $order->distributor?->name ?? '-' }}</dd>
                        <p class="text-xs text-slate-500">{{ $order->user?->email }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Dirección y Ciudad</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $order->company_address ?? '-' }} · {{ $order->city ?? '-' }}</dd>
                    </div>
                </dl>

                @if($hasFinancialGap)
                    <div class="mt-4">
                        <x-ui.alert variant="warning" title="Diferencia detectada en el total">
                            La suma de subtotales (${{ number_format($totals['subtotals_total'], 0, ',', '.') }}) no coincide con el total del pedido.
                        </x-ui.alert>
                    </div>
                @endif
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
                                        <td data-label="Precio">${{ number_format((float) $item->price_each, 0, ',', '.') }}</td>
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
                <x-ui.card class="p-5">
                    <h2 class="card-title">Observaciones</h2>
                    <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $order->notes }}</p>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Estado y Trazabilidad</h2>
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

            <x-ui.card class="p-5">
                <h2 class="card-title">Documentación y Acciones</h2>
                <div class="mt-3 space-y-2">
                    @if($order->pdf_path)
                        <x-ui.alert variant="success" title="PDF listo para descarga">
                            Documento comercial generado y almacenado.
                        </x-ui.alert>
                    @else
                        <x-ui.alert variant="warning" title="PDF pendiente">
                            El PDF se encuentra en cola o aún no fue generado.
                        </x-ui.alert>
                    @endif
                </div>

                @if($nextStatuses->isNotEmpty())
                    <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="mt-4 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="form-label" for="order-status-next">Cambiar estado</label>
                            <x-ui.select id="order-status-next" name="status" required>
                                <option value="">Selecciona un estado</option>
                                @foreach($nextStatuses as $statusOption)
                                    <option value="{{ $statusOption['value'] }}" @selected(old('status') === $statusOption['value'])>
                                        {{ $statusOption['label'] }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                            <x-input-error :messages="$errors->get('status')" />
                        </div>
                        <div>
                            <label class="form-label" for="order-status-note">Nota de trazabilidad</label>
                            <x-ui.textarea id="order-status-note" name="note" rows="3" placeholder="Obligatoria para vendido y despachado...">{{ old('note') }}</x-ui.textarea>
                            <x-input-error :messages="$errors->get('note')" />
                            <p class="mt-1 text-xs text-slate-500">Requerida al marcar como vendido o despachado.</p>
                        </div>
                        <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">Actualizar estado</x-ui.button>
                    </form>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('admin.orders.pdf', $order) }}" class="btn btn-primary w-full justify-center sm:w-auto">Descargar PDF</a>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Volver</a>
                    @if($order->contact_email)
                        <a href="mailto:{{ $order->contact_email }}" class="btn btn-secondary w-full justify-center sm:w-auto">Enviar correo</a>
                    @endif
                    <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" data-confirm="¿Eliminar {{ $order->oc_number }}? Esta acción no se puede deshacer." class="w-full sm:w-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-full justify-center sm:w-auto">Eliminar</button>
                    </form>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Checklist Operativo</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <span>Correo de contacto</span>
                        <x-ui.badge :variant="$order->contact_email ? 'success' : 'warning'">{{ $order->contact_email ? 'OK' : 'Falta' }}</x-ui.badge>
                    </li>
                    <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <span>NIT / Identificación</span>
                        <x-ui.badge :variant="$order->company_nit ? 'success' : 'warning'">{{ $order->company_nit ? 'OK' : 'Falta' }}</x-ui.badge>
                    </li>
                    <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <span>Dirección y ciudad</span>
                        <x-ui.badge :variant="($order->company_address && $order->city) ? 'success' : 'warning'">{{ ($order->company_address && $order->city) ? 'OK' : 'Incompleto' }}</x-ui.badge>
                    </li>
                    <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <span>Documento PDF</span>
                        <x-ui.badge :variant="$order->pdf_path ? 'success' : 'warning'">{{ $order->pdf_path ? 'Listo' : 'Pendiente' }}</x-ui.badge>
                    </li>
                </ul>
            </x-ui.card>
        </div>
    </section>
</x-app-layout>
