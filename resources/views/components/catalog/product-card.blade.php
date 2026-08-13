@props([
    'product',
    'isLcpCandidate' => false,
    'pricingMode' => null,
    'tier' => null,
])

{{--
Component contract:
- Props: product with category, photos, variants, price, stock, sku, is_vat_excluded;
  isLcpCandidate; optional pricingMode (active-discount|locked-discount|single);
  optional DistributorTier tier (resolved from auth when omitted).
- Slots: none.
- Use for: catalog grids and product-list partials where product relations are eager loaded.
- Notes: dual pricing uses the locally synchronized Gold/Silver pair.
--}}
@php
    use App\Modules\Orders\Pricing\DistributorPriceCalculator;
    use App\Modules\Orders\Pricing\DistributorTierResolver;
    use App\Modules\Shared\Enums\DistributorTier;

    $activeVariants = $product->activeVariantsCollection();
    $hasVariants = $activeVariants->isNotEmpty();
    $detailUrl = route('products.show', $product);
    $coverPhoto = $product->primaryPhoto
        ?? ($product->relationLoaded('photos') ? $product->photos->first() : null);
    $coverPhotoUrl = $coverPhoto
        ? \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($coverPhoto->path)
        : null;
    $coverPhotoWidth = (int) ($coverPhoto?->photo_width ?? 0);
    $coverPhotoHeight = (int) ($coverPhoto?->photo_height ?? 0);
    $coverPhotoDimensions = ($coverPhotoWidth > 0 && $coverPhotoHeight > 0)
        ? ['width' => $coverPhotoWidth, 'height' => $coverPhotoHeight]
        : ['width' => 1200, 'height' => 1200];
    $coverPhotoSrcset = null;
    $coverPhotoSizes = '(min-width: 1280px) 25vw, (min-width: 1024px) 33vw, 50vw';
    $isLcpImage = (bool) $isLcpCandidate;
    $hasStock = is_numeric($product->available_stock) && (float) $product->available_stock > 0;
    $stockLabel = $hasStock ? 'En stock' : 'Agotado';
    $stockLabelClasses = $hasStock ? 'text-emerald-700' : 'text-red-700';
    $isCurrentlyNew = $product->isCurrentlyNew();
    $vatLabel = \App\Modules\Orders\Support\OrderLineVat::label((bool) $product->is_vat_excluded);

    $resolvedTier = $tier instanceof DistributorTier
        ? $tier
        : app(DistributorTierResolver::class)->resolve(auth()->user());

    $isDistributorViewer = auth()->user()?->isDistributor() ?? false;
    $mode = $pricingMode
        ?? ($isDistributorViewer ? $resolvedTier->pricingMode() : 'single');

    $calculator = app(DistributorPriceCalculator::class);
    $pricePairs = $hasVariants
        ? $activeVariants->map(fn ($variant) => [(string) $variant->price, $variant->silver_price])->all()
        : [[(string) $product->price, $product->silver_price]];

    $priced = collect($pricePairs)
        ->filter(fn (array $pair): bool => $pair[1] !== null && (string) $pair[1] !== '')
        ->map(fn (array $pair) => $calculator->calculateFromDecimal($pair[0], (string) $pair[1], $resolvedTier));
    $silverAvailable = count($pricePairs) === $priced->count();

    $minGold = $priced->isEmpty() ? 0 : (float) $priced->min(fn ($p) => (float) $p->basePriceDecimal());
    $maxGold = $priced->isEmpty() ? 0 : (float) $priced->max(fn ($p) => (float) $p->basePriceDecimal());
    $minSilver = $priced->isEmpty() ? 0 : (float) $priced->min(fn ($p) => (float) $p->silverPriceDecimal());
    $maxSilver = $priced->isEmpty() ? 0 : (float) $priced->max(fn ($p) => (float) $p->silverPriceDecimal());

    if ($isDistributorViewer) {
        $minEffective = (float) $priced->min(fn ($p) => (float) $p->effectivePriceDecimal());
        $maxEffective = (float) $priced->max(fn ($p) => (float) $p->effectivePriceDecimal());
    } else {
        // Invitados/admins: precio lista 2026 (Plata), sin descuento Oro.
        $minEffective = $minSilver;
        $maxEffective = $maxSilver;
        $mode = 'single';
    }

    // Unit savings vs gold for display: silver - gold on the representative (min) pair.
    $displaySavings = max(0, $minSilver - $minGold);
    $isRangePrice = $maxEffective > $minEffective || $maxSilver > $minSilver || $maxGold > $minGold;

    $cardCopy = $resolvedTier->productCardCopy();
    $savingsTemplate = (string) ($cardCopy['savings_template'] ?? 'Ahorras :amount');
    $priceBadge = (string) ($cardCopy['price_badge'] ?? 'Precio Oro');
    $standardLabel = (string) ($cardCopy['standard_label'] ?? 'Precio estándar');
    $tierPriceLabel = (string) ($cardCopy['tier_price_label'] ?? 'Precio Oro');
    $formatMoney = static fn (float $amount): string => '$'.number_format($amount, 0, ',', '.');
    $savingsText = str_replace(':amount', $formatMoney($displaySavings), $savingsTemplate);
    $showDual = in_array($mode, ['active-discount', 'locked-discount'], true) && $displaySavings > 0;
    $isLocked = $mode === 'locked-discount';
    $categoryName = $product->category?->name;
    $metaLine = collect([$categoryName, $product->brand])->filter()->implode(' · ');
@endphp

<article @class([
    'group relative flex h-full cursor-pointer flex-col overflow-hidden rounded-xl border bg-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-panel',
    'border-amber-200/90 hover:border-amber-300' => $showDual,
    'border-slate-200 hover:border-slate-300' => ! $showDual,
])>
    <a
        href="{{ $detailUrl }}"
        class="relative isolate flex aspect-square items-center justify-center overflow-hidden bg-slate-100 p-2 focus-ring sm:p-3"
        aria-label="Ver detalle de {{ $product->name }}"
        tabindex="-1"
    >
        @if($isCurrentlyNew)
            <div class="pointer-events-none absolute right-2 top-2 z-10" data-product-new-badge>
                <span class="inline-flex items-center rounded-full bg-[#309EA7] px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.08em] text-white shadow-sm sm:px-2.5 sm:py-1 sm:text-[0.65rem]">
                    NUEVO
                </span>
            </div>
        @endif

        @if($showDual)
            <div @class([
                'pointer-events-none absolute left-2 top-2 z-10',
                'max-w-[calc(100%-5rem)]' => $isCurrentlyNew,
                'max-w-[calc(100%-1rem)]' => ! $isCurrentlyNew,
            ])>
                <span @class([
                    'inline-flex max-w-full items-center truncate rounded-full border px-2 py-0.5 text-[0.65rem] font-semibold shadow-sm',
                    'border-amber-300 bg-amber-100 text-amber-950' => ! $isLocked,
                    'border-amber-300/80 bg-amber-50 text-amber-900' => $isLocked,
                ])>
                    @if($isLocked)
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    @endif
                    {{ $priceBadge }}
                </span>
            </div>
        @endif

        @if($coverPhoto)
            <img
                src="{{ $coverPhotoUrl }}"
                alt=""
                width="{{ $coverPhotoDimensions['width'] }}"
                height="{{ $coverPhotoDimensions['height'] }}"
                loading="{{ $isLcpImage ? 'eager' : 'lazy' }}"
                decoding="{{ $isLcpImage ? 'sync' : 'async' }}"
                @if($isLcpImage)
                    fetchpriority="high"
                @endif
                @if($coverPhotoSrcset)
                    srcset="{{ $coverPhotoSrcset }}"
                    sizes="{{ $coverPhotoSizes }}"
                @endif
                class="h-full w-full object-contain object-center transition duration-300 group-hover:scale-[1.03]"
            >
        @else
            <div class="pointer h-full w-full items-center justify-center bg-gradient-to-br from-slate-50 to-slate-200">
                <div class="rounded-full border border-slate-300 bg-white p-4 shadow-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="m8 13 2.5-2.5 2.5 2.5 3-3 2 2"></path>
                        <circle cx="9" cy="9" r="1.2"></circle>
                    </svg>
                </div>
            </div>
        @endif
    </a>

    <div class="relative z-[1] flex flex-1 flex-col bg-white p-2 sm:p-3">
        {{-- Title in normal flow (no absolute category badge over text). --}}
        <h3 class="min-h-[2.5rem] overflow-hidden text-sm font-semibold leading-tight text-slate-900 [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:2]">
            <a
                href="{{ $detailUrl }}"
                class="focus-ring rounded after:absolute after:inset-0 after:z-0 after:content-['']"
                tabindex="0"
            >{{ $product->name }}</a>
        </h3>

        @if(filled($metaLine))
            <p class="mt-1 truncate text-xs text-slate-500">{{ $metaLine }}</p>
        @endif

        <dl class="mt-1.5 space-y-1 text-xs text-slate-600">
            <div class="flex gap-1.5">
                <dt class="shrink-0 font-medium text-slate-500">SKU</dt>
                <dd class="min-w-0 truncate font-medium text-slate-800">{{ $product->sku }}</dd>
            </div>
        </dl>

        <div class="relative z-10 mt-2 flex flex-1 flex-col justify-end gap-2 sm:mt-2.5">
            <div class="min-w-0">
                <p class="text-xs font-semibold sm:text-sm {{ $stockLabelClasses }}">
                    <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full {{ $hasStock ? 'bg-emerald-500' : 'bg-red-500' }}" aria-hidden="true"></span>
                    {{ $stockLabel }}
                </p>

                @if($showDual)
                    <div class="mt-1.5 space-y-1">
                        <div class="flex flex-wrap items-end justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-[0.65rem] uppercase tracking-wide text-slate-400">{{ $standardLabel }}</p>
                                <p @class([
                                    'text-xs tabular-nums sm:text-sm',
                                    'text-slate-400 line-through' => ! $isLocked,
                                    'font-semibold text-slate-950' => $isLocked,
                                ])>
                                    @if($isRangePrice && $minSilver !== $maxSilver)
                                        {{ $formatMoney($minSilver) }} – {{ $formatMoney($maxSilver) }}
                                    @else
                                        {{ $formatMoney($minSilver) }}
                                    @endif
                                </p>
                            </div>
                            <div class="min-w-0 text-right">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-amber-800/80">{{ $tierPriceLabel }}</p>
                                <p @class([
                                    'rounded-md border px-1.5 py-0.5 text-sm font-semibold tabular-nums tracking-tight sm:text-base',
                                    'border-amber-200 bg-amber-50 text-amber-950' => ! $isLocked,
                                    'border-amber-200/70 bg-amber-50/80 text-amber-900' => $isLocked,
                                ])>
                                    @if($isRangePrice && $minGold !== $maxGold)
                                        {{ $formatMoney($minGold) }} – {{ $formatMoney($maxGold) }}
                                    @else
                                        {{ $formatMoney($minGold) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if($displaySavings > 0)
                            <p @class([
                                'rounded-md border px-2 py-1 text-center text-[0.7rem] font-semibold',
                                'border-emerald-200 bg-emerald-50 text-emerald-800' => ! $isLocked,
                                'border-amber-200 bg-amber-50 text-amber-900' => $isLocked,
                            ])>
                                {{ $savingsText }}
                            </p>
                        @endif
                    </div>
                @else
                    <p class="text-sm font-semibold tabular-nums tracking-tight text-slate-950 sm:text-base lg:text-lg">
                        @if($isRangePrice && $minEffective !== $maxEffective)
                            {{ $formatMoney($minEffective) }} – {{ $formatMoney($maxEffective) }}
                        @else
                            {{ $formatMoney($minEffective) }}
                        @endif
                    </p>
                    @if($isRangePrice)
                        <p class="text-xs text-slate-500">Precio según variante</p>
                    @endif
                @endif

                <p class="mt-0.5 text-xs text-slate-500">{{ $vatLabel }}</p>
            </div>

            <div class="relative z-10 flex items-center justify-end gap-2">
                @if($hasVariants)
                    <a href="{{ $detailUrl }}" class="relative z-10 inline-flex h-10 items-center justify-center rounded-lg border border-brand-primary bg-brand-primary px-3 text-xs font-semibold text-white transition hover:bg-brand-hover focus-ring">
                        Elegir
                    </a>
                @elseif(! $hasStock || ! $silverAvailable)
                    <span class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-slate-100 px-3 text-xs font-semibold text-slate-500">
                        {{ $silverAvailable ? 'No disponible' : 'Precio pendiente' }}
                    </span>
                @else
                    <form action="{{ route('cart.store') }}" method="POST" class="cart-qty-form relative z-10">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="unit_label" value="unidad">
                        <input type="hidden" name="qty" value="1" class="cart-qty-value">

                        <div class="flex items-center gap-1">
                            <div class="cart-qty-control flex items-center rounded-lg border border-slate-200 bg-white shadow-sm">
                                <button
                                    type="button"
                                    class="cart-qty-minus flex h-8 w-7 items-center justify-center text-base font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 active:bg-slate-200"
                                    aria-label="Reducir cantidad"
                                >−</button>
                                <span class="cart-qty-display w-6 select-none text-center text-sm font-bold tabular-nums text-slate-800">1</span>
                                <button
                                    type="button"
                                    class="cart-qty-plus flex h-8 w-7 items-center justify-center text-base font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 active:bg-slate-200"
                                    aria-label="Aumentar cantidad"
                                >+</button>
                            </div>

                            <button
                                type="submit"
                                data-cart-compact="true"
                                class="cart-add-btn inline-flex h-10 min-h-[2.5rem] shrink-0 items-center justify-center gap-1.5 rounded-lg border border-brand-primary bg-brand-primary px-3 text-xs font-semibold text-white transition hover:bg-brand-hover focus-ring sm:w-10 sm:px-0"
                                aria-label="Agregar {{ $product->name }} al carrito"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="10" cy="20.5" r="1.25"></circle>
                                    <circle cx="17.5" cy="20.5" r="1.25"></circle>
                                    <path d="M3 3h2l2.3 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 7H7.2"></path>
                                </svg>
                                <span class="sm:hidden" data-cart-btn-label>Agregar</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</article>
