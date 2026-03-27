<x-app-layout>
    @php
        $whatsappNumber = '573117479607';
        $advisorMessage = 'Hola, quiero hablar con un asesor sobre la cotización '.$order->oc_number.'.';
        $whatsappUrl = 'https://wa.me/'.$whatsappNumber.'?text='.rawurlencode($advisorMessage);
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Solicitud enviada" subtitle="Tu solicitud de cotización fue recibida y está en gestión.">
            <x-slot name="actions">
                <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">Ver detalles del pedido</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="mx-auto max-w-3xl">
        <x-ui.card class="overflow-hidden">
            <div class="border-b border-emerald-200 bg-emerald-50 px-6 py-6">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Solicitud recibida</p>
                <h1 class="mt-2 text-2xl font-semibold text-emerald-900">¡Solicitud enviada con éxito!</h1>
                <p class="mt-2 text-sm text-emerald-800">
                    Su solicitud de cotización ha sido recibida. Estamos generando el PDF y te llegará también por correo.
                </p>
            </div>

            <div class="space-y-4 px-6 py-6">
                <x-ui.alert variant="warning" title="Importante">
                    La cotización generada tendrá una vigencia de 1 día a partir de la fecha de emisión.
                </x-ui.alert>

                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    Numero de solicitud: <span class="font-semibold text-slate-900">{{ $order->oc_number }}</span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary w-full justify-center">Ver detalles del pedido</a>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary w-full justify-center shadow-soft ring-2 ring-brand-primary/20">
                        Hablar con un asesor
                    </a>
                </div>

                <p class="text-xs text-slate-500">
                    Si la descarga del PDF no inicia automáticamente,
                    <a href="{{ route('orders.pdf', $order) }}" class="font-semibold text-brand-dark hover:underline">descárgalo aquí</a>.
                </p>
            </div>
        </x-ui.card>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const storageKey = 'order_pdf_autodownloaded_{{ $order->id }}';

            try {
                if (window.sessionStorage.getItem(storageKey)) {
                    return;
                }

                window.sessionStorage.setItem(storageKey, '1');
            } catch (error) {
                // Continue with download even if sessionStorage is unavailable.
            }

            const downloadFrame = document.createElement('iframe');
            downloadFrame.style.display = 'none';
            downloadFrame.src = @json(route('orders.pdf', $order));
            document.body.appendChild(downloadFrame);
        });
    </script>
</x-app-layout>
