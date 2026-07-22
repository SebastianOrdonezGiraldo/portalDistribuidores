@push('head')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="robots" content="index,follow">
@endpush

{{--
View contract:
- Source: App\Modules\Catalog\Http\Controllers\ProductController::show.
- Expects: $product plus view data from ProductController::buildViewData(), breadcrumbs, document variables,
  related/alternative paginators, and optional tier pricing fields ($showTierPricing, $distributorTier, $pricingMode,
  dual-price strings, $variantPriceMap).
- Owns: product presentation, add-to-cart form, media gallery, document links, and related product sections.
- Notes: protected document authorization/downloads stay in Documents module; cart writes stay in CartController.
--}}
<x-app-layout>
    {{-- Presentation state for media URLs and defaults; commercial availability is prepared before rendering. --}}
    @php
        $defaultQty = $stepValue;
        $defaultStockLimit = is_numeric($stock ?? null) ? max(0, (int) floor((float) $stock)) : null;
        $mainPhotoUrl = $mainPhoto ? \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($mainPhoto->path) : null;
        $mainPhotoDimensions = $mainPhoto?->resolvedDimensions() ?? ['width' => 1200, 'height' => 1200];
        $mainPhotoSrcset = $mainPhoto?->responsiveSrcsetFromKnownVariants();
        $mainPhotoSizes = '(min-width: 1024px) 50vw, 100vw';
        $thumbSizes = '64px';
        $showTierPricing = (bool) ($showTierPricing ?? false);
        $showDualPricing = (bool) ($showDualPricing ?? false);
        $isLockedDiscount = (bool) ($isLockedDiscount ?? false);
        $distributorTier = $distributorTier ?? null;
        $pricingMode = $pricingMode ?? 'single';
        $variantPriceMap = $variantPriceMap ?? [];
        $savingsTemplate = $savingsTemplate ?? 'Ahorras :amount';
        $savingsText = $savingsText ?? '';
        $formattedGold = $formattedGold ?? $formattedPrice;
        $formattedSilver = $formattedSilver ?? $formattedPrice;
        $priceBadge = $priceBadge ?? 'Precio Oro';
        $standardLabel = $standardLabel ?? 'Precio estándar';
        $tierPriceLabel = $tierPriceLabel ?? 'Precio Oro';
        $displaySavings = (float) ($displaySavings ?? 0);
    @endphp

    {{-- Mismo chrome de header que el catálogo (catalogToolbar), no el buscador global. --}}
    <x-slot name="catalogToolbar">
        <form method="GET" action="{{ route('catalog.index') }}" class="catalog-toolbar">
            <div class="catalog-toolbar-meta">
                <nav aria-label="Breadcrumb" class="catalog-toolbar-breadcrumb">
                    <ol class="flex flex-wrap items-center gap-1">
                        <li>
                            <a href="{{ auth()->check() ? route('dashboard') : route('catalog.index') }}" class="catalog-toolbar-breadcrumb-link">Inicio</a>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li>
                            <a href="{{ route('catalog.index') }}" class="catalog-toolbar-breadcrumb-link">Catálogo</a>
                        </li>
                        @if($product->category_id)
                            <li aria-hidden="true">/</li>
                            <li>
                                <a
                                    href="{{ route('catalog.index', ['category_id' => $product->category_id]) }}"
                                    class="catalog-toolbar-breadcrumb-link"
                                >{{ $categoryName }}</a>
                            </li>
                        @endif
                        <li aria-hidden="true">/</li>
                        <li class="catalog-toolbar-breadcrumb-current max-w-[12rem] truncate sm:max-w-xs">{{ $product->name }}</li>
                    </ol>
                </nav>
            </div>

            <div class="catalog-toolbar-controls">
                <div class="catalog-search-field">
                    <label class="sr-only" for="product-top-search">Buscar productos</label>
                    <svg xmlns="http://www.w3.org/2000/svg" class="catalog-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input
                        id="product-top-search"
                        type="text"
                        name="term"
                        value=""
                        placeholder="Buscar producto, SKU, marca o categoría"
                        class="catalog-search-input"
                    >
                </div>
            </div>
        </form>
    </x-slot>

    <div class="product-detail-page space-y-5 pb-32 sm:pb-24 lg:pb-8">

        {{-- ──────────────────────────────────────────────────────────────
             HERO: imagen (izquierda) + info + compra (derecha)
        ────────────────────────────────────────────────────────────────── --}}
        <section class="product-detail-hero overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft sm:rounded-2xl lg:grid lg:grid-cols-2">

            {{-- Imagen principal: 50% en escritorio, miniaturas integradas abajo --}}
            <div class="relative flex flex-col overflow-hidden border-b border-slate-200 bg-slate-50/80 lg:border-b-0 lg:border-r">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_20%,rgba(54,177,187,0.12),transparent_45%),radial-gradient(circle_at_82%_88%,rgba(15,23,42,0.05),transparent_40%)]"></div>
                <div class="group/img relative flex min-h-[14rem] flex-1 items-center justify-center px-5 py-6 sm:min-h-[24rem] sm:px-10 sm:py-10">
                    @if($mainPhoto)
                        <img
                            data-product-main-image
                            src="{{ $mainPhotoUrl }}"
                            alt="{{ $product->name }}"
                            width="{{ $mainPhotoDimensions['width'] }}"
                            height="{{ $mainPhotoDimensions['height'] }}"
                            loading="eager"
                            fetchpriority="high"
                            decoding="sync"
                            @if($mainPhotoSrcset)
                                srcset="{{ $mainPhotoSrcset }}"
                                sizes="{{ $mainPhotoSizes }}"
                            @endif
                            class="h-full max-h-[28rem] w-full object-contain object-center drop-shadow-[0_20px_28px_rgba(15,23,42,0.18)] transition duration-500 ease-out will-change-transform group-hover/img:scale-[1.06]"
                        >
                    @else
                        <div class="flex flex-col items-center justify-center gap-3 text-center">
                            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-soft">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                                    <path d="m8 13 2.5-2.5 2.5 2.5 3-3 2 2"/>
                                    <circle cx="9" cy="9" r="1.2"/>
                                </svg>
                            </div>
                            <p class="text-xs font-medium text-slate-400">Sin imagen disponible</p>
                        </div>
                    @endif
                </div>

                @if($galleryPhotos->count() > 1)
                    <div class="relative px-5 pb-5 sm:px-8 sm:pb-6" data-product-gallery>
                        <div class="flex gap-2 overflow-x-auto py-1" style="scrollbar-width: thin;">
                            @foreach($galleryPhotos as $photo)
                                @php
                                    $thumbUrl = \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($photo->path);
                                    $thumbDimensions = $photo->resolvedDimensions();
                                    $thumbSrcset = $photo->responsiveSrcsetFromKnownVariants();
                                @endphp
                                <button
                                    type="button"
                                    data-product-thumb
                                    data-src="{{ $thumbUrl }}"
                                    data-alt="{{ $product->name }}"
                                    data-width="{{ $thumbDimensions['width'] }}"
                                    data-height="{{ $thumbDimensions['height'] }}"
                                    @if($thumbSrcset)
                                        data-srcset="{{ $thumbSrcset }}"
                                        data-sizes="{{ $mainPhotoSizes }}"
                                    @endif
                                    class="group/thumb h-14 w-14 shrink-0 overflow-hidden rounded-xl border-2 bg-white/90 transition focus-ring sm:h-16 sm:w-16 {{ $loop->first ? 'border-brand-primary shadow-sm' : 'border-white/70 hover:border-slate-300 hover:bg-white' }}"
                                    aria-label="Ver imagen {{ $loop->iteration }}"
                                >
                                    <img
                                        src="{{ $thumbUrl }}"
                                        alt="{{ $product->name }}"
                                        width="{{ $thumbDimensions['width'] }}"
                                        height="{{ $thumbDimensions['height'] }}"
                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                        decoding="async"
                                        @if($thumbSrcset)
                                            srcset="{{ $thumbSrcset }}"
                                            sizes="{{ $thumbSizes }}"
                                        @endif
                                        class="h-full w-full object-contain p-1 transition duration-200 group-hover/thumb:scale-105"
                                    >
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Información y compra: 50% en escritorio --}}
            <div class="flex flex-col divide-y divide-slate-100">

                {{-- Bloque: información del producto --}}
                <div class="p-5 sm:p-7 lg:p-7 xl:p-8">

                    {{-- Badges de estado --}}
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Solo Oro muestra badge de precio activo; en Plata confundiría con el precio a pagar. --}}
                        @if($showDualPricing && ! $isLockedDiscount)
                            <span class="inline-flex items-center rounded-full border border-amber-300/80 bg-amber-50 px-2.5 py-1 text-[0.7rem] font-semibold text-amber-950">
                                {{ $priceBadge }}
                            </span>
                        @endif

                        <x-ui.badge :variant="$availability['badge']" class="!normal-case !tracking-normal">
                            {{ $availability['label'] }}
                        </x-ui.badge>

                        @if($isLowStock)
                            <x-ui.badge variant="warning" class="!normal-case !tracking-normal">
                                Alta rotación
                            </x-ui.badge>
                        @endif

                        @if($discountPercent)
                            <x-ui.badge variant="brand" class="!normal-case !tracking-normal">
                                −{{ $formatQty($discountPercent) }}% dto.
                            </x-ui.badge>
                        @endif
                    </div>

                    {{-- Categoría como overline --}}
                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">
                        {{ $categoryName }}
                    </p>

                    {{-- Nombre del producto --}}
                    <h1 class="mt-1.5 text-balance text-xl font-bold leading-snug text-slate-950 sm:text-[1.65rem]">
                        {{ $product->name }}
                    </h1>

                    {{-- Marca + SKU en línea --}}
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                <line x1="7" y1="7" x2="7.01" y2="7"/>
                            </svg>
                            <span class="text-slate-500">Marca</span>
                            <span class="font-semibold text-slate-900">{{ $brand }}</span>
                        </div>

                        <div class="h-3.5 w-px bg-slate-200" aria-hidden="true"></div>

                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2"/>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                            </svg>
                            <span class="text-slate-500">SKU</span>
                            <span class="font-mono font-semibold text-slate-900">{{ $product->sku }}</span>
                            <button
                                type="button"
                                data-copy-sku
                                data-value="{{ $product->sku }}"
                                title="Copiar SKU"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400 transition hover:border-slate-300 hover:text-slate-600 focus-ring"
                                aria-label="Copiar SKU"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Descripción resumida --}}
                    @if(! blank($product->description))
                        <div class="mt-5 border-t border-slate-100 pt-5">
                            <p class="line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $product->description }}</p>
                            <button type="button" data-tab-target="descripcion" class="mt-2 inline-flex items-center gap-1 rounded text-xs font-semibold text-brand-primary transition hover:underline focus-ring">
                                Ver descripción completa
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </button>
                        </div>
                    @else
                        <p class="mt-4 text-sm leading-relaxed text-slate-500">{{ $availability['helper'] }}</p>
                    @endif
                </div>

                {{-- Bloque: precio y acción de compra --}}
                <div class="border-t border-slate-100 bg-white p-5 sm:p-6 lg:p-7">

                    {{-- Promo --}}
                    @if($promoLabel)
                        <div class="mb-4 flex items-center gap-2 rounded-lg border border-brand-primary/20 bg-brand-primary/5 px-3 py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                            <p class="text-sm font-semibold text-brand-dark">{{ $promoLabel }}</p>
                        </div>
                    @endif

                    {{-- Precio: siempre gana el precio que paga el cliente --}}
                    <div class="mb-5">
                        @if($showDualPricing)
                            @if($isLockedDiscount)
                                {{-- Plata: tu precio es el héroe; Oro es una pista discreta --}}
                                <div class="product-detail-price">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-slate-400">Tu precio</p>
                                        @if($distributorTier)
                                            <x-tier.badge :tier="$distributorTier" size="sm" :interactive="$distributorTier->hasBenefitsModal()" />
                                        @endif
                                    </div>

                                    <div class="mt-1.5 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                        <span
                                            class="product-detail-price__hero tabular-nums text-slate-950"
                                            data-variant-price-target
                                            data-variant-silver-target
                                            data-default-value="{{ $formattedSilver }}"
                                        >{{ $formattedSilver }}</span>
                                        <span class="text-sm text-slate-500">/ {{ $unitLabelLower }} · {{ $vatLabel }}</span>
                                    </div>

                                    @if($displaySavings > 0)
                                        <button
                                            type="button"
                                            class="product-detail-price__nudge mt-3"
                                            @click="$dispatch('open-modal', 'tier-upgrade')"
                                        >
                                            <span class="product-detail-price__nudge-label">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-amber-700" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="M12 2.5 14.6 8l6 .5-4.6 4 1.4 5.8L12 15.8 6.6 18.3 8 12.5 3.4 8.5l6-.5L12 2.5Z"/>
                                                </svg>
                                                Con Oro
                                            </span>
                                            <span
                                                class="tabular-nums font-semibold text-amber-950"
                                                data-variant-gold-target
                                                data-default-value="{{ $formattedGold }}"
                                            >{{ $formattedGold }}</span>
                                            <span class="text-amber-800/80">·</span>
                                            <span
                                                class="font-medium text-amber-900/90"
                                                data-variant-savings-target
                                                data-default-value="{{ $savingsText }}"
                                                data-savings-template="{{ $savingsTemplate }}"
                                            >{{ $savingsText }}</span>
                                        </button>
                                    @endif
                                </div>
                            @else
                                {{-- Oro: precio activo grande; estándar tachado y ahorro compacto --}}
                                <div class="product-detail-price">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-amber-800/70">{{ $tierPriceLabel }}</p>
                                        @if($distributorTier)
                                            <x-tier.badge :tier="$distributorTier" size="sm" :interactive="$distributorTier->hasBenefitsModal()" />
                                        @endif
                                    </div>

                                    <div class="mt-1.5">
                                        <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                                            <span
                                                class="product-detail-price__hero product-detail-price__hero--gold tabular-nums"
                                                data-variant-gold-target
                                                data-default-value="{{ $formattedGold }}"
                                            >{{ $formattedGold }}</span>
                                            <span
                                                class="text-base tabular-nums text-slate-400 line-through"
                                                data-variant-silver-target
                                                data-default-value="{{ $formattedSilver }}"
                                            >{{ $formattedSilver }}</span>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-500">/ {{ $unitLabelLower }} · {{ $vatLabel }}</p>
                                        @if($displaySavings > 0)
                                            <p class="product-detail-price__save mt-2">
                                                <span
                                                    data-variant-savings-target
                                                    data-default-value="{{ $savingsText }}"
                                                    data-savings-template="{{ $savingsTemplate }}"
                                                >{{ $savingsText }}</span>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @else
                            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-slate-400">Precio</p>
                            <div class="mt-1.5 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                <span
                                    class="product-detail-price__hero tabular-nums text-slate-950"
                                    data-variant-price-target
                                    data-default-value="{{ $formattedPrice }}"
                                >{{ $formattedPrice }}</span>
                                <span class="text-sm text-slate-500">/ {{ $unitLabelLower }} · {{ $vatLabel }}</span>
                            </div>
                        @endif

                        @if($hasVariants)
                            <p class="mt-1.5 text-xs text-slate-400">El precio varía según la variante seleccionada</p>
                        @endif
                    </div>

                    {{-- Disponibilidad + stock (fila compacta) --}}
                    <div class="mb-5 flex flex-wrap items-start gap-x-5 gap-y-2 rounded-xl border border-slate-200/80 bg-slate-50/70 px-3.5 py-3">
                        <div class="min-w-0">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-slate-400">Disponibilidad</p>
                            <div class="mt-1">
                                <x-ui.badge :variant="$availability['badge']" class="!normal-case !tracking-normal">
                                    {{ $availability['label'] }}
                                </x-ui.badge>
                            </div>
                        </div>
                        <div class="hidden h-10 w-px bg-slate-200 sm:block" aria-hidden="true"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-slate-400">Stock</p>
                            <p
                                class="mt-1 text-sm font-semibold text-slate-900"
                                data-variant-stock-target
                                data-default-value="{{ $stockLabel }}"
                            >{{ $stockLabel }}</p>
                            <p class="mt-0.5 text-xs {{ $stockIsStale ? 'text-amber-700' : 'text-slate-400' }}">
                                {{ $stockFreshnessLabel }}
                                @if($stockIsStale)
                                    · Puede requerir actualización
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Formulario de compra --}}
                    <form
                        id="product-purchase-form"
                        action="{{ route('cart.store') }}"
                        method="POST"
                        data-loading-form
                        data-qty-control
                        data-min-multiple="{{ $stepValue }}"
                        data-default-stock="{{ $defaultStockLimit ?? '' }}"
                        class="space-y-4"
                    >
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="unit_label" value="{{ $unitLabel }}">

                        {{-- Selector de variante --}}
                        @if($hasVariants)
                            <div>
                                <label class="form-label" for="purchase-variant">
                                    Selecciona {{ \Illuminate\Support\Str::lower($variantAttributeName) }}
                                    <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <x-ui.select
                                    id="purchase-variant"
                                    name="variant_id"
                                    required
                                    data-variant-select
                                    :disabled="! $canBuy"
                                >
                                    <option value="">— Seleccionar {{ \Illuminate\Support\Str::lower($variantAttributeName) }} —</option>
                                    @foreach($activeVariants as $variant)
                                        @php
                                            $variantPrices = $variantPriceMap[(int) $variant->id] ?? null;
                                            $variantEffective = (float) ($variantPrices['effective'] ?? $variant->price);
                                            $variantGold = (float) ($variantPrices['gold'] ?? $variant->price);
                                            $variantSilver = (float) ($variantPrices['silver'] ?? $variant->price);
                                            $variantStock = $variant->stock;
                                            $variantValue = $variant->attributeValue?->value ?? 'Valor';
                                            $variantStockLabel = is_null($variantStock)
                                                ? 'Stock a confirmar'
                                                : $formatQty($variantStock).' '.$unitLabelLower;
                                        @endphp
                                        <option
                                            value="{{ $variant->id }}"
                                            data-price="{{ $variantEffective }}"
                                            data-price-gold="{{ $variantGold }}"
                                            data-price-silver="{{ $variantSilver }}"
                                            data-stock="{{ $variantStock ?? '' }}"
                                            data-stock-max="{{ is_null($variantStock) ? '' : max(0, (int) floor((float) $variantStock)) }}"
                                            @selected((string) old('variant_id') === (string) $variant->id)
                                        >
                                            {{ $variantValue }} — ${{ number_format($variantEffective, 0, ',', '.') }} · {{ $variantStockLabel }}
                                        </option>
                                    @endforeach
                                </x-ui.select>
                                <x-input-error :messages="$errors->get('variant_id')" />
                            </div>
                        @endif

                        {{-- Cantidad + CTA --}}
                        <div>
                            <label for="purchase-qty" class="mb-2 block text-sm font-semibold text-slate-700">
                                Cantidad a agregar
                            </label>

                            <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch sm:gap-3">
                                {{-- Stepper de cantidad --}}
                                <div class="inline-flex w-fit shrink-0 items-center rounded-xl border border-slate-300 bg-white shadow-sm">
                                    <button
                                        type="button"
                                        data-qty-step="-1"
                                        class="inline-flex h-11 w-10 items-center justify-center rounded-l-xl text-lg font-medium text-slate-700 transition hover:bg-slate-100 focus-ring disabled:cursor-not-allowed disabled:opacity-40"
                                        aria-label="Disminuir cantidad"
                                        @disabled(! $canBuy)
                                    >−</button>
                                    <input
                                        id="purchase-qty"
                                        type="number"
                                        name="qty"
                                        value="{{ $defaultQty }}"
                                        min="{{ $stepValue }}"
                                        step="{{ $stepValue }}"
                                        @if($defaultStockLimit !== null) max="{{ $defaultStockLimit }}" @endif
                                        inputmode="numeric"
                                        data-qty-input
                                        data-primary-qty
                                        data-shared-qty
                                        class="no-number-spinner w-14 border-0 bg-transparent text-center text-base font-bold text-slate-900 focus:ring-0 disabled:opacity-50"
                                        @disabled(! $canBuy)
                                        aria-label="Cantidad"
                                    >
                                    <button
                                        type="button"
                                        data-qty-step="1"
                                        class="inline-flex h-11 w-10 items-center justify-center rounded-r-xl text-lg font-medium text-slate-700 transition hover:bg-slate-100 focus-ring disabled:cursor-not-allowed disabled:opacity-40"
                                        aria-label="Aumentar cantidad"
                                        @disabled(! $canBuy)
                                    >+</button>
                                </div>

                                {{-- Botón principal CTA --}}
                                <button
                                    type="submit"
                                    class="btn btn-primary h-11 w-full justify-center gap-2 text-sm font-semibold sm:flex-1"
                                    data-loading-label="Agregando..."
                                    @disabled(! $canBuy)
                                >
                                    @if($canBuy)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="10" cy="20.5" r="1.25"/><circle cx="17.5" cy="20.5" r="1.25"/>
                                            <path d="M3 3h2l2.3 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 7H7.2"/>
                                        </svg>
                                        Agregar al pedido
                                    @else
                                        No disponible
                                    @endif
                                </button>
                            </div>

                            {{-- Indicadores de múltiplo y no disponible --}}
                            @if($minMultiple > 1)
                                <p class="mt-2 flex items-center gap-1 text-xs text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                    </svg>
                                    Se vende en múltiplos de {{ $stepValue }} {{ $unitLabelLower }}.
                                </p>
                            @endif

                            @if(! $canBuy)
                                <div class="mt-3 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                    <p class="text-xs font-medium text-amber-800">
                                        Sin inventario inmediato.
                                        <button type="button" data-tab-target="alternativas" class="underline hover:no-underline">Consulta las alternativas</button>.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </form>

                    {{-- Info comercial: entrega --}}
                    @if($leadTimeLabel || $etaLabel)
                        <div class="mt-5 space-y-2 border-t border-slate-200 pt-4">
                            @if($leadTimeLabel)
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span>{{ $leadTimeLabel }}</span>
                                </div>
                            @endif
                            @if($etaLabel)
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/>
                                    </svg>
                                    <span>{{ $etaLabel }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="space-y-5" data-product-tabs>
        {{-- ──────────────────────────────────────────────────────────────
             Pestañas de contenido: solo se muestra un panel a la vez
        ────────────────────────────────────────────────────────────────── --}}
        <nav
            class="-mx-3 overflow-x-auto border-y border-slate-200 bg-white/95 backdrop-blur sm:-mx-6 lg:-mx-8"
            aria-label="Información del producto"
        >
            <div class="flex min-w-max items-center px-3 sm:px-6 lg:px-8" role="tablist" aria-label="Información del producto">
                @foreach($sections as $section)
                    <button
                        type="button"
                        id="product-tab-{{ $section['id'] }}"
                        role="tab"
                        aria-controls="product-panel-{{ $section['id'] }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        tabindex="{{ $loop->first ? '0' : '-1' }}"
                        data-product-tab="{{ $section['id'] }}"
                        data-tab-target="{{ $section['id'] }}"
                        class="inline-flex items-center border-b-2 px-4 py-3.5 text-sm font-semibold transition hover:border-slate-300 hover:text-slate-900 focus-ring rounded-t {{ $loop->first ? 'border-brand-primary text-brand-primary' : 'border-transparent text-slate-500' }}"
                    >
                        {{ $section['label'] }}
                    </button>
                @endforeach
            </div>
        </nav>

        {{-- ──────────────────────────────────────────────────────────────
             Sección: Descripción
        ────────────────────────────────────────────────────────────────── --}}
        <section id="product-panel-descripcion" role="tabpanel" aria-labelledby="product-tab-descripcion" data-tab-panel="descripcion" class="card overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 sm:px-7">
                <h2 class="text-lg font-bold text-slate-950">Descripción del producto</h2>
            </div>
            <div class="px-6 py-5 sm:px-7 sm:py-6">
                @if(blank($product->description))
                    <div class="flex items-start gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-5 py-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Sin descripción comercial</p>
                            <p class="mt-0.5 text-sm text-slate-500">
                                Usa las especificaciones técnicas y los documentos adjuntos para validar la compra, o contacta a tu asesor comercial.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="prose prose-sm prose-slate max-w-none">
                        <p class="whitespace-pre-line leading-relaxed text-slate-700">{{ $product->description }}</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- ──────────────────────────────────────────────────────────────
             Sección: Especificaciones técnicas
        ────────────────────────────────────────────────────────────────── --}}
        <section id="product-panel-especificaciones" role="tabpanel" aria-labelledby="product-tab-especificaciones" data-tab-panel="especificaciones" class="card overflow-hidden" hidden>
            <div class="border-b border-slate-100 px-6 py-4 sm:px-7">
                <h2 class="text-lg font-bold text-slate-950">Especificaciones técnicas</h2>
                <p class="mt-0.5 text-xs text-slate-500">Información comercial y de identificación del producto</p>
            </div>
            <div class="px-6 py-5 sm:px-7 sm:py-6">
                @if(count($specRows) > 0)
                    <dl class="divide-y divide-slate-100">
                        @foreach($specRows as $index => $row)
                            <div class="grid gap-1.5 py-3 sm:grid-cols-[200px_1fr] sm:items-baseline sm:gap-4 {{ $index === 0 ? 'pt-0' : '' }} {{ $index === count($specRows) - 1 ? 'pb-0' : '' }}">
                                <dt class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-400">{{ $row['label'] }}</dt>
                                <dd class="text-sm font-medium text-slate-800">{{ $row['value'] ?? '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <div class="flex items-start gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-5 py-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <p class="text-sm text-slate-500">No hay especificaciones cargadas para este producto.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- ──────────────────────────────────────────────────────────────
             Sección: Documentos — ⚠️ Afecta descargas de PDF protegidas
             Si se modifica la ruta route('documents.tech-sheet.download')
             o route('documents.manual.download')
             o route('documents.invima.download')
             o route('documents.quick-guide.download')
             o route('documents.calibration-document.download')
             también se debe actualizar el controlador de documentos.
        ────────────────────────────────────────────────────────────────── --}}
        <section id="product-panel-documentos" role="tabpanel" aria-labelledby="product-tab-documentos" data-tab-panel="documentos" class="card overflow-hidden" hidden>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4 sm:px-7">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Documentos</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Fichas técnicas, manuales y documentos comerciales</p>
                </div>
                @if(! is_null($remainingDownloads))
                    <div class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <span class="text-xs font-semibold text-slate-600">
                            {{ $remainingDownloads }}/{{ $techSheetMonthlyLimit }} descargas restantes este mes
                        </span>
                    </div>
                @endif
            </div>

            <div class="px-6 py-5 sm:px-7 sm:py-6">
                @php
                    $hasAnyDocument = $techSheet || $manual || $productVideo || $invima || $quickGuide || $calibrationDocument || $secondaryDocuments->isNotEmpty();
                @endphp

                @if(! $hasAnyDocument)
                    <div class="flex items-start gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-5 py-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Sin documentos disponibles</p>
                            <p class="mt-0.5 text-sm text-slate-500">Solicita la ficha técnica o documentos a tu asesor comercial.</p>
                        </div>
                    </div>
                @else
                    {{-- Grid principal: Ficha técnica + Manual de usuario + Video de apoyo + INVIMA + Guía rápida + Calibración --}}
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">

                        {{-- Tarjeta izquierda: Ficha técnica --}}
                        <div class="group relative overflow-hidden rounded-2xl border transition {{ $techSheet ? 'border-brand-primary/30 bg-brand-primary/5 hover:border-brand-primary/50' : 'border-slate-200 bg-slate-50' }}">
                            <div class="flex items-start gap-4 p-5">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $techSheet ? 'bg-brand-primary/15 text-brand-primary' : 'bg-slate-200 text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                        <polyline points="10 9 9 9 8 9"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900">Ficha técnica</p>
                                        <span class="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-xs font-bold uppercase tracking-wide text-slate-500">PDF</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">Documento principal para validación técnica y comercial</p>

                                    <div class="mt-3">
                                        @if($techSheet)
                                            <a
                                                href="{{ route('documents.tech-sheet.download', $techSheet) }}"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-brand-primary/40 bg-white px-3.5 py-2 text-sm font-semibold text-brand-primary shadow-sm transition hover:bg-brand-primary/5 hover:border-brand-primary focus-ring sm:w-auto"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                    <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                                </svg>
                                                Descargar ficha técnica
                                            </a>
                                        @else
                                            <p class="text-xs text-slate-400">No disponible — solicítala a tu asesor</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tarjeta central: Manual de usuario --}}
                        <div class="group relative overflow-hidden rounded-2xl border transition {{ $manual ? 'border-amber-200 bg-amber-50/70 hover:border-amber-300' : 'border-slate-200 bg-slate-50' }}">
                            <div class="flex items-start gap-4 p-5">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $manual ? 'bg-amber-100 text-amber-600' : 'bg-slate-200 text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                        <path d="M6.5 2H20v18H6.5A2.5 2.5 0 0 0 4 22V4.5A2.5 2.5 0 0 1 6.5 2z"/>
                                        <path d="M9 7h7"/>
                                        <path d="M9 11h7"/>
                                        <path d="M9 15h4"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900">Manual de usuario</p>
                                        <span class="rounded-md border border-amber-200 bg-white px-1.5 py-0.5 text-xs font-bold uppercase tracking-wide text-amber-700">PDF</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">Guía de uso, instalación y operación del producto</p>

                                    <div class="mt-3">
                                        @if($manual)
                                            <a
                                                href="{{ route('documents.manual.download', $manual) }}"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-amber-300 bg-white px-3.5 py-2 text-sm font-semibold text-amber-700 shadow-sm transition hover:border-amber-400 hover:bg-amber-50/70 focus-ring sm:w-auto"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 3v12"/>
                                                    <path d="m7 10 5 5 5-5"/>
                                                    <path d="M5 21h14"/>
                                                </svg>
                                                Descargar manual
                                            </a>
                                        @else
                                            <p class="text-xs text-slate-400">No disponible por ahora</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tarjeta derecha: Video de apoyo --}}
                        <div class="group relative overflow-hidden rounded-2xl border transition {{ $productVideo ? 'border-slate-200 bg-white hover:border-slate-300' : 'border-slate-200 bg-slate-50' }}">
                            <div class="flex items-start gap-4 p-5">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $productVideo ? 'bg-rose-50 text-rose-500' : 'bg-slate-200 text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <polygon points="23 7 16 12 23 17 23 7"/>
                                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900">Video de apoyo</p>
                                        <span class="rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-xs font-bold uppercase tracking-wide text-slate-400">Video</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">Demostración y guía de uso del producto</p>

                                    <div class="mt-3">
                                        @if($productVideo)
                                            <a
                                                href="{{ $productVideo->url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 focus-ring sm:w-auto"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                                </svg>
                                                Ver video
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                                    <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                                                </svg>
                                            </a>
                                        @else
                                            <p class="text-xs text-slate-400">Sin video disponible para este producto</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tarjeta: INVIMA --}}
                        <div class="group relative overflow-hidden rounded-2xl border transition {{ $invima ? 'border-emerald-200 bg-emerald-50/70 hover:border-emerald-300' : 'border-slate-200 bg-slate-50' }}">
                            <div class="flex items-start gap-4 p-5">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $invima ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-200 text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        <path d="m9 12 2 2 4-5"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900">INVIMA</p>
                                        <span class="rounded-md border border-emerald-200 bg-white px-1.5 py-0.5 text-xs font-bold uppercase tracking-wide text-emerald-700">PDF</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">Registro sanitario y soporte regulatorio del producto</p>

                                    <div class="mt-3">
                                        @if($invima)
                                            <a
                                                href="{{ route('documents.invima.download', $invima) }}"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-white px-3.5 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:border-emerald-400 hover:bg-emerald-50/70 focus-ring sm:w-auto"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 3v12"/>
                                                    <path d="m7 10 5 5 5-5"/>
                                                    <path d="M5 21h14"/>
                                                </svg>
                                                Descargar INVIMA
                                            </a>
                                        @else
                                            <p class="text-xs text-slate-400">No disponible por ahora</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tarjeta: Guía rápida --}}
                        <div class="group relative overflow-hidden rounded-2xl border transition {{ $quickGuide ? 'border-sky-200 bg-sky-50/70 hover:border-sky-300' : 'border-slate-200 bg-slate-50' }}">
                            <div class="flex items-start gap-4 p-5">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $quickGuide ? 'bg-sky-100 text-sky-600' : 'bg-slate-200 text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M8 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                                        <path d="M18 3h-6a2 2 0 0 0-2 2v6"/>
                                        <path d="M14 3v4h4"/>
                                        <path d="M8 15h8"/>
                                        <path d="M8 18h5"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900">Guía rápida</p>
                                        <span class="rounded-md border border-sky-200 bg-white px-1.5 py-0.5 text-xs font-bold uppercase tracking-wide text-sky-700">PDF</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">Resumen operativo para consulta rápida del producto</p>

                                    <div class="mt-3">
                                        @if($quickGuide)
                                            <a
                                                href="{{ route('documents.quick-guide.download', $quickGuide) }}"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-sky-300 bg-white px-3.5 py-2 text-sm font-semibold text-sky-700 shadow-sm transition hover:border-sky-400 hover:bg-sky-50/70 focus-ring sm:w-auto"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 3v12"/>
                                                    <path d="m7 10 5 5 5-5"/>
                                                    <path d="M5 21h14"/>
                                                </svg>
                                                Descargar guía rápida
                                            </a>
                                        @else
                                            <p class="text-xs text-slate-400">No disponible por ahora</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tarjeta: Documento de calibracion --}}
                        <div class="group relative overflow-hidden rounded-2xl border transition {{ $calibrationDocument ? 'border-cyan-200 bg-cyan-50/70 hover:border-cyan-300' : 'border-slate-200 bg-slate-50' }}">
                            <div class="flex items-start gap-4 p-5">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $calibrationDocument ? 'bg-cyan-100 text-cyan-700' : 'bg-slate-200 text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <path d="M14 2v6h6"/>
                                        <path d="M9 15.5l1.6 1.6L15 12.7"/>
                                        <path d="M8 10h8"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900">Documento de calibracion</p>
                                        <span class="rounded-md border border-cyan-200 bg-white px-1.5 py-0.5 text-xs font-bold uppercase tracking-wide text-cyan-700">PDF</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">Soporte de calibración y trazabilidad del producto</p>

                                    <div class="mt-3">
                                        @if($calibrationDocument)
                                            <a
                                                href="{{ route('documents.calibration-document.download', $calibrationDocument) }}"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-cyan-300 bg-white px-3.5 py-2 text-sm font-semibold text-cyan-700 shadow-sm transition hover:border-cyan-400 hover:bg-cyan-50/70 focus-ring sm:w-auto"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 3v12"/>
                                                    <path d="m7 10 5 5 5-5"/>
                                                    <path d="M5 21h14"/>
                                                </svg>
                                                Documento de calibracion
                                            </a>
                                        @else
                                            <p class="text-xs text-slate-400">No disponible por ahora</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Documentos secundarios adicionales (catálogos, certificados, manuales) --}}
                    @if($secondaryDocuments->isNotEmpty())
                        <div class="mt-3">
                            <x-ui.table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Archivo</th>
                                        <th class="text-right">Acceso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($secondaryDocuments as $document)
                                        <tr>
                                            <td data-label="Tipo" class="font-medium text-slate-700">
                                                {{ $documentTypeLabels[$document->type] ?? ucfirst($document->type) }}
                                            </td>
                                            <td data-label="Archivo" data-full="true" class="text-slate-500" title="{{ $document->filename }}">
                                                <span class="block truncate">{{ $document->filename }}</span>
                                            </td>
                                            <td data-label="Acceso" class="text-right text-xs text-slate-400">
                                                @if($document->isManual())
                                                    <a
                                                        href="{{ route('documents.manual.download', $document) }}"
                                                        class="inline-flex items-center gap-1 font-semibold text-brand-primary transition hover:underline focus-ring rounded"
                                                    >
                                                        Descargar PDF
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M12 3v12"/>
                                                            <path d="m7 10 5 5 5-5"/>
                                                            <path d="M5 21h14"/>
                                                        </svg>
                                                    </a>
                                                @else
                                                    Solicitar a soporte comercial
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.table>
                        </div>
                    @endif
                @endif
            </div>
        </section>

        <div id="product-panel-alternativas" role="tabpanel" aria-labelledby="product-tab-alternativas" data-tab-panel="alternativas" hidden>
            <x-catalog.product-grid-section
                id="alternativas"
                title="Alternativas similares"
                subtitle="Opciones de sustitución y continuidad operativa"
                :products="$alternativeProducts"
                empty-message="No hay alternativas registradas para este producto."
                list-key="alternatives"
                :pricing-mode="$pricingMode"
                :tier="$distributorTier"
            />
        </div>
        </div>

        {{-- Productos relacionados siempre visibles debajo de las pestañas --}}
        <x-catalog.product-grid-section
            id="relacionados"
            title="Productos relacionados"
            subtitle="De la misma categoría · Ideal para compra de reposición"
            :products="$relatedProducts"
            empty-message="No hay productos relacionados disponibles para esta categoría."
            list-key="related"
            :pricing-mode="$pricingMode"
            :tier="$distributorTier"
        />

    @include('catalog._floating-support-cards')

    {{-- ──────────────────────────────────────────────────────────────
         Barra de compra fija en mobile
    ────────────────────────────────────────────────────────────────── --}}
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] shadow-[0_-4px_16px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden">
        <div class="mx-auto flex max-w-xl flex-wrap items-center gap-2 sm:gap-3">
            <div class="w-full min-w-0 sm:flex-1">
                @if($showDualPricing && $isLockedDiscount)
                    <p class="truncate text-[0.65rem] font-semibold uppercase tracking-wide text-slate-400">Tu precio</p>
                    <p
                        class="truncate text-base font-bold tabular-nums text-slate-950"
                        data-variant-mobile-price-target
                        data-variant-mobile-silver-target
                        data-default-value="{{ $formattedSilver }}"
                    >{{ $formattedSilver }}</p>
                    <p class="truncate text-xs text-amber-800/90">
                        Oro
                        <span class="font-semibold tabular-nums" data-variant-mobile-gold-target data-default-value="{{ $formattedGold }}">{{ $formattedGold }}</span>
                        · {{ $vatLabel }}
                    </p>
                @elseif($showDualPricing)
                    <div>
                        <p class="truncate text-[0.65rem] font-semibold uppercase tracking-wide text-amber-800/80">{{ $tierPriceLabel }}</p>
                        <p
                            class="truncate text-base font-bold tabular-nums text-slate-950"
                            data-variant-mobile-gold-target
                            data-default-value="{{ $formattedGold }}"
                        >{{ $formattedGold }}</p>
                        <p class="truncate text-xs text-slate-500">
                            <span class="line-through text-slate-400" data-variant-mobile-silver-target data-default-value="{{ $formattedSilver }}">{{ $formattedSilver }}</span>
                            · {{ $vatLabel }}
                        </p>
                    </div>
                @else
                    <p
                        class="truncate text-base font-bold tabular-nums text-slate-950"
                        data-variant-mobile-price-target
                        data-default-value="{{ $formattedPrice }}"
                    >{{ $formattedPrice }}</p>
                    <p class="truncate text-xs text-slate-500">por {{ $unitLabelLower }} · {{ $vatLabel }}</p>
                @endif
            </div>

            @if($canBuy)
                <div
                    class="inline-flex shrink-0 items-center rounded-xl border border-slate-300 bg-white"
                    data-qty-control
                    data-min-multiple="{{ $stepValue }}"
                    data-default-stock="{{ $defaultStockLimit ?? '' }}"
                >
                    <button
                        type="button"
                        data-qty-step="-1"
                        class="inline-flex h-10 w-9 items-center justify-center rounded-l-xl text-base font-medium text-slate-700 transition hover:bg-slate-100 focus-ring"
                        aria-label="Disminuir cantidad"
                    >−</button>
                    <input
                        type="number"
                        value="{{ $defaultQty }}"
                        min="{{ $stepValue }}"
                        step="{{ $stepValue }}"
                        @if($defaultStockLimit !== null) max="{{ $defaultStockLimit }}" @endif
                        inputmode="numeric"
                        data-qty-input
                        data-shared-qty
                        class="no-number-spinner w-12 border-0 bg-transparent text-center text-sm font-bold text-slate-900 focus:ring-0"
                        aria-label="Cantidad"
                    >
                    <button
                        type="button"
                        data-qty-step="1"
                        class="inline-flex h-10 w-9 items-center justify-center rounded-r-xl text-base font-medium text-slate-700 transition hover:bg-slate-100 focus-ring"
                        aria-label="Aumentar cantidad"
                    >+</button>
                </div>

                <button
                    type="submit"
                    form="product-purchase-form"
                    class="btn btn-primary h-10 w-full justify-center px-5 text-sm sm:w-auto"
                >
                    Agregar
                </button>
            @else
                <button type="button" data-tab-target="alternativas" class="btn btn-secondary h-10 w-full justify-center px-5 text-sm sm:w-auto">
                    Ver alternativas
                </button>
            @endif
        </div>
    </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ── Galería de imágenes ──────────────────────────────────────
            const galleryThumbs = Array.from(document.querySelectorAll('[data-product-thumb]'));
            const mainImage = document.querySelector('[data-product-main-image]');

            galleryThumbs.forEach((thumb) => {
                thumb.addEventListener('click', () => {
                    if (mainImage) {
                        mainImage.src = thumb.dataset.src || mainImage.src;
                        mainImage.alt = thumb.dataset.alt || mainImage.alt;
                        mainImage.width = Number(thumb.dataset.width || mainImage.width || 0);
                        mainImage.height = Number(thumb.dataset.height || mainImage.height || 0);

                        if (thumb.dataset.srcset) {
                            mainImage.srcset = thumb.dataset.srcset;
                            mainImage.sizes = thumb.dataset.sizes || '';
                        } else {
                            mainImage.removeAttribute('srcset');
                            mainImage.removeAttribute('sizes');
                        }
                    }

                    galleryThumbs.forEach((item) => {
                        item.classList.remove('border-brand-primary', 'shadow-sm');
                        item.classList.add('border-slate-200');
                    });

                    thumb.classList.remove('border-slate-200');
                    thumb.classList.add('border-brand-primary', 'shadow-sm');
                });
            });

            // ── Variantes: precio y stock dinámicos ──────────────────────
            const variantSelect = document.querySelector('[data-variant-select]');
            const priceTargets = Array.from(document.querySelectorAll('[data-variant-price-target]'));
            const mobilePriceTargets = Array.from(document.querySelectorAll('[data-variant-mobile-price-target]'));
            const goldTargets = Array.from(document.querySelectorAll('[data-variant-gold-target], [data-variant-mobile-gold-target]'));
            const silverTargets = Array.from(document.querySelectorAll('[data-variant-silver-target], [data-variant-mobile-silver-target]'));
            const savingsTarget = document.querySelector('[data-variant-savings-target]');
            const stockTarget = document.querySelector('[data-variant-stock-target]');
            const unitLabel = @json($unitLabel);
            const savingsTemplate = savingsTarget?.dataset.savingsTemplate || 'Ahorras :amount';

            const formatMoney = (value) => `$${Number(value).toLocaleString('es-CO', { maximumFractionDigits: 0 })}`;
            const formatStock = (value) => {
                const parsed = Number(value);
                if (!Number.isFinite(parsed)) return 'Stock a confirmar';
                return `${parsed.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ${unitLabel}`;
            };

            const setTargets = (targets, value) => {
                targets.forEach((el) => {
                    el.textContent = value;
                });
            };

            const refreshVariantSummary = () => {
                if (!variantSelect) return;

                const selected = variantSelect.selectedOptions[0];
                const hasSelection = selected && selected.value;

                if (!hasSelection) {
                    priceTargets.forEach((el) => { el.textContent = el.dataset.defaultValue || ''; });
                    mobilePriceTargets.forEach((el) => { el.textContent = el.dataset.defaultValue || ''; });
                    goldTargets.forEach((el) => { el.textContent = el.dataset.defaultValue || ''; });
                    silverTargets.forEach((el) => { el.textContent = el.dataset.defaultValue || ''; });
                    if (savingsTarget) savingsTarget.textContent = savingsTarget.dataset.defaultValue || '';
                    if (stockTarget) stockTarget.textContent = stockTarget?.dataset.defaultValue || '';
                    return;
                }

                const price = Number(selected.dataset.price || 0);
                const gold = Number(selected.dataset.priceGold || price);
                const silver = Number(selected.dataset.priceSilver || price);
                const stock = selected.dataset.stock;
                const stockText = stock === '' || stock === undefined ? 'Stock a confirmar' : formatStock(stock);
                const formattedPrice = formatMoney(price);
                const formattedGold = formatMoney(gold);
                const formattedSilver = formatMoney(silver);
                const savings = Math.max(0, silver - gold);
                const savingsText = savingsTemplate.replace(':amount', formatMoney(savings));

                setTargets(priceTargets, formattedPrice);
                setTargets(mobilePriceTargets, formattedPrice);
                setTargets(goldTargets, formattedGold);
                setTargets(silverTargets, formattedSilver);
                if (savingsTarget) savingsTarget.textContent = savingsText;
                if (stockTarget) stockTarget.textContent = stockText;
            };

            // ── Control de cantidad ──────────────────────────────────────
            const qtyRoots = Array.from(document.querySelectorAll('[data-qty-control]'));
            const sharedInputs = Array.from(document.querySelectorAll('[data-shared-qty]'));

            const parseValue = (rawValue, fallback = 1) => {
                const normalized = String(rawValue ?? '').replace(',', '.');
                const value = Number(normalized);
                return Number.isFinite(value) ? value : fallback;
            };

            const formatQtyVal = (value) => String(Math.max(1, Math.round(value)));

            const normalizeQty = (value, minValue, multiple) => {
                if (!Number.isFinite(value) || value <= 0) return minValue;
                const base = Math.max(minValue, value);
                return Math.ceil(base / multiple) * multiple;
            };

            const parseStockLimit = (value) => {
                if (value === '' || value === null || value === undefined) return null;
                const parsed = Number(value);
                if (!Number.isFinite(parsed)) return null;
                return Math.max(0, Math.floor(parsed));
            };

            const activeStockLimit = () => {
                if (variantSelect) {
                    const selected = variantSelect.selectedOptions[0];
                    if (selected && selected.value) {
                        return parseStockLimit(selected.dataset.stockMax);
                    }
                }

                const primaryRoot = qtyRoots[0];
                return parseStockLimit(primaryRoot?.dataset.defaultStock);
            };

            const syncMaxAttributes = (limit) => {
                sharedInputs.forEach((input) => {
                    if (limit === null) {
                        input.removeAttribute('max');
                        return;
                    }

                    input.max = String(limit);
                });
            };

            const syncQtyInputs = (value) => {
                sharedInputs.forEach((input) => { input.value = formatQtyVal(value); });
            };

            const applyQty = (requestedValue, root) => {
                const input = root.querySelector('[data-qty-input]');
                if (!input || input.disabled) return;
                const multiple = Math.max(1, Math.round(parseValue(root.dataset.minMultiple, 1)));
                const minValue = Math.max(multiple, parseValue(input.min || multiple, multiple));
                const stockLimit = activeStockLimit();
                syncMaxAttributes(stockLimit);

                let normalizedValue = normalizeQty(requestedValue, minValue, multiple);
                if (stockLimit !== null) {
                    normalizedValue = Math.min(normalizedValue, stockLimit);
                }

                syncQtyInputs(normalizedValue);
            };

            variantSelect?.addEventListener('change', () => {
                refreshVariantSummary();

                const primaryRoot = qtyRoots[0];
                if (!primaryRoot) return;

                const primaryInput = primaryRoot.querySelector('[data-qty-input]');
                applyQty(parseValue(primaryInput?.value, parseValue(primaryInput?.min, 1)), primaryRoot);
            });
            refreshVariantSummary();

            qtyRoots.forEach((root) => {
                const input = root.querySelector('[data-qty-input]');
                if (!input || input.disabled) return;

                root.querySelectorAll('[data-qty-step]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const currentValue = parseValue(input.value, parseValue(input.min, 1));
                        const direction = Number(button.dataset.qtyStep || 0);
                        const multiple = Math.max(1, Math.round(parseValue(root.dataset.minMultiple, 1)));
                        applyQty(currentValue + (direction * multiple), root);
                    });
                });

                input.addEventListener('change', () => applyQty(parseValue(input.value, parseValue(input.min, 1)), root));
                input.addEventListener('blur', () => applyQty(parseValue(input.value, parseValue(input.min, 1)), root));
            });

            const primaryQtyInput = document.querySelector('[data-primary-qty]');
            if (primaryQtyInput) {
                const primaryRoot = primaryQtyInput.closest('[data-qty-control]');
                if (primaryRoot) applyQty(parseValue(primaryQtyInput.value, parseValue(primaryQtyInput.min, 1)), primaryRoot);
            }

            // ── Pestañas de información del producto ─────────────────────
            const tabsRoot = document.querySelector('[data-product-tabs]');
            const tabButtons = Array.from(document.querySelectorAll('[data-product-tab]'));
            const tabPanels = Array.from(document.querySelectorAll('[data-tab-panel]'));
            const validTabs = tabPanels.map((panel) => panel.dataset.tabPanel).filter(Boolean);

            const activateTab = (id, { moveFocus = false, scrollToTabs = false } = {}) => {
                if (!validTabs.includes(id)) return;

                tabPanels.forEach((panel) => {
                    panel.hidden = panel.dataset.tabPanel !== id;
                });

                tabButtons.forEach((button) => {
                    const isActive = button.dataset.productTab === id;
                    button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    button.tabIndex = isActive ? 0 : -1;
                    button.classList.toggle('border-brand-primary', isActive);
                    button.classList.toggle('text-brand-primary', isActive);
                    button.classList.toggle('border-transparent', !isActive);
                    button.classList.toggle('text-slate-500', !isActive);
                    if (isActive && moveFocus) button.focus();
                });

                if (window.history?.replaceState) {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.hash = id;
                    window.history.replaceState(null, '', currentUrl);
                }

                if (scrollToTabs && tabsRoot) {
                    tabsRoot.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            };

            document.querySelectorAll('[data-tab-target]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    activateTab(trigger.dataset.tabTarget, {
                        scrollToTabs: !trigger.hasAttribute('data-product-tab'),
                    });
                });
            });

            tabButtons.forEach((button, index) => {
                button.addEventListener('keydown', (event) => {
                    let nextIndex = null;
                    if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabButtons.length;
                    if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabButtons.length) % tabButtons.length;
                    if (event.key === 'Home') nextIndex = 0;
                    if (event.key === 'End') nextIndex = tabButtons.length - 1;
                    if (nextIndex === null) return;

                    event.preventDefault();
                    activateTab(tabButtons[nextIndex].dataset.productTab, { moveFocus: true });
                });
            });

            const initialTab = window.location.hash.replace('#', '');
            activateTab(validTabs.includes(initialTab) ? initialTab : 'descripcion');

            // ── Copiar SKU al portapapeles ────────────────────────────────
            const copySkuBtn = document.querySelector('[data-copy-sku]');
            if (copySkuBtn) {
                const iconCopy = `<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>`;
                const iconCheck = `<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

                copySkuBtn.addEventListener('click', async () => {
                    const value = copySkuBtn.dataset.value || '';
                    try {
                        await navigator.clipboard.writeText(value);
                        copySkuBtn.innerHTML = iconCheck;
                        copySkuBtn.classList.add('border-emerald-300', 'bg-emerald-50');
                        setTimeout(() => {
                            copySkuBtn.innerHTML = iconCopy;
                            copySkuBtn.classList.remove('border-emerald-300', 'bg-emerald-50');
                        }, 1800);
                    } catch {
                        // portapapeles no disponible, falla silenciosa
                    }
                });
            }
        });
    </script>
</x-app-layout>
