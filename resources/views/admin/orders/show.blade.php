<x-app-layout>
    @php
        $timeline = collect([
            [
                'title' => 'Pedido creado',
                'description' => 'Registro inicial de la CTC en el portal.',
                'status' => 'approved',
                'at' => $order->created_at,
            ],
            [
                'title' => 'Estado operativo: '.strtoupper($order->status->value),
                'description' => 'Seguimiento del proceso logístico/comercial.',
                'status' => $order->status,
                'at' => $order->updated_at,
            ],
            $order->pdf_path ? [
                'title' => 'PDF disponible',
                'description' => 'Documento generado y listo para descarga.',
                'status' => 'sent',
                'at' => $order->updated_at,
            ] : [
                'title' => 'PDF pendiente',
                'description' => 'Aún no se encuentra un documento generado.',
                'status' => 'pending',
                'at' => $order->updated_at,
            ],
        ])->filter()->sortByDesc('at')->values();
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Pedido {{ $order->oc_number }}" subtitle="Detalle integral para validación comercial, documentación y trazabilidad operativa.">
            <x-slot name="actions">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Volver al listado</a>
                <a href="{{ route('admin.orders.pdf', $order) }}" class="btn btn-primary">Descargar PDF</a>
                <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" data-confirm="¿Eliminar {{ $order->oc_number }}? Esta acción no se puede deshacer." class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar pedido</button>
                </form>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 xl:grid-cols-[1.7fr_1fr]">
        <div class="space-y-4">
            <x-ui.card class="p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="card-title">Resumen General</h2>
                        <p class="mt-1 text-sm text-slate-600">Cliente, contacto y estado del pedido.</p>
                    </div>
                    <x-ui.status-badge :status="$order->status" class="shrink-0" />
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
            </x-ui.card>

            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <h2 class="card-title">Ítems del Pedido</h2>
                        <p class="mt-1 text-xs text-slate-500">Detalle de cantidades, precio unitario y subtotal.</p>
                    </div>
                </x-slot>

                <div class="p-5 pt-0">
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $item->sku_snapshot }}</td>
                                    <td>
                                        <p class="font-medium text-slate-900">{{ $item->product_name_snapshot }}</p>
                                    </td>
                                    <td>{{ (int) $item->qty }} {{ $item->unit_label }}</td>
                                    <td>${{ number_format((float) $item->price_each, 0, ',', '.') }}</td>
                                    <td class="font-semibold text-slate-900">${{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>

                    <div class="mt-4 flex justify-end border-t border-slate-200 pt-3">
                        <p class="text-lg font-semibold text-slate-900">Total: ${{ number_format((float) $order->total_amount, 0, ',', '.') }}</p>
                    </div>
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
                <h2 class="card-title">Timeline</h2>
                <ol class="mt-4 space-y-3">
                    @foreach($timeline as $event)
                        <li class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-slate-900">{{ $event['title'] }}</p>
                                <x-ui.status-badge :status="$event['status']" class="shrink-0" />
                            </div>
                            <p class="mt-1 text-xs text-slate-600">{{ $event['description'] }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $event['at']?->format('d/m/Y H:i') }}</p>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Documentos</h2>
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

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('admin.orders.pdf', $order) }}" class="btn btn-primary">Descargar PDF</a>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Volver</a>
                    <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" data-confirm="¿Eliminar {{ $order->oc_number }}? Esta acción no se puede deshacer." class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </x-ui.card>
        </div>
    </section>
</x-app-layout>
