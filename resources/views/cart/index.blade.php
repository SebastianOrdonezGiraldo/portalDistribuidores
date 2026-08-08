{{--
View contract:
- Source: App\Modules\Orders\Http\Controllers\CartController::index.
- Expects: $items from CartService::items(), $total from CartService::total(), $pricing from CartService::pricingResult().
- Owns: cart review UI and checkout navigation. Commercial decisions come from $pricing only.
- Live sync: PATCH cart.update expectsJson → CartLiveUpdatePresenter fragments (no client-side rule recalculation).
--}}
<x-app-layout>
    @php
        $itemsCount = $items->count();
        $unitsCount = (int) $items->sum(fn ($item) => (int) $item['qty']);
        $money = fn ($value) => '$'.number_format((float) $value, 0, ',', '.');

        $tier = $items->isNotEmpty() ? $items->first()['tier'] : \App\Modules\Shared\Enums\DistributorTier::Silver;
        $isGold = $tier === \App\Modules\Shared\Enums\DistributorTier::Gold;

        /** @var \App\Modules\Orders\Pricing\OrderPricingResult|null $pricing */
        $pricing = $pricing ?? null;
        $checkoutAllowed = $pricing?->checkoutAllowed() ?? true;
        $goldPricingApplied = $pricing?->goldPricingApplied ?? false;
        $goldSavings = $pricing !== null ? (float) $pricing->goldSavingsDecimal() : 0.0;
        $hasGoldSavings = $goldPricingApplied && $goldSavings > 0.5;
        $grossTotal = $pricing !== null ? (float) $pricing->effectiveTotalDecimal() : (float) $total;
        $productsSubtotal = $hasGoldSavings
            ? (float) $pricing->silverCandidateTotalDecimal()
            : $grossTotal;

        $goldTotal = (float) $items->sum(fn ($item) => (float) $item['base_unit_price'] * (int) $item['qty']);
        $potentialSavings = max(0, $grossTotal - $goldTotal);
        $potentialSavingsPct = $grossTotal > 0 ? (int) round($potentialSavings / $grossTotal * 100) : 0;
        $hasPotentialSavings = ! $isGold && $potentialSavings > 0.5;

        $silverTier = \App\Modules\Shared\Enums\DistributorTier::Silver;
        $upgradeMessage = (string) ($silverTier->upgrade()['whatsapp_message'] ?? '');
        $upgradeWhatsappUrl = (! $isGold && filled($upgradeMessage))
            ? (($currentAdvisor ?? null)?->whatsappUrl($upgradeMessage)
                ?? (($supportWhatsappNumber ?? null) ? 'https://wa.me/'.$supportWhatsappNumber.'?text='.rawurlencode($upgradeMessage) : null))
            : null;
        $canOpenUpgradeModal = ! $isGold && auth()->user()?->distributor !== null;

        $advisorUrl = ($currentAdvisor ?? null)?->whatsappUrl('Hola, tengo dudas con mi pedido en el portal ICMTHERAPY.')
            ?? $supportWhatsappUrl
            ?? '#';
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
        <div class="space-y-4" data-cart-live-root>
            <div data-commerce-status-root>
                <x-commerce.order-commercial-status :pricing="$pricing" :tier="$tier" context="cart" />
            </div>

            <div
                data-cart-sync-alert
                class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"
                role="alert"
                aria-live="polite"
            ></div>

            @if($isGold)
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
                <div class="cart-review-oro-upsell" data-oro-upsell>
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

            <form
                id="cart-update-form"
                action="{{ route('cart.update') }}"
                method="POST"
                data-loading-form
                data-cart-form
                data-cart-live="1"
                data-checkout-allowed="{{ $checkoutAllowed ? '1' : '0' }}"
                class="grid min-w-0 gap-4 lg:grid-cols-[1.85fr_1fr] lg:items-start"
            >
                @csrf
                @method('PATCH')

                <x-ui.card class="min-w-0 p-4 sm:p-5">
                    <div class="cart-review-head">
                        <span>Producto</span>
                        <span>Precio unitario</span>
                        <span class="text-center">Cantidad</span>
                        <span class="text-right">Total</span>
                    </div>

                    <div class="space-y-3 lg:space-y-0" data-cart-lines>
                        @foreach($items as $item)
                            @php
                                $product = $item['product'];
                                $lineKey = (string) $item['line_key'];
                                $inputId = 'qty-'.preg_replace('/[^A-Za-z0-9\-_]/', '-', $lineKey);
                                $available = $item['available_qty'];
                                $inStock = $available === null || (int) $available > 0;
                            @endphp
                            <article
                                class="cart-review-line"
                                data-cart-item
                                data-line-key="{{ $lineKey }}"
                                data-unit-price="{{ (float) $item['unit_price'] }}"
                                data-base-price="{{ (float) $item['base_unit_price'] }}"
                                data-silver-price="{{ (float) $item['silver_unit_price'] }}"
                                data-vat-excluded="{{ $item['is_vat_excluded'] ? '1' : '0' }}"
                                data-stock-limit="{{ $available ?? '' }}"
                            >
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

                                <div class="min-w-0" data-line-pricing>
                                    <x-cart.line-pricing
                                        :unit-price="$item['unit_price']"
                                        :silver-unit-price="$item['silver_unit_price']"
                                        :vat-label="$item['vat_label']"
                                        :gold-pricing-applied="$goldPricingApplied"
                                        :line-savings="$item['line_savings']"
                                        :is-gold="$isGold"
                                    />
                                </div>

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

                                <div class="lg:text-right" data-line-subtotal>
                                    <x-cart.line-subtotal :subtotal="$item['subtotal']" :vat-label="$item['vat_label']" />
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

                <aside class="min-w-0 max-w-full space-y-4 lg:sticky lg:top-24 lg:h-fit">
                    <x-ui.card class="min-w-0 p-5">
                        <div data-order-summary-root>
                            <x-cart.order-summary
                                :products-subtotal="$productsSubtotal"
                                :gross-total="$grossTotal"
                                :gold-pricing-applied="$goldPricingApplied"
                                :gold-savings="$goldSavings"
                                :is-gold="$isGold"
                                :has-potential-savings="$hasPotentialSavings"
                                :potential-savings="$potentialSavings"
                                :potential-savings-pct="$potentialSavingsPct"
                            />
                        </div>

                        <ul class="mt-4 space-y-2">
                            @foreach([
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
                            <div data-checkout-cta-root>
                                <x-cart.checkout-cta :checkout-allowed="$checkoutAllowed" />
                            </div>
                            <button
                                type="submit"
                                class="w-full text-center text-xs font-semibold text-slate-500 transition hover:text-brand-dark"
                                data-loading-label="Actualizando..."
                                data-cart-update
                                data-cart-manual-update
                            >
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
            const cartForm = document.querySelector('[data-cart-form][data-cart-live]');

            if (!cartForm) {
                return;
            }

            const IS_GOLD = {{ $isGold ? 'true' : 'false' }};
            const DEBOUNCE_MS = 400;
            const NETWORK_ERROR_MESSAGE = 'No pudimos confirmar los cambios del carrito. Actualiza las cantidades manualmente para continuar.';
            const UPDATING_CTA_HTML = `
                <button type="button" class="btn btn-primary w-full justify-center opacity-60" disabled data-checkout-updating>
                    Actualizando carrito…
                </button>
            `;

            let editVersion = 0;
            let confirmedVersion = 0;
            let dirty = false;
            let requestInFlight = false;
            let syncQueued = false;
            let debounceTimer = null;
            let checkoutAllowedConfirmed = cartForm.dataset.checkoutAllowed === '1';
            let confirmedCtaHtml = document.querySelector('[data-checkout-cta-root]')?.innerHTML || '';
            let consecutiveNetworkFailures = 0;

            const csrfToken = cartForm.querySelector('input[name="_token"]')?.value || '';
            const syncAlert = document.querySelector('[data-cart-sync-alert]');
            const manualUpdateBtn = cartForm.querySelector('[data-cart-manual-update]');
            const redirectCheckoutInput = cartForm.querySelector('[data-redirect-checkout-input]');

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
            const centsToPesos = (cents) => Math.floor(Number(cents || 0) / 100);

            const setText = (selector, text) => {
                document.querySelectorAll(selector).forEach((el) => { el.textContent = text; });
            };

            const showAlert = (message) => {
                if (!syncAlert) {
                    return;
                }
                syncAlert.textContent = message || '';
                syncAlert.classList.toggle('hidden', !message);
            };

            const hideManualUpdate = () => {
                if (manualUpdateBtn) {
                    manualUpdateBtn.classList.add('hidden');
                }
            };

            const showManualUpdate = () => {
                if (manualUpdateBtn) {
                    manualUpdateBtn.classList.remove('hidden');
                }
            };

            hideManualUpdate();

            const showUpdatingCta = () => {
                const root = document.querySelector('[data-checkout-cta-root]');
                if (!root) {
                    return;
                }
                if (!root.querySelector('[data-checkout-updating]')) {
                    confirmedCtaHtml = root.innerHTML;
                }
                root.innerHTML = UPDATING_CTA_HTML;
            };

            const collectQuantities = () => {
                const quantities = {};
                cartForm.querySelectorAll('[data-cart-item]').forEach((item) => {
                    const lineKey = item.dataset.lineKey;
                    const qtyInput = item.querySelector('[data-cart-qty]');
                    if (!lineKey || !qtyInput) {
                        return;
                    }
                    const stockLimit = parseStockLimit(item.dataset.stockLimit);
                    let qty = parseQty(qtyInput.value);
                    if (stockLimit !== null) {
                        qty = Math.min(qty, stockLimit);
                    }
                    quantities[lineKey] = qty;
                });
                return quantities;
            };

            const refreshLocalLineSubtotals = () => {
                let units = 0;
                let products = 0;
                let total = 0;
                let goldTotal = 0;

                cartForm.querySelectorAll('[data-cart-item]').forEach((item) => {
                    const qtyInput = item.querySelector('[data-cart-qty]');
                    if (!qtyInput || qtyInput.value === '') {
                        return;
                    }

                    const stockLimit = parseStockLimit(item.dataset.stockLimit);
                    let qty = parseQty(qtyInput.value);
                    if (stockLimit !== null) {
                        qty = Math.min(qty, stockLimit);
                    }

                    const unitPrice = Number(item.dataset.unitPrice || 0);
                    const basePrice = Number(item.dataset.basePrice || 0);
                    const subtotal = qty * unitPrice;
                    total += subtotal;
                    goldTotal += qty * basePrice;
                    units += qty;
                    if (qty > 0) {
                        products += 1;
                    }

                    const subtotalOutput = item.querySelector('[data-cart-subtotal-amount]');
                    if (subtotalOutput) {
                        subtotalOutput.textContent = formatMoney(subtotal);
                    }
                });

                setText('[data-cart-units-count]', formatNumber(units));
                setText('[data-cart-products-count]', formatNumber(products));

                if (!IS_GOLD) {
                    const potentialSavings = Math.max(0, total - goldTotal);
                    const potentialSavingsPct = total > 0 ? Math.round((potentialSavings / total) * 100) : 0;
                    setText('[data-sum-potential-savings]', formatMoney(potentialSavings));
                    setText('[data-sum-potential-savings-pct]', String(potentialSavingsPct));
                }
            };

            const applyCanonicalState = (payload, { preserveLocalQty = false } = {}) => {
                if (!payload || payload.empty) {
                    window.location.href = payload?.redirect_url || '{{ route('cart.index') }}';
                    return;
                }

                const serverKeys = new Set(Object.keys(payload.lines || {}));

                cartForm.querySelectorAll('[data-cart-item]').forEach((item) => {
                    const lineKey = item.dataset.lineKey;
                    if (!serverKeys.has(lineKey)) {
                        item.remove();
                        return;
                    }

                    const line = payload.lines[lineKey];
                    const qtyInput = item.querySelector('[data-cart-qty]');
                    if (qtyInput && !preserveLocalQty) {
                        qtyInput.value = String(line.qty);
                    }

                    item.dataset.unitPrice = String(centsToPesos(line.unit_price_cents));
                    item.dataset.silverPrice = String(centsToPesos(line.silver_unit_price_cents));
                    item.dataset.basePrice = String(centsToPesos(line.base_unit_price_cents));

                    const pricingRoot = item.querySelector('[data-line-pricing]');
                    if (pricingRoot && typeof line.pricing_html === 'string') {
                        pricingRoot.innerHTML = line.pricing_html;
                    }

                    const subtotalRoot = item.querySelector('[data-line-subtotal]');
                    if (subtotalRoot && typeof line.subtotal_html === 'string') {
                        subtotalRoot.innerHTML = line.subtotal_html;
                    }
                });

                const commercialRoot = document.querySelector('[data-commerce-status-root]');
                if (commercialRoot && payload.html?.commercial_status !== undefined) {
                    commercialRoot.innerHTML = payload.html.commercial_status;
                }

                const summaryRoot = document.querySelector('[data-order-summary-root]');
                if (summaryRoot && payload.html?.order_summary !== undefined) {
                    summaryRoot.innerHTML = payload.html.order_summary;
                }

                const ctaRoot = document.querySelector('[data-checkout-cta-root]');
                if (ctaRoot && payload.html?.checkout_cta !== undefined) {
                    ctaRoot.innerHTML = payload.html.checkout_cta;
                    confirmedCtaHtml = payload.html.checkout_cta;
                    bindCheckoutSubmit();
                }

                if (payload.counts) {
                    setText('[data-cart-products-count]', formatNumber(payload.counts.products || 0));
                    setText('[data-cart-units-count]', formatNumber(payload.counts.units || 0));
                }

                checkoutAllowedConfirmed = Boolean(payload.pricing?.checkout_allowed);
                cartForm.dataset.checkoutAllowed = checkoutAllowedConfirmed ? '1' : '0';
            };

            const bindCheckoutSubmit = () => {
                cartForm.querySelector('[data-checkout-submit]')?.addEventListener('click', (event) => {
                    if (dirty || requestInFlight || !checkoutAllowedConfirmed) {
                        event.preventDefault();
                        showUpdatingCta();
                        return;
                    }
                    if (redirectCheckoutInput) {
                        redirectCheckoutInput.value = '1';
                    }
                });
            };

            bindCheckoutSubmit();

            const markDirty = () => {
                dirty = true;
                editVersion += 1;
                checkoutAllowedConfirmed = false;
                showUpdatingCta();
                showAlert('');
            };

            const scheduleSync = () => {
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }
                debounceTimer = setTimeout(() => {
                    debounceTimer = null;
                    maybeSync();
                }, DEBOUNCE_MS);
            };

            const maybeSync = () => {
                if (requestInFlight) {
                    syncQueued = true;
                    return;
                }
                void runSync();
            };

            const runSync = async () => {
                if (requestInFlight) {
                    syncQueued = true;
                    return;
                }

                requestInFlight = true;
                syncQueued = false;
                const sentVersion = editVersion;
                const quantities = collectQuantities();

                const body = new FormData();
                body.append('_token', csrfToken);
                body.append('_method', 'PATCH');
                Object.entries(quantities).forEach(([lineKey, qty]) => {
                    body.append(`quantities[${lineKey}]`, String(qty));
                });

                try {
                    const response = await fetch(cartForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body,
                        credentials: 'same-origin',
                    });

                    let payload = null;
                    try {
                        payload = await response.json();
                    } catch (_error) {
                        payload = null;
                    }

                    if (response.status === 422 && payload?.state) {
                        applyCanonicalState(payload.state);
                        confirmedVersion = editVersion;
                        dirty = false;
                        consecutiveNetworkFailures = 0;
                        hideManualUpdate();
                        showAlert(payload.message || '');
                        return;
                    }

                    if (!response.ok || !payload || payload.ok !== true) {
                        throw new Error('network');
                    }

                    const hasPendingEdits = editVersion > sentVersion;
                    applyCanonicalState(payload, { preserveLocalQty: hasPendingEdits });

                    if (!hasPendingEdits) {
                        confirmedVersion = sentVersion;
                        dirty = false;
                        consecutiveNetworkFailures = 0;
                        hideManualUpdate();
                        showAlert('');
                    } else {
                        dirty = true;
                        checkoutAllowedConfirmed = false;
                        showUpdatingCta();
                        syncQueued = true;
                        refreshLocalLineSubtotals();
                    }
                } catch (_error) {
                    consecutiveNetworkFailures += 1;
                    dirty = true;
                    checkoutAllowedConfirmed = false;
                    showUpdatingCta();
                    showManualUpdate();
                    showAlert(NETWORK_ERROR_MESSAGE);
                    // No reintentar automáticamente: la petición pudo haberse aplicado en el servidor.
                    syncQueued = false;
                } finally {
                    requestInFlight = false;

                    if (syncQueued) {
                        syncQueued = false;
                        void runSync();
                    }
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
                    let next = Math.max(0, parseQty(input.value || '0') + direction);
                    if (stockLimit !== null) {
                        next = Math.min(next, stockLimit);
                    }
                    input.value = String(next);
                    markDirty();
                    refreshLocalLineSubtotals();
                    scheduleSync();
                });
            });

            cartForm.querySelectorAll('[data-cart-qty]').forEach((input) => {
                input.addEventListener('input', () => {
                    if (input.value === '') {
                        markDirty();
                        return;
                    }
                    markDirty();
                    refreshLocalLineSubtotals();
                    scheduleSync();
                });

                input.addEventListener('change', () => {
                    if (input.value === '') {
                        input.value = '0';
                    }
                    const item = input.closest('[data-cart-item]');
                    const stockLimit = parseStockLimit(item?.dataset.stockLimit);
                    let qty = parseQty(input.value);
                    if (stockLimit !== null) {
                        qty = Math.min(qty, stockLimit);
                    }
                    input.value = String(qty);
                    markDirty();
                    refreshLocalLineSubtotals();
                    scheduleSync();
                });

                input.addEventListener('blur', () => {
                    if (input.value === '') {
                        input.value = '0';
                        markDirty();
                        refreshLocalLineSubtotals();
                        scheduleSync();
                    }
                });
            });

            cartForm.querySelector('[data-cart-update]')?.addEventListener('click', () => {
                if (redirectCheckoutInput) {
                    redirectCheckoutInput.value = '0';
                }
            });

            cartForm.addEventListener('submit', (event) => {
                const goingToCheckout = redirectCheckoutInput?.value === '1';
                if (!goingToCheckout) {
                    return;
                }
                if (dirty || requestInFlight || !checkoutAllowedConfirmed) {
                    event.preventDefault();
                    showUpdatingCta();
                    showAlert('Espera a que se confirmen los cambios del carrito antes de continuar.');
                }
            });

            document.querySelectorAll('[data-cart-remove]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.cartRemove || '');
                    if (!input) {
                        return;
                    }
                    input.value = '0';
                    markDirty();
                    refreshLocalLineSubtotals();
                    scheduleSync();
                });
            });

            cartForm.querySelector('[data-cart-clear]')?.addEventListener('click', () => {
                cartForm.querySelectorAll('[data-cart-qty]').forEach((input) => { input.value = '0'; });
                markDirty();
                refreshLocalLineSubtotals();
                scheduleSync();
            });

            refreshLocalLineSubtotals();
        });
    </script>
</x-app-layout>
