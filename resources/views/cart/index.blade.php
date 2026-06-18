<x-app-layout>
    @php
        $itemsCount = $items->count();
        $unitsCount = (int) $items->sum(fn ($item) => (int) $item['qty']);
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Tu carrito" subtitle="Revisa cantidades y confirma el pedido antes de continuar a checkout.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Productos: <span data-cart-products-count>{{ number_format($itemsCount) }}</span></span>
                    <span class="stat-pill">Unidades: <span data-cart-units-count>{{ number_format($unitsCount) }}</span></span>
                    <span class="stat-pill">Total: <span data-cart-total-amount>${{ number_format((float) $total, 0, ',', '.') }}</span></span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('catalog.index') }}" class="btn btn-secondary">Seguir comprando</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if($items->isEmpty())
        <x-ui.empty-state title="Tu carrito está vacío" description="Agrega productos desde el catálogo para iniciar tu pedido comercial.">
            <x-slot name="action">
                <a href="{{ route('catalog.index') }}" class="btn btn-primary">Ir al catálogo</a>
            </x-slot>
        </x-ui.empty-state>
    @else
        <form id="cart-update-form" action="{{ route('cart.update') }}" method="POST" data-loading-form data-cart-form class="grid min-w-0 gap-4 lg:grid-cols-[1.8fr_1fr]">
            @csrf
            @method('PATCH')

            <x-ui.card class="min-w-0 p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                    <h2 class="card-title">Productos agregados</h2>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach($items as $item)
                        @php
                            $product = $item['product'];
                            $lineKey = (string) $item['line_key'];
                            $inputId = 'qty-'.preg_replace('/[^A-Za-z0-9\-_]/', '-', $lineKey);
                        @endphp
                        <article
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4"
                            data-cart-item
                            data-unit-price="{{ (float) $item['unit_price'] }}"
                            data-stock-limit="{{ $item['available_qty'] ?? '' }}"
                        >
                            <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-ui.product-thumb :product="$product" size="md" />

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $product->name }}</p>
                                        @if(isset($item['available_qty']) && $item['available_qty'] !== null)
                                            <p class="text-xs text-slate-500">Stock disponible: {{ number_format((int) $item['available_qty']) }}</p>
                                        @endif
                                        <p class="text-xs text-slate-500">SKU: {{ $product->sku }}</p>
                                        @if($item['variant_label'])
                                            <p class="text-xs text-slate-500">{{ $item['variant_label'] }}</p>
                                        @endif
                                        <p class="mt-1 text-sm font-semibold text-slate-900">
                                            ${{ number_format((float) $item['unit_price'], 0, ',', '.') }}
                                            <span class="font-normal text-slate-500">/ {{ $item['unit_label'] }} · {{ $item['vat_label'] }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center">
                                    <div class="inline-flex w-full max-w-[11rem] items-center rounded-xl border border-slate-300 bg-white p-1 sm:w-auto sm:max-w-none">
                                        <button type="button" data-cart-step="-1" data-cart-target="{{ $inputId }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-100 focus-ring" aria-label="Disminuir cantidad">-</button>
                                        <input
                                            id="{{ $inputId }}"
                                            type="number"
                                            name="quantities[{{ $lineKey }}]"
                                            min="0"
                                            step="1"
                                            value="{{ (int) $item['qty'] }}"
                                            @if(isset($item['available_qty']) && $item['available_qty'] !== null) max="{{ (int) $item['available_qty'] }}" @endif
                                            class="no-number-spinner w-14 border-0 bg-transparent text-center text-sm font-semibold text-slate-900 focus:ring-0"
                                            data-cart-qty
                                        >
                                        <button type="button" data-cart-step="1" data-cart-target="{{ $inputId }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-100 focus-ring" aria-label="Aumentar cantidad">+</button>
                                    </div>

                                    <button type="button" data-cart-remove="{{ $inputId }}" class="btn btn-ghost w-full justify-center !px-3 text-xs text-red-700 hover:bg-red-50 sm:w-auto">
                                        Quitar
                                    </button>
                                </div>
                            </div>

                            <div class="mt-2 text-right">
                                <span class="text-xs text-slate-500">Subtotal</span>
                                <p class="text-sm font-semibold text-slate-900" data-cart-subtotal-amount>${{ number_format((float) $item['subtotal'], 0, ',', '.') }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </x-ui.card>

            <aside class="min-w-0 max-w-full space-y-4 lg:sticky lg:top-24 lg:h-fit">
                <x-ui.card class="min-w-0 p-5">
                    <h2 class="card-title">Resumen comercial</h2>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="min-w-0 text-slate-500">Productos distintos</span>
                            <span class="shrink-0 text-right font-medium text-slate-900" data-cart-products-count>{{ number_format($itemsCount) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="min-w-0 text-slate-500">Unidades totales</span>
                            <span class="shrink-0 text-right font-medium text-slate-900" data-cart-units-count>{{ number_format($unitsCount) }}</span>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total estimado</p>
                        <p class="mt-1 break-words text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl" data-cart-total-amount>${{ number_format((float) $total, 0, ',', '.') }}</p>
                    </div>

                    <div class="mt-4 space-y-2">
                        <a href="{{ route('catalog.index') }}" class="btn btn-ghost w-full justify-center">Seguir comprando</a>
                        <x-ui.button type="submit" variant="secondary" class="w-full justify-center" data-loading-label="Actualizando...">Actualizar carrito</x-ui.button>
                        <x-ui.button type="submit" variant="primary" class="w-full justify-center" data-loading-label="Procesando..." data-checkout-submit>Continuar al checkout</x-ui.button>
                    </div>
                    <input type="hidden" name="redirect_checkout" value="0" data-redirect-checkout-input>
                </x-ui.card>
            </aside>
        </form>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cartForm = document.querySelector('[data-cart-form]');

            if (!cartForm) {
                return;
            }

            const parseQty = (value) => {
                const parsed = Number(value);
                if (!Number.isFinite(parsed)) {
                    return 0;
                }

                return Math.max(0, Math.round(parsed));
            };

            const parseStockLimit = (value) => {
                if (value === '' || value === null || value === undefined) {
                    return null;
                }

                const parsed = Number(value);
                if (!Number.isFinite(parsed)) {
                    return null;
                }

                return Math.max(0, Math.floor(parsed));
            };

            const numberFormatter = new Intl.NumberFormat('es-CO', {
                maximumFractionDigits: 0,
            });

            const formatNumber = (value) => numberFormatter.format(Math.max(0, Math.round(value)));
            const formatMoney = (value) => `$${formatNumber(value)}`;

            const refreshCartSummary = () => {
                let total = 0;
                let units = 0;
                let products = 0;

                cartForm.querySelectorAll('[data-cart-item]').forEach((item) => {
                    const qtyInput = item.querySelector('[data-cart-qty]');
                    if (!qtyInput) {
                        return;
                    }

                    const qty = parseQty(qtyInput.value);
                    const stockLimit = parseStockLimit(item.dataset.stockLimit);
                    const normalizedQty = stockLimit === null ? qty : Math.min(qty, stockLimit);
                    qtyInput.value = String(normalizedQty);

                    const unitPrice = Number(item.dataset.unitPrice || 0);
                    const subtotal = normalizedQty * unitPrice;

                    total += subtotal;
                    units += normalizedQty;
                    if (normalizedQty > 0) {
                        products += 1;
                    }

                    const subtotalOutput = item.querySelector('[data-cart-subtotal-amount]');
                    if (subtotalOutput) {
                        subtotalOutput.textContent = formatMoney(subtotal);
                    }
                });

                document.querySelectorAll('[data-cart-total-amount]').forEach((element) => {
                    element.textContent = formatMoney(total);
                });

                document.querySelectorAll('[data-cart-units-count]').forEach((element) => {
                    element.textContent = formatNumber(units);
                });

                document.querySelectorAll('[data-cart-products-count]').forEach((element) => {
                    element.textContent = formatNumber(products);
                });
            };

            document.querySelectorAll('[data-cart-step]').forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.cartTarget;
                    const input = targetId ? document.getElementById(targetId) : null;

                    if (!input) {
                        return;
                    }

                    const direction = Number(button.dataset.cartStep || 0);
                    const current = parseQty(input.value);
                    const item = input.closest('[data-cart-item]');
                    const stockLimit = parseStockLimit(item?.dataset.stockLimit);
                    const nextValue = Math.max(0, current + direction);
                    input.value = String(stockLimit === null ? nextValue : Math.min(nextValue, stockLimit));
                    refreshCartSummary();
                });
            });

            cartForm.querySelectorAll('[data-cart-qty]').forEach((input) => {
                input.addEventListener('input', refreshCartSummary);
                input.addEventListener('change', refreshCartSummary);
            });

            const checkoutSubmitButton = cartForm.querySelector('[data-checkout-submit]');
            const updateCartButton = cartForm.querySelector('[data-loading-label="Actualizando..."]');
            const redirectCheckoutInput = cartForm.querySelector('[data-redirect-checkout-input]');

            checkoutSubmitButton?.addEventListener('click', () => {
                if (redirectCheckoutInput) {
                    redirectCheckoutInput.value = '1';
                }
            });

            updateCartButton?.addEventListener('click', () => {
                if (redirectCheckoutInput) {
                    redirectCheckoutInput.value = '0';
                }
            });

            document.querySelectorAll('[data-cart-remove]').forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.cartRemove;
                    const input = targetId ? document.getElementById(targetId) : null;

                    if (!input) {
                        return;
                    }

                    input.value = '0';
                    refreshCartSummary();
                    cartForm.requestSubmit();
                });
            });

            refreshCartSummary();
        });
    </script>
</x-app-layout>
