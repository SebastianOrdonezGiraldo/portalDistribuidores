<x-app-layout>
    @php
        $itemsCount = $items->count();
        $unitsCount = (int) $items->sum(fn ($item) => (int) $item['qty']);
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Tu carrito" subtitle="Revisa cantidades y confirma el pedido antes de continuar a checkout.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Productos: {{ number_format($itemsCount) }}</span>
                    <span class="stat-pill">Unidades: {{ number_format($unitsCount) }}</span>
                    <span class="stat-pill">Total: ${{ number_format((float) $total, 0, ',', '.') }}</span>
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
        <form id="cart-update-form" action="{{ route('cart.update') }}" method="POST" data-loading-form data-cart-form class="grid gap-4 lg:grid-cols-[1.8fr_1fr]">
            @csrf
            @method('PATCH')

            <x-ui.card class="p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                    <h2 class="card-title">Productos agregados</h2>
                    <p class="text-xs text-slate-500">Tip: usa cantidad 0 para quitar un producto.</p>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach($items as $item)
                        @php
                            $product = $item['product'];
                            $lineKey = (string) $item['line_key'];
                            $inputId = 'qty-'.preg_replace('/[^A-Za-z0-9\-_]/', '-', $lineKey);
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-ui.product-thumb :product="$product" size="md" />

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-500">SKU: {{ $product->sku }}</p>
                                        @if($item['variant_label'])
                                            <p class="text-xs text-slate-500">{{ $item['variant_label'] }}</p>
                                        @endif
                                        <p class="mt-1 text-sm font-semibold text-slate-900">
                                            ${{ number_format((float) $item['unit_price'], 0, ',', '.') }}
                                            <span class="font-normal text-slate-500">/ {{ $item['unit_label'] }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="inline-flex items-center rounded-xl border border-slate-300 bg-white p-1">
                                        <button type="button" data-cart-step="-1" data-cart-target="{{ $inputId }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-100 focus-ring" aria-label="Disminuir cantidad">-</button>
                                        <input
                                            id="{{ $inputId }}"
                                            type="number"
                                            name="quantities[{{ $lineKey }}]"
                                            min="0"
                                            step="1"
                                            value="{{ (int) $item['qty'] }}"
                                            class="w-16 border-0 bg-transparent text-center text-sm font-semibold text-slate-900 focus:ring-0"
                                            data-cart-qty
                                        >
                                        <button type="button" data-cart-step="1" data-cart-target="{{ $inputId }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-100 focus-ring" aria-label="Aumentar cantidad">+</button>
                                    </div>

                                    <button type="button" data-cart-remove="{{ $inputId }}" class="btn btn-ghost !px-2 text-xs text-red-700 hover:bg-red-50">
                                        Quitar
                                    </button>
                                </div>
                            </div>

                            <div class="mt-2 text-right">
                                <span class="text-xs text-slate-500">Subtotal</span>
                                <p class="text-sm font-semibold text-slate-900">${{ number_format((float) $item['subtotal'], 0, ',', '.') }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </x-ui.card>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:h-fit">
                <x-ui.card class="p-5">
                    <h2 class="card-title">Resumen comercial</h2>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Productos distintos</span>
                            <span class="font-medium text-slate-900">{{ number_format($itemsCount) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Unidades totales</span>
                            <span class="font-medium text-slate-900">{{ number_format($unitsCount) }}</span>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total estimado</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">${{ number_format((float) $total, 0, ',', '.') }}</p>
                    </div>

                    <div class="mt-4 space-y-2">
                        <x-ui.button type="submit" variant="secondary" class="w-full justify-center" data-loading-label="Actualizando...">Actualizar carrito</x-ui.button>
                        <a href="{{ route('checkout.show') }}" class="btn btn-primary w-full justify-center">Continuar al checkout</a>
                    </div>
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

            document.querySelectorAll('[data-cart-step]').forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.cartTarget;
                    const input = targetId ? document.getElementById(targetId) : null;

                    if (!input) {
                        return;
                    }

                    const direction = Number(button.dataset.cartStep || 0);
                    const current = parseQty(input.value);
                    input.value = String(Math.max(0, current + direction));
                });
            });

            document.querySelectorAll('[data-cart-remove]').forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.cartRemove;
                    const input = targetId ? document.getElementById(targetId) : null;

                    if (!input) {
                        return;
                    }

                    input.value = '0';
                    cartForm.requestSubmit();
                });
            });
        });
    </script>
</x-app-layout>
