{{--
View contract:
- Source: App\Modules\Orders\Http\Controllers\CartController::index.
- Expects: $items collection from CartService::items() and $total from CartService::total().
- Owns: cart review, quantity update form, and checkout navigation.
- Notes: line validation, stock checks, and cart mutations stay in CartController/CartService.
--}}
<x-app-layout>
    @php
        // Presentation-only summaries; the authoritative pricing lives in CartService.
        $itemsCount = $items->count();
        $unitsCount = (int) $items->sum(fn ($item) => (int) $item['qty']);
        $money = fn ($value) => '$'.number_format((float) $value, 0, ',', '.');

        $tier = $items->isNotEmpty() ? $items->first()['tier'] : \App\Modules\Shared\Enums\DistributorTier::Silver;
        $isGold = $tier === \App\Modules\Shared\Enums\DistributorTier::Gold;
        $tierName = $isGold ? 'ORO' : 'Plata';
        $vatRate = \App\Modules\Orders\Support\OrderLineVat::DEFAULT_RATE;

        $grossTotal = (float) $total;
        $listTotal = (float) $items->sum(fn ($item) => (float) $item['silver_unit_price'] * (int) $item['qty']);
        $savingsTotal = (float) $items->sum('line_savings');
        $netTotal = (float) $items->sum(fn ($item) => $item['is_vat_excluded']
            ? (float) $item['subtotal']
            : (float) $item['subtotal'] / (1 + $vatRate));
        $ivaTotal = max(0, $grossTotal - $netTotal);
        $savingsPct = $listTotal > 0 ? (int) round($savingsTotal / $listTotal * 100) : 0;
        $hasSavings = $savingsTotal > 0.5;

        $goldTotal = (float) $items->sum(fn ($item) => (float) $item['base_unit_price'] * (int) $item['qty']);
        $potentialSavings = max(0, $grossTotal - $goldTotal);
        $potentialSavingsPct = $grossTotal > 0 ? (int) round($potentialSavings / $grossTotal * 100) : 0;
        $hasPotentialSavings = $potentialSavings > 0.5;

        $silverTier = \App\Modules\Shared\Enums\DistributorTier::Silver;
        $upgradeMessage = (string) ($silverTier->upgrade()['whatsapp_message'] ?? '');
        $upgradeWhatsappUrl = (! $isGold && filled($upgradeMessage))
            ? 'https://wa.me/'.config('commerce.support.whatsapp_number', '573117479607').'?text='.rawurlencode($upgradeMessage)
            : null;
        $canOpenUpgradeModal = ! $isGold && auth()->user()?->distributor !== null;

        $advisorUrl = 'https://wa.me/573117479607?text='.rawurlencode('Hola, tengo dudas con mi pedido en el portal ICMTHERAPY.');
    @endphp

    <x-slot name="header">
        <x-ui.page-header
            title="Mi carrito"
            subtitle="Revisa los productos que agregaste y continúa con tu pedido."
        >
            <x-slot name="meta">
                <span data-cart-products-count>{{ number_format($itemsCount) }}</span>
                {{ \Illuminate\Support\Str::plural('producto', $itemsCount) }}
                ·
                <span data-cart-units-count>{{ number_format($unitsCount) }}</span>
                {{ \Illuminate\Support\Str::plural('unidad', $unitsCount) }}
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if($items->isEmpty())
        <x-ui.empty-state-panel
            eyebrow="Pedido en preparación"
            title="Tu carrito está listo para empezar"
            description="Explora el catálogo, agrega los productos que necesitas y arma tu pedido comercial en un solo lugar."
        >
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <circle cx="9" cy="19" r="1.75" />
                    <circle cx="17" cy="19" r="1.75" />
                    <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8H18a1 1 0 0 0 .97-.76L20.6 8H7" />
                </svg>
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('catalog.index') }}" class="btn btn-primary">Ir al catálogo</a>
            </x-slot>
        </x-ui.empty-state-panel>
    @else
        <div class="space-y-4">
            @if($isGold)
                {{-- Franja de garantías comerciales (Cliente Oro) --}}
                <div class="cart-review-strip">
                    <div class="cart-review-feature">
                        <span class="cart-review-feature-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 16 -1.6 -8 4.6 3.5L12 5l3.9 6.5L20.6 8 19 16z"/><path d="M5 19h14"/></svg>
                        </span>
                        <p class="cart-review-feature-text">Precios exclusivos por tu nivel ORO</p>
                    </div>
                    <div class="cart-review-feature">
                        <span class="cart-review-feature-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-5H6a2 2 0 0 0-2 2z"/><path d="M20 13v3a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2z"/><path d="M17 18a3 3 0 0 1-3 3h-1"/></svg>
                        </span>
                        <p class="cart-review-feature-text">Asesoría especializada en tu compra</p>
                    </div>
                    <div class="cart-review-feature">
                        <span class="cart-review-feature-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17.5" cy="18" r="1.8"/></svg>
                        </span>
                        <p class="cart-review-feature-text">Envíos a todo el país con cobertura nacional</p>
                    </div>
                    <div class="cart-review-feature">
                        <span class="cart-review-feature-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v5c0 4.5 3 7.5 7 10 4-2.5 7-5.5 7-10V6z"/></svg>
                        </span>
                        <p class="cart-review-feature-text">Garantía y respaldo ICMTHERAPY</p>
                    </div>
                </div>
            @elseif($hasPotentialSavings)
                {{-- Banner de oportunidad Oro (Cliente Plata) --}}
                <div class="cart-review-oro-upsell">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <span class="cart-review-oro-upsell-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="m5 16 -1.6 -8 4.6 3.5L12 5l3.9 6.5L20.6 8 19 16z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">
                                Con nivel <span class="text-amber-700">Oro</span> te habrías ahorrado
                                <span class="text-amber-700" data-sum-potential-savings>{{ $money($potentialSavings) }}</span>
                                en este pedido
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Descuento estimado nivel Oro: <span data-sum-potential-savings-pct>{{ $potentialSavingsPct }}</span>%
                            </p>
                        </div>
                    </div>
                    @if($canOpenUpgradeModal)
                        <button type="button" class="cart-review-oro-upsell-cta" @click="$dispatch('open-modal', 'tier-upgrade')">
                            Solicitar ascenso a Oro
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </button>
                    @elseif($upgradeWhatsappUrl)
                        <a href="{{ $upgradeWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="cart-review-oro-upsell-cta">
                            Solicitar ascenso a Oro
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                    @endif
                </div>
            @endif

            <form id="cart-update-form" action="{{ route('cart.update') }}" method="POST" data-loading-form data-cart-form class="grid min-w-0 gap-4 lg:grid-cols-[1.85fr_1fr] lg:items-start">
                @csrf
                @method('PATCH')

                {{-- Listado de productos --}}
                <x-ui.card class="min-w-0 p-4 sm:p-5">
                    <div class="cart-review-head">
                        <span>Producto</span>
                        <span>Precio unitario</span>
                        <span class="text-center">Cantidad</span>
                        <span class="text-right">Total</span>
                    </div>

                    <div class="space-y-3 lg:space-y-0">
                        @foreach($items as $item)
                            @php
                                $product = $item['product'];
                                $lineKey = (string) $item['line_key'];
                                $inputId = 'qty-'.preg_replace('/[^A-Za-z0-9\-_]/', '-', $lineKey);
                                $available = $item['available_qty'];
                                $inStock = $available === null || (int) $available > 0;
                                $lineSavings = (float) $item['unit_savings'];
                                $linePct = ($item['silver_unit_price'] > 0 && $lineSavings > 0)
                                    ? (int) round($lineSavings / $item['silver_unit_price'] * 100)
                                    : 0;
                            @endphp
                            <article
                                class="cart-review-line"
                                data-cart-item
                                data-unit-price="{{ (float) $item['unit_price'] }}"
                                data-base-price="{{ (float) $item['base_unit_price'] }}"
                                data-silver-price="{{ (float) $item['silver_unit_price'] }}"
                                data-vat-excluded="{{ $item['is_vat_excluded'] ? '1' : '0' }}"
                                data-stock-limit="{{ $available ?? '' }}"
                            >
                                {{-- Columna: Producto --}}
                                <div class="flex min-w-0 items-start gap-3">
                                    <x-ui.product-thumb :product="$product" size="md" />
                                    <div class="min-w-0">
                                        <p class="line-clamp-2 text-sm font-semibold text-slate-900">{{ $product->name }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                            <span>SKU: {{ $product->sku }}</span>
                                            @if($inStock)
                                                <span class="cart-review-stock cart-review-stock--ok">En stock</span>
                                            @else
                                                <span class="cart-review-stock cart-review-stock--out">Agotado</span>
                                            @endif
                                        </div>
                                        @if($product->brand)
                                            <p class="text-xs text-slate-400">Marca: {{ $product->brand }}</p>
                                        @endif
                                        @if($item['variant_label'])
                                            <p class="text-xs text-slate-400">{{ $item['variant_label'] }}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Columna: Precio unitario --}}
                                <div class="min-w-0">
                                    <span class="cart-review-cell-label lg:hidden">Precio unitario</span>
                                    <p class="text-sm font-semibold text-slate-900">{{ $money($item['unit_price']) }}</p>
                                    <p class="text-[0.7rem] text-slate-400">{{ $item['vat_label'] }}</p>
                                    @if($isGold && $linePct > 0)
                                        <span class="cart-review-oro-chip mt-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="m5 16 -1.6 -8 4.6 3.5L12 5l3.9 6.5L20.6 8 19 16z"/></svg>
                                            {{ $linePct }}% dto. Cliente ORO
                                        </span>
                                    @elseif(! $isGold && (float) $item['base_unit_price'] < (float) $item['unit_price'])
                                        <span class="cart-review-oro-estimate-chip mt-1.5">
                                            Precio Oro estimado: {{ $money($item['base_unit_price']) }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Columna: Cantidad --}}
                                <div class="flex flex-col items-start gap-2 lg:items-center">
                                    <span class="cart-review-cell-label lg:hidden">Cantidad</span>
                                    <div class="cart-review-stepper">
                                        <button type="button" data-cart-step="-1" data-cart-target="{{ $inputId }}" class="cart-review-step" aria-label="Disminuir cantidad">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/></svg>
                                        </button>
                                        <input
                                            id="{{ $inputId }}"
                                            type="number"
                                            name="quantities[{{ $lineKey }}]"
                                            min="0"
                                            step="1"
                                            value="{{ (int) $item['qty'] }}"
                                            @if($available !== null) max="{{ (int) $available }}" @endif
                                            class="no-number-spinner w-12 border-0 bg-transparent text-center text-sm font-bold text-slate-900 focus:ring-0"
                                            data-cart-qty
                                            aria-label="Cantidad para {{ $product->name }}"
                                        >
                                        <button type="button" data-cart-step="1" data-cart-target="{{ $inputId }}" class="cart-review-step cart-review-step--inc" aria-label="Aumentar cantidad">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                    <button type="button" data-cart-remove="{{ $inputId }}" class="cart-review-remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M9 7V5h6v2M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12"/></svg>
                                        Eliminar
                                    </button>
                                </div>

                                {{-- Columna: Total --}}
                                <div class="lg:text-right">
                                    <span class="cart-review-cell-label lg:hidden">Total</span>
                                    <p class="text-base font-bold text-slate-950" data-cart-subtotal-amount>{{ $money($item['subtotal']) }}</p>
                                    <p class="text-[0.7rem] text-slate-400">{{ $item['vat_label'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                        <button type="button" data-cart-clear class="cart-review-remove">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M9 7V5h6v2M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12"/></svg>
                            Vaciar carrito
                        </button>
                        <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-dark transition hover:gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                            Seguir comprando
                        </a>
                    </div>
                </x-ui.card>

                {{-- Resumen del pedido --}}
                <aside class="min-w-0 max-w-full space-y-4 lg:sticky lg:top-24 lg:h-fit">
                    <x-ui.card class="min-w-0 p-5">
                        <h2 class="text-base font-bold text-slate-900">Resumen del pedido</h2>

                        <dl class="mt-4 space-y-2.5 text-sm">
                            @if($isGold && $hasSavings)
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-500">Subtotal (precio Plata)</dt>
                                    <dd class="font-medium text-slate-700" data-sum-list>{{ $money($listTotal) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="font-medium text-amber-600">Descuento Cliente ORO (<span data-sum-savings-pct>{{ $savingsPct }}</span>%)</dt>
                                    <dd class="font-semibold text-amber-600">− <span data-sum-savings>{{ $money($savingsTotal) }}</span></dd>
                                </div>
                                <div class="!mt-3 border-t border-dashed border-slate-200 pt-3"></div>
                            @elseif(! $isGold)
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-500">Subtotal (antes de IVA)</dt>
                                    <dd class="font-medium text-slate-700" data-sum-net>{{ $money($netTotal) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-500">IVA (13%)</dt>
                                    <dd class="font-medium text-slate-700" data-sum-iva>{{ $money($ivaTotal) }}</dd>
                                </div>
                            @endif
                            @if($isGold)
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-500">Base gravable</dt>
                                    <dd class="font-medium text-slate-700" data-sum-net>{{ $money($netTotal) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-500">IVA (13%)</dt>
                                    <dd class="font-medium text-slate-700" data-sum-iva>{{ $money($ivaTotal) }}</dd>
                                </div>
                            @endif
                        </dl>

                        <div class="cart-review-total {{ $isGold ? 'cart-review-total--gold' : 'cart-review-total--silver' }} mt-4">
                            @if($isGold)
                                <svg class="cart-review-crown" xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m5 16 -1.6 -8 4.6 3.5L12 5l3.9 6.5L20.6 8 19 16z"/></svg>
                            @endif
                            <p class="relative text-xs font-semibold uppercase tracking-wide text-slate-500">Total del pedido</p>
                            <p class="relative mt-1 break-words text-3xl font-bold tracking-tight text-slate-950" data-sum-total>{{ $money($grossTotal) }} <span class="text-base font-semibold text-slate-400">COP</span></p>
                            <p class="relative text-xs text-slate-500">IVA incluido</p>
                        </div>

                        @if(! $isGold && $hasPotentialSavings)
                            <div class="cart-review-oro-savings-box mt-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-none text-amber-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m5 16 -1.6 -8 4.6 3.5L12 5l3.9 6.5L20.6 8 19 16z"/></svg>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">
                                        Si fueras Cliente Oro ahorrarías:
                                        <span class="text-amber-700"><span data-sum-potential-savings>{{ $money($potentialSavings) }}</span> COP</span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Descuento estimado nivel Oro: <span data-sum-potential-savings-pct>{{ $potentialSavingsPct }}</span>%
                                    </p>
                                </div>
                            </div>
                        @endif

                        <ul class="mt-4 space-y-2">
                            @foreach($isGold ? [
                                'Precios exclusivos por tu nivel ORO',
                                'Envíos a todo el país',
                                'Asesoría especializada',
                                'Garantía y respaldo ICMTHERAPY',
                            ] : [
                                'Precios exclusivos por tu nivel Oro',
                                'Envíos a todo el país',
                                'Asesoría especializada',
                                'Garantía y respaldo ICMTHERAPY',
                            ] as $benefit)
                                <li class="cart-review-benefit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-none text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                    {{ $benefit }}
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-5 space-y-2">
                            <x-ui.button type="submit" variant="primary" class="w-full justify-center" data-loading-label="Procesando..." data-checkout-submit>
                                Continuar con mi pedido
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </x-ui.button>
                            <button type="submit" class="w-full text-center text-xs font-semibold text-slate-500 transition hover:text-brand-dark" data-loading-label="Actualizando..." data-cart-update>
                                Actualizar cantidades
                            </button>
                        </div>
                        <input type="hidden" name="redirect_checkout" value="0" data-redirect-checkout-input>
                    </x-ui.card>

                    <div class="cart-review-secure">
                        <span class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full bg-emerald-50 text-emerald-600" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v5c0 4.5 3 7.5 7 10 4-2.5 7-5.5 7-10V6z"/><path d="m9 12 2 2 4-4"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">Compra segura</p>
                            <p class="text-xs text-slate-500">Tu información y transacciones están protegidas con los más altos estándares de seguridad.</p>
                        </div>
                    </div>
                </aside>
            </form>

            {{-- Banner de asesoría --}}
            <div class="cart-review-advisor">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-11 w-11 flex-none items-center justify-center rounded-full bg-white text-brand-dark shadow-sm" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-5H6a2 2 0 0 0-2 2z"/><path d="M20 13v3a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2z"/><path d="M17 18a3 3 0 0 1-3 3h-1"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $isGold ? '¿Dudas con tu pedido?' : '¿Necesitas ayuda con tu pedido?' }}</p>
                        <p class="text-xs text-slate-500">Nuestro equipo comercial está listo para ayudarte.</p>
                    </div>
                </div>
                <a href="{{ $advisorUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8s.7-2 .9-2.2c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2.1.4 0 .5l-.4.6c-.2.2-.3.4-.1.7.2.3.8 1.3 1.7 2 .9.7 1.5.9 1.8 1 .2.1.4.1.6-.1l.7-.9c.2-.2.4-.2.6-.1l1.8.9c.2.1.4.2.5.3.1.2.1.9-.1 1.6z"/></svg>
                    Contactar asesor
                </a>
            </div>

            @if(! $isGold)
                <div class="cart-review-info-strip">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-none text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                    <p class="text-xs text-slate-600">
                        <span class="font-semibold text-slate-800">¿Dudas con tu pedido?</span>
                        Escríbenos por WhatsApp o contáctanos; un asesor comercial te orientará antes de confirmar.
                    </p>
                </div>
            @endif
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cartForm = document.querySelector('[data-cart-form]');

            if (!cartForm) {
                return;
            }

            const VAT_RATE = {{ $vatRate }};
            const IS_GOLD = {{ $isGold ? 'true' : 'false' }};

            const parseQty = (value) => {
                const parsed = Number(value);
                return Number.isFinite(parsed) ? Math.max(0, Math.round(parsed)) : 0;
            };

            const parseStockLimit = (value) => {
                if (value === '' || value === null || value === undefined) {
                    return null;
                }
                const parsed = Number(value);
                return Number.isFinite(parsed) ? Math.max(0, Math.floor(parsed)) : null;
            };

            const numberFormatter = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
            const formatNumber = (value) => numberFormatter.format(Math.max(0, Math.round(value)));
            const formatMoney = (value) => `$${formatNumber(value)}`;

            const setText = (selector, text) => {
                document.querySelectorAll(selector).forEach((el) => { el.textContent = text; });
            };

            const refreshCartSummary = () => {
                let total = 0;
                let listTotal = 0;
                let goldTotal = 0;
                let netTotal = 0;
                let units = 0;
                let products = 0;

                cartForm.querySelectorAll('[data-cart-item]').forEach((item) => {
                    const qtyInput = item.querySelector('[data-cart-qty]');
                    if (!qtyInput) {
                        return;
                    }

                    const stockLimit = parseStockLimit(item.dataset.stockLimit);
                    let qty = parseQty(qtyInput.value);
                    if (stockLimit !== null) {
                        qty = Math.min(qty, stockLimit);
                    }
                    qtyInput.value = String(qty);

                    const unitPrice = Number(item.dataset.unitPrice || 0);
                    const silverPrice = Number(item.dataset.silverPrice || 0);
                    const basePrice = Number(item.dataset.basePrice || 0);
                    const vatExcluded = item.dataset.vatExcluded === '1';
                    const subtotal = qty * unitPrice;

                    total += subtotal;
                    listTotal += qty * silverPrice;
                    goldTotal += qty * basePrice;
                    netTotal += vatExcluded ? subtotal : subtotal / (1 + VAT_RATE);
                    units += qty;
                    if (qty > 0) {
                        products += 1;
                    }

                    const subtotalOutput = item.querySelector('[data-cart-subtotal-amount]');
                    if (subtotalOutput) {
                        subtotalOutput.textContent = formatMoney(subtotal);
                    }
                });

                const savings = Math.max(0, listTotal - total);
                const potentialSavings = Math.max(0, total - goldTotal);
                const iva = Math.max(0, total - netTotal);
                const savingsPct = listTotal > 0 ? Math.round((savings / listTotal) * 100) : 0;
                const potentialSavingsPct = total > 0 ? Math.round((potentialSavings / total) * 100) : 0;

                setText('[data-sum-total]', `${formatMoney(total)} COP`);
                setText('[data-sum-net]', formatMoney(netTotal));
                setText('[data-sum-iva]', formatMoney(iva));
                setText('[data-cart-units-count]', formatNumber(units));
                setText('[data-cart-products-count]', formatNumber(products));

                if (IS_GOLD) {
                    setText('[data-sum-list]', formatMoney(listTotal));
                    setText('[data-sum-savings]', formatMoney(savings));
                    setText('[data-sum-savings-pct]', String(savingsPct));
                } else {
                    setText('[data-sum-potential-savings]', formatMoney(potentialSavings));
                    setText('[data-sum-potential-savings-pct]', String(potentialSavingsPct));
                }
            };

            document.querySelectorAll('[data-cart-step]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.cartTarget || '');
                    if (!input) {
                        return;
                    }
                    const direction = Number(button.dataset.cartStep || 0);
                    const item = input.closest('[data-cart-item]');
                    const stockLimit = parseStockLimit(item?.dataset.stockLimit);
                    let next = Math.max(0, parseQty(input.value) + direction);
                    if (stockLimit !== null) {
                        next = Math.min(next, stockLimit);
                    }
                    input.value = String(next);
                    refreshCartSummary();
                });
            });

            cartForm.querySelectorAll('[data-cart-qty]').forEach((input) => {
                input.addEventListener('input', refreshCartSummary);
                input.addEventListener('change', refreshCartSummary);
            });

            const redirectCheckoutInput = cartForm.querySelector('[data-redirect-checkout-input]');
            const setRedirect = (value) => {
                if (redirectCheckoutInput) {
                    redirectCheckoutInput.value = value;
                }
            };

            cartForm.querySelector('[data-checkout-submit]')?.addEventListener('click', () => setRedirect('1'));
            cartForm.querySelector('[data-cart-update]')?.addEventListener('click', () => setRedirect('0'));

            document.querySelectorAll('[data-cart-remove]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.cartRemove || '');
                    if (!input) {
                        return;
                    }
                    input.value = '0';
                    refreshCartSummary();
                    setRedirect('0');
                    cartForm.requestSubmit();
                });
            });

            cartForm.querySelector('[data-cart-clear]')?.addEventListener('click', () => {
                cartForm.querySelectorAll('[data-cart-qty]').forEach((input) => { input.value = '0'; });
                refreshCartSummary();
                setRedirect('0');
                cartForm.requestSubmit();
            });

            refreshCartSummary();
        });
    </script>
</x-app-layout>
