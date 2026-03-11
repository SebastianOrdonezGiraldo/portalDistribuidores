<x-app-layout>
    @php
        $timeline = collect([
            [
                'title' => 'Pedido recibido',
                'description' => 'Tu CTC fue registrada correctamente en el sistema.',
                'status' => 'approved',
                'at' => $order->created_at,
            ],
            [
                'title' => 'Estado actual: '.strtoupper($order->status->value),
                'description' => 'Seguimiento de validación y despacho.',
                'status' => $order->status,
                'at' => $order->updated_at,
            ],
            $order->pdf_path ? [
                'title' => 'Documento disponible',
                'description' => 'El PDF del pedido ya puede descargarse.',
                'status' => 'sent',
                'at' => $order->updated_at,
            ] : null,
        ])->filter()->sortByDesc('at')->values();
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Seguimiento de Pedido {{ $order->oc_number }}" subtitle="Consulta estado, detalle de ítems y documentación de forma centralizada.">
            <x-slot name="actions">
                <a href="{{ route('catalog.index') }}" class="btn btn-secondary">Volver al catálogo</a>
                @if($order->pdf_path)
                    <a href="{{ route('orders.pdf', $order) }}" class="btn btn-primary">Descargar PDF</a>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 xl:grid-cols-[1.7fr_1fr]">
        <div class="space-y-4">
            <x-ui.card class="p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="card-title">Resumen del Pedido</h2>
                    <x-ui.status-badge :status="$order->status" />
                </div>

                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Razón social</dt>
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
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Dirección</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $order->company_address ?? '-' }} · {{ $order->city ?? '-' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <x-slot name="header">
                    <h2 class="card-title">Ítems del pedido</h2>
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
                                        <p>{{ $item->product_name_snapshot }}</p>
                                        @if($item->variant_value_snapshot)
                                            <p class="text-xs text-slate-500">
                                                {{ $item->variant_attribute_snapshot ?? 'Variante' }}: {{ $item->variant_value_snapshot }}
                                            </p>
                                        @endif
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
                        <x-ui.alert variant="success" title="PDF generado">
                            Documento disponible para consulta y descarga.
                        </x-ui.alert>
                        <a href="{{ route('orders.pdf', $order) }}" class="btn btn-primary w-full justify-center">Descargar PDF</a>
                    @else
                        <x-ui.alert variant="warning" title="PDF en proceso">
                            El documento aún no está disponible. Intenta más tarde.
                        </x-ui.alert>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </section>

    @if(request()->boolean('download_pdf') || request()->boolean('open_whatsapp'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (@json(request()->boolean('download_pdf'))) {
                    const downloadFrame = document.createElement('iframe');
                    downloadFrame.style.display = 'none';
                    downloadFrame.src = @json(route('orders.pdf', $order));
                    document.body.appendChild(downloadFrame);
                }

                if (@json(request()->boolean('open_whatsapp'))) {
                    const whatsappNumber = '573117479607';
                    const message = @json('Hola, acabo de crear la una orden '.$order->oc_number.'.');
                    const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;
                    const popup = window.open(whatsappUrl, '_blank', 'noopener');

                    if (!popup) {
                        window.location.href = whatsappUrl;
                    }
                }

                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('download_pdf');
                cleanUrl.searchParams.delete('open_whatsapp');
                window.history.replaceState({}, '', cleanUrl.toString());
            });
        </script>
    @endif
</x-app-layout>
