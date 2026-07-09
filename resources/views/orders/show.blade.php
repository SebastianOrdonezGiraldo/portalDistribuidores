{{--
View contract:
- Source: App\Modules\Orders\Http\Controllers\OrderController::show.
- Expects: $order with items, distributor, user, and statusHistory.actor loaded.
- Owns: customer-facing order detail, timeline display, PDF download link, and advisor contact CTA.
- Notes: access checks and private PDF streaming stay in OrderController/OrderPdfGenerator.
--}}
<x-app-layout>
    {{-- Timeline entries are presentation summaries of status history; status transitions are not decided here. --}}
    @php
        $timeline = $order->statusHistory
            ->map(function ($event) {
                $status = \App\Modules\Shared\Enums\OrderStatus::tryFrom((string) $event->to_status);

                return [
                    'title' => 'Estado: '.($status?->label() ?? strtoupper((string) $event->to_status)),
                    'description' => $event->note ?: 'Cambio de estado registrado.',
                    'status' => $event->to_status,
                    'at' => $event->created_at,
                ];
            })
            ->values();

        if ($timeline->isEmpty()) {
            $timeline = collect([
                [
                    'title' => 'Pedido recibido',
                    'description' => 'Tu CTC fue registrada correctamente en el sistema.',
                    'status' => $order->status,
                    'at' => $order->created_at,
                ],
            ]);
        }

        if ($order->pdf_path) {
            $timeline->push([
                'title' => 'Documento disponible',
                'description' => 'El PDF del pedido ya puede descargarse.',
                'status' => 'info',
                'at' => $order->updated_at,
            ]);
        }

        $timeline = $timeline->sortByDesc('at')->values();
        $quotationReference = (string) ($order->oc_number ?: $order->id);
        $advisorWhatsappMessage = sprintf(
            'Hola, quiero hablar con un asesor sobre la cotización ##%s',
            $quotationReference
        );
        $advisorWhatsappUrl = 'https://wa.me/573117479607?text='.rawurlencode($advisorWhatsappMessage);
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Seguimiento de Pedido {{ $order->oc_number }}" subtitle="Consulta estado, detalle de ítems y documentación de forma centralizada.">
            <x-slot name="actions">
                <a href="{{ route('catalog.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Volver al catálogo</a>
                @if($order->pdf_path)
                    <a href="{{ route('orders.pdf', $order) }}" class="btn btn-primary w-full justify-center sm:w-auto">Descargar PDF</a>
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
                        <dd class="mt-1 font-semibold text-slate-900">
                            {{ $order->company_address ?? '-' }} · {{ $order->city ?? '-' }}
                            @if($order->department) · {{ $order->department }} @endif
                        </dd>
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
                                    <td data-label="SKU" class="font-medium text-slate-900">{{ $item->sku_snapshot }}</td>
                                    <td data-label="Producto" data-full="true">
                                        <p>{{ $item->product_name_snapshot }}</p>
                                        @if($item->variant_value_snapshot)
                                            <p class="text-xs text-slate-500">
                                                {{ $item->variant_attribute_snapshot ?? 'Variante' }}: {{ $item->variant_value_snapshot }}
                                            </p>
                                        @endif
                                    </td>
                                    <td data-label="Cantidad">{{ (int) $item->qty }} {{ $item->unit_label }}</td>
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
                            <p class="mt-1 text-xs text-slate-500">{{ $event['at']?->format('d/m/Y H:i') }}</p>
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

                    <a href="{{ $advisorWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn-whatsapp-advisor">
                        <span class="relative z-10 inline-flex items-center gap-2">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12a9.75 9.75 0 0014.59 8.47l4.66 1.24-1.24-4.66A9.75 9.75 0 102.25 12z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75h.008v.008H8.25V9.75zm3.75 0h.008v.008H12V9.75zm3.75 0h.008v.008h-.008V9.75z" />
                            </svg>
                            Hablar con un asesor
                        </span>
                    </a>
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

                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('download_pdf');
                cleanUrl.searchParams.delete('open_whatsapp');
                window.history.replaceState({}, '', cleanUrl.toString());
            });
        </script>
    @endif
</x-app-layout>
