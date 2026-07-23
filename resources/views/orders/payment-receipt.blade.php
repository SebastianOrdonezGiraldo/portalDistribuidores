{{--
View contract:
- Source: PaymentReceiptController::show
- Expects: $order, $state (ready|token_expired|token_consumed_or_closed), $plainToken, $canRegenerate, $maxSizeLabel
--}}
<x-guest-layout>
    <div class="mx-auto max-w-lg px-4 py-10">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Comprobante de pago</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-900">Pedido {{ $order->oc_number }}</h1>
            <p class="mt-1 text-sm text-slate-600">Estado: {{ $order->payment_status->label() }}</p>

            @if (session('status'))
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($state === 'token_expired')
                <div class="mt-5 space-y-3">
                    <p class="text-sm text-slate-700">El enlace expiró. Genera uno nuevo desde el detalle del pedido (en el equipo donde iniciaste el checkout) o inicia sesión.</p>
                    @if ($canRegenerate)
                        <form method="POST" action="{{ route('orders.payment-receipt.regenerate', $order) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-full justify-center">Generar nuevo enlace</button>
                        </form>
                    @endif
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary w-full justify-center">Ir al detalle del pedido</a>
                </div>
            @elseif ($state === 'token_consumed_or_closed')
                <div class="mt-5 space-y-3">
                    <p class="text-sm text-slate-700">
                        @if($order->payment_status === \App\Modules\Shared\Enums\PaymentStatus::Confirming)
                            Ya recibimos un comprobante. Estamos confirmando el pago.
                        @elseif($order->payment_status === \App\Modules\Shared\Enums\PaymentStatus::Validated)
                            El pago de este pedido ya fue validado.
                        @else
                            Este enlace ya no admite subidas. Estado actual: {{ $order->payment_status->label() }}.
                        @endif
                    </p>
                </div>
            @else
                <form
                    method="POST"
                    action="{{ route('orders.payment-receipt.store', $order) }}"
                    enctype="multipart/form-data"
                    class="mt-5 space-y-4"
                    data-loading-form
                >
                    @csrf
                    <input type="hidden" name="token" value="{{ $plainToken }}">
                    <div>
                        <label class="form-label" for="receipt">Comprobante (imagen o PDF, máx. {{ $maxSizeLabel }})</label>
                        <input
                            id="receipt"
                            name="receipt"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
                            required
                            class="form-input"
                        >
                    </div>
                    <button type="submit" class="btn btn-primary w-full justify-center" data-loading-label="Subiendo...">
                        Subir comprobante
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-guest-layout>
