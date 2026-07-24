{{--
View contract:
- Source: PaymentReceiptController::show
- Expects: $order, $state (ready|token_expired|token_consumed_or_closed), $plainToken, $canRegenerate, $maxSizeLabel
--}}
@php
    $statusBadgeClass = $order->payment_status->badgeClass();
@endphp
<x-guest-layout :compact="true">
    <div class="space-y-5">
        <div>
            <p class="text-[0.7rem] font-bold uppercase tracking-[0.14em] text-brand-primary">Comprobante de pago</p>
            <h1 class="mt-1 text-balance text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                Pedido {{ $order->oc_number }}
            </h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="text-sm text-slate-600">Estado</span>
                <span class="badge {{ $statusBadgeClass }}">{{ $order->payment_status->label() }}</span>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($state === 'token_expired')
            <div class="space-y-3">
                <p class="text-sm leading-relaxed text-slate-700">
                    El enlace expiró. Genera uno nuevo desde el detalle del pedido (en el equipo donde iniciaste el checkout) o inicia sesión.
                </p>
                @if ($canRegenerate)
                    <form method="POST" action="{{ route('orders.payment-receipt.regenerate', $order) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full justify-center !min-h-11">Generar nuevo enlace</button>
                    </form>
                @endif
                <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary w-full justify-center !min-h-11">
                    Ir al detalle del pedido
                </a>
            </div>
        @elseif ($state === 'token_consumed_or_closed')
            <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-sm leading-relaxed text-slate-700">
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
                class="space-y-4"
                data-loading-form
            >
                @csrf
                <input type="hidden" name="token" value="{{ $plainToken }}">
                <div>
                    <label class="form-label" for="receipt">
                        Adjunta tu comprobante
                    </label>
                    <p class="mb-2 text-xs text-slate-500">Imagen o PDF · máximo {{ $maxSizeLabel }}</p>
                    <input
                        id="receipt"
                        name="receipt"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
                        required
                        class="form-input block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-primary/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-dark"
                    >
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center !min-h-11" data-loading-label="Subiendo...">
                    Subir comprobante
                </button>
            </form>
        @endif
    </div>
</x-guest-layout>
