{{--
Partial: payment upload + magic link QR for order detail.
Expects: $order, optional $paymentUploadUrl, $paymentUploadToken, $receiptMaxSizeLabel
--}}
@php
    $paymentUploadUrl = $paymentUploadUrl ?? null;
    $paymentUploadToken = $paymentUploadToken ?? session('payment_upload_token');
    $receiptMaxSizeLabel = $receiptMaxSizeLabel ?? \App\Modules\Catalog\Support\ProductUploadLimits::photoMaxSizeLabel();
    $allowsUpload = $order->payment_status->allowsReceiptUpload();
    $methodLines = $order->payment_method?->instructionLines() ?? [];
    $methodImageUrl = $order->payment_method?->instructionImageUrl();
@endphp

@if($order->requiresManualPayment())
    <x-ui.card
        class="relative isolate overflow-hidden p-5"
        data-payment-panel
        data-payment-status-url="{{ route('orders.payment-status', $order) }}"
        data-payment-status="{{ $order->payment_status->value }}"
        data-payment-expires-at="{{ $order->payment_reservation_expires_at?->toIso8601String() }}"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="card-title">Pago del pedido</h2>
            <span
                class="badge {{ $order->payment_status->badgeClass() }}"
                data-payment-status-badge
            >
                {{ $order->payment_status->label() }}
            </span>
        </div>

        @if($order->payment_method)
            <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm">
                <p class="font-semibold text-slate-900">Método: {{ $order->payment_method->label() }}</p>
                <ul class="mt-1 list-disc space-y-0.5 pl-4 text-xs text-slate-600">
                    @foreach($methodLines as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
                @if($methodImageUrl)
                    <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-[#2a0a3d]">
                        <img
                            src="{{ $methodImageUrl }}"
                            alt="QR de pago {{ $order->payment_method->label() }}"
                            class="mx-auto block h-auto w-full max-w-sm object-contain"
                        >
                    </div>
                @endif
            </div>
        @endif

        <p class="mt-3 text-sm text-slate-600" data-payment-status-message>
            @if($order->payment_status === \App\Modules\Shared\Enums\PaymentStatus::PendingUpload)
                Realiza la transferencia y sube el comprobante. La reserva vence
                @if($order->payment_reservation_expires_at)
                    el {{ $order->payment_reservation_expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.
                @else
                    pronto.
                @endif
            @elseif($order->payment_status === \App\Modules\Shared\Enums\PaymentStatus::Rejected)
                Tu comprobante fue rechazado. Sube uno nuevo antes de que venza la reserva.
            @elseif($order->payment_status === \App\Modules\Shared\Enums\PaymentStatus::Confirming)
                Comprobante recibido. Estamos confirmando tu pago.
            @elseif($order->payment_status === \App\Modules\Shared\Enums\PaymentStatus::Validated)
                Pago validado. Continuamos con la gestión de tu pedido.
            @elseif($order->payment_status === \App\Modules\Shared\Enums\PaymentStatus::Expired)
                El plazo de pago venció y la reserva se liberó. Puedes armar un nuevo pedido desde el catálogo.
            @endif
        </p>

        @if($allowsUpload)
            <div data-payment-upload-zone>
                <form
                    action="{{ route('orders.payment-receipt.upload', $order) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="mt-4 space-y-3"
                    data-loading-form
                >
                    @csrf
                    <div>
                        <label class="form-label" for="receipt">Comprobante (imagen o PDF, máx. {{ $receiptMaxSizeLabel }})</label>
                        <input
                            id="receipt"
                            name="receipt"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
                            required
                            class="form-input"
                        >
                        <x-input-error :messages="$errors->get('receipt')" />
                    </div>
                    <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto" data-loading-label="Subiendo...">
                        Subir comprobante
                    </x-ui.button>
                </form>

                <div class="mt-5 border-t border-slate-200 pt-4">
                    <p class="text-sm font-semibold text-slate-900">¿Prefieres subir el comprobante desde tu celular?</p>
                    <p class="mt-1 text-xs text-slate-500">Escanea este código o abre el enlace en tu teléfono.</p>

                    @if($paymentUploadUrl)
                        <div class="mt-3 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                            <canvas
                                data-qr-url="{{ $paymentUploadUrl }}"
                                width="160"
                                height="160"
                                class="mx-auto rounded-xl border border-slate-200 bg-white p-2 sm:mx-0"
                                aria-label="Código QR para subir comprobante"
                            ></canvas>
                            <div class="min-w-0 space-y-2">
                                <a href="{{ $paymentUploadUrl }}" class="block break-all text-xs font-medium text-brand-primary hover:underline">{{ $paymentUploadUrl }}</a>
                                <form action="{{ route('orders.payment-upload-link', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary w-full justify-center text-xs sm:w-auto">Generar nuevo enlace</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <form action="{{ route('orders.payment-upload-link', $order) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full justify-center sm:w-auto">Generar enlace para celular</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </x-ui.card>
@endif
