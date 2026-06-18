@push('head')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="robots" content="index,follow">
@endpush

<x-app-layout>
    @php
        $defaultQty = $stepValue;
        $defaultStockLimit = is_numeric($stock ?? null) ? max(0, (int) floor((float) $stock)) : null;
        $mainPhotoUrl = $mainPhoto ? \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($mainPhoto->path) : null;
        $mainPhotoDimensions = $mainPhoto?->resolvedDimensions() ?? ['width' => 1200, 'height' => 1200];
        $mainPhotoSrcset = $mainPhoto?->responsiveSrcsetFromKnownVariants();
        $mainPhotoSizes = '(min-width: 1280px) 52vw, (min-width: 1024px) 48vw, 100vw';
        $thumbSizes = '64px';
    @endphp

    {{-- ──────────────────────────────────────────────────────────────
         Header: breadcrumb + acceso rápido al carrito
    ────────────────────────────────────────────────────────────────── --}}
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="flex min-w-0 items-center gap-1.5 text-sm" aria-label="Ruta del producto">
                <a
                    href="{{ route('catalog.index') }}"
                    class="inline-flex shrink-0 items-center gap-1.5 font-medium text-slate-500 transition hover:text-slate-900 focus-ring rounded"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Catálogo
                </a>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
                @if($product->category_id)
                    <a
                        href="{{ route('catalog.index', ['category_id' => $product->category_id]) }}"
                        class="inline-flex shrink-0 items-center rounded text-slate-500 transition hover:text-slate-900 focus-ring"
                    >
                        {{ $categoryName }}
                    </a>
                @else
                    <span class="shrink-0 text-slate-500">{{ $categoryName }}</span>
                @endif
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
                <span class="min-w-0 truncate font-semibold text-slate-900">{{ $product->name }}</span>
            </nav>

            <a href="{{ route('cart.index') }}" class="btn btn-secondary shrink-0 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="10" cy="20.5" r="1.25"/><circle cx="17.5" cy="20.5" r="1.25"/>
                    <path d="M3 3h2l2.3 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 7H7.2"/>
                </svg>
                Ver carrito
            </a>
        </div>
    </x-slot>

    <div class="space-y-5 pb-24 lg:pb-8">

        {{-- ──────────────────────────────────────────────────────────────
             HERO: imagen (izquierda) + info + compra (derecha)
        ────────────────────────────────────────────────────────────────── --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(20rem,24rem)] xl:grid-cols-[minmax(0,1.15fr)_minmax(22rem,26rem)]">

            {{-- Columna imagen --}}
            <div class="relative flex flex-col border-b border-slate-200 bg-slate-50/80 lg:border-b-0 lg:border-r">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_20%,rgba(54,177,187,0.15),transparent_45%),radial-gradient(circle_at_82%_88%,rgba(15,23,42,0.07),transparent_40%)]"></div>

                {{-- Imagen principal --}}
                <div class="group/img relative flex min-h-[18rem] flex-1 items-center justify-center overflow-hidden px-6 py-8 sm:min-h-[24rem] sm:px-10 sm:py-12">
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

                {{-- Galería de miniaturas --}}
                @if($galleryPhotos->count() > 1)
                    <div class="relative border-t border-slate-200 bg-white/80 px-4 py-3 sm:px-5" data-product-gallery>
                        <div class="flex gap-2 overflow-x-auto pb-0.5" style="scrollbar-width: thin;">
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
                                    class="group/thumb h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-white transition focus-ring {{ $loop->first ? 'border-brand-primary shadow-sm' : 'border-slate-200 hover:border-slate-300' }}"
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

            {{-- Columna info + compra (scrollable en desktop si el contenido es largo) --}}
            <div class="flex flex-col divide-y divide-slate-100">

                {{-- Bloque: información del producto --}}
                <div class="p-6 sm:p-7">

                    {{-- Badges de estado --}}
                    <div class="flex flex-wrap items-center gap-2">
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
                    <h1 class="mt-1.5 text-balance text-2xl font-bold leading-snug text-slate-950 sm:text-[1.65rem]">
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
                            <a href="#descripcion" data-scroll-link class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-brand-primary transition hover:underline focus-ring rounded">
                                Ver descripción completa
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </a>
                        </div>
                    @else
                        <p class="mt-4 text-sm leading-relaxed text-slate-500">{{ $availability['helper'] }}</p>
                    @endif
                </div>

                {{-- Bloque: precio y acción de compra --}}
                <div class="bg-slate-50/60 p-6 sm:p-7">

                    {{-- Promo --}}
                    @if($promoLabel)
                        <div class="mb-4 flex items-center gap-2 rounded-xl border border-brand-primary/25 bg-brand-primary/8 px-3.5 py-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                            <p class="text-sm font-semibold text-brand-dark">{{ $promoLabel }}</p>
                        </div>
                    @endif

                    {{-- Precio principal --}}
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Precio</p>
                        <div class="mt-1 flex flex-wrap items-baseline gap-2">
                            <span
                                class="text-4xl font-bold tabular-nums tracking-tight text-slate-950"
                                data-variant-price-target
                                data-default-value="{{ $formattedPrice }}"
                            >{{ $formattedPrice }}</span>
                            <span class="text-sm text-slate-500">
                                / {{ $unitLabelLower }} · {{ $vatLabel }}
                            </span>
                        </div>
                        @if($hasVariants)
                            <p class="mt-1 text-xs text-slate-400">El precio varía según la variante seleccionada</p>
                        @endif
                    </div>

                    {{-- Disponibilidad + stock --}}
                    <div class="mb-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Disponibilidad</p>
                            <x-ui.badge :variant="$availability['badge']" class="!normal-case !tracking-normal">
                                {{ $availability['label'] }}
                            </x-ui.badge>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Stock</p>
                            <p
                                class="text-sm font-semibold text-slate-900"
                                data-variant-stock-target
                                data-default-value="{{ $stockLabel }}"
                            >{{ $stockLabel }}</p>
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
                                            $variantPrice = (float) $variant->price;
                                            $variantStock = $variant->stock;
                                            $variantValue = $variant->attributeValue?->value ?? 'Valor';
                                            $variantStockLabel = is_null($variantStock)
                                                ? 'Stock a confirmar'
                                                : $formatQty($variantStock).' '.$unitLabelLower;
                                        @endphp
                                        <option
                                            value="{{ $variant->id }}"
                                            data-price="{{ $variantPrice }}"
                                            data-stock="{{ $variantStock ?? '' }}"
                                            data-stock-max="{{ is_null($variantStock) ? '' : max(0, (int) floor((float) $variantStock)) }}"
                                            @selected((string) old('variant_id') === (string) $variant->id)
                                        >
                                            {{ $variantValue }} — ${{ number_format($variantPrice, 0, ',', '.') }} · {{ $variantStockLabel }}
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
                                        <a href="#alternativas" data-scroll-link class="underline hover:no-underline">Consulta las alternativas</a>.
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

        {{-- ──────────────────────────────────────────────────────────────
             Navegación de secciones (sticky, estilo underline)
        ────────────────────────────────────────────────────────────────── --}}
        <nav
            class="-mx-4 overflow-x-auto border-y border-slate-200 bg-white/95 backdrop-blur md:sticky md:top-[4.5rem] md:z-20 sm:-mx-6 lg:-mx-8"
            aria-label="Secciones del producto"
        >
            <div class="flex min-w-max items-center px-4 sm:px-6 lg:px-8" data-section-nav>
                @foreach($sections as $section)
                    <a
                        href="#{{ $section['id'] }}"
                        data-scroll-link
                        data-nav-link="{{ $section['id'] }}"
                        class="inline-flex items-center border-b-2 border-transparent px-4 py-3.5 text-sm font-semibold text-slate-500 transition hover:border-slate-300 hover:text-slate-900 focus-ring rounded-t"
                    >
                        {{ $section['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>

        {{-- ──────────────────────────────────────────────────────────────
             Sección: Descripción
        ────────────────────────────────────────────────────────────────── --}}
        <section id="descripcion" class="scroll-mt-32 card overflow-hidden">
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
        <section id="especificaciones" class="scroll-mt-32 card overflow-hidden">
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
             también se debe actualizar el controlador de documentos.
        ────────────────────────────────────────────────────────────────── --}}
        <section id="documentos" class="scroll-mt-32 card overflow-hidden">
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
                    $hasAnyDocument = $techSheet || $manual || $productVideo || $secondaryDocuments->isNotEmpty();
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
                    {{-- Grid principal: Ficha técnica + Manual de usuario + Video de apoyo --}}
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

        {{-- ──────────────────────────────────────────────────────────────
             Sección: Productos relacionados
        ────────────────────────────────────────────────────────────────── --}}
        <x-catalog.product-grid-section
            id="relacionados"
            title="Productos relacionados"
            subtitle="De la misma categoría · Ideal para compra de reposición"
            :products="$relatedProducts"
            empty-message="No hay productos relacionados disponibles para esta categoría."
            list-key="related"
        />

        <x-catalog.product-grid-section
            id="alternativas"
            title="Alternativas similares"
            subtitle="Opciones de sustitución y continuidad operativa"
            :products="$alternativeProducts"
            empty-message="No hay alternativas registradas para este producto."
            list-key="alternatives"
        />

    </div>

    {{-- ──────────────────────────────────────────────────────────────
         Barra de compra fija en mobile
    ────────────────────────────────────────────────────────────────── --}}
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] shadow-[0_-4px_16px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden">
        <div class="mx-auto flex max-w-xl flex-wrap items-center gap-2 sm:gap-3">
            <div class="w-full min-w-0 sm:flex-1">
                <p
                    class="truncate text-base font-bold tabular-nums text-slate-950"
                    data-variant-mobile-price-target
                    data-default-value="{{ $formattedPrice }}"
                >{{ $formattedPrice }}</p>
                <p class="truncate text-xs text-slate-500">por {{ $unitLabelLower }} · {{ $vatLabel }}</p>
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
                <a href="#alternativas" data-scroll-link class="btn btn-secondary h-10 w-full justify-center px-5 text-sm sm:w-auto">
                    Ver alternativas
                </a>
            @endif
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
            const priceTarget = document.querySelector('[data-variant-price-target]');
            const mobilePriceTarget = document.querySelector('[data-variant-mobile-price-target]');
            const stockTarget = document.querySelector('[data-variant-stock-target]');
            const unitLabel = @json($unitLabel);

            const formatMoney = (value) => `$${Number(value).toLocaleString('es-CO', { maximumFractionDigits: 0 })}`;
            const formatStock = (value) => {
                const parsed = Number(value);
                if (!Number.isFinite(parsed)) return 'Stock a confirmar';
                return `${parsed.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ${unitLabel}`;
            };

            const refreshVariantSummary = () => {
                if (!variantSelect) return;

                const selected = variantSelect.selectedOptions[0];
                const hasSelection = selected && selected.value;

                if (!hasSelection) {
                    if (priceTarget) priceTarget.textContent = priceTarget?.dataset.defaultValue || '';
                    if (mobilePriceTarget) mobilePriceTarget.textContent = mobilePriceTarget?.dataset.defaultValue || '';
                    if (stockTarget) stockTarget.textContent = stockTarget?.dataset.defaultValue || '';
                    return;
                }

                const price = Number(selected.dataset.price || 0);
                const stock = selected.dataset.stock;
                const stockText = stock === '' || stock === undefined ? 'Stock a confirmar' : formatStock(stock);
                const formattedPrice = formatMoney(price);

                if (priceTarget) priceTarget.textContent = formattedPrice;
                if (mobilePriceTarget) mobilePriceTarget.textContent = formattedPrice;
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

            // ── Scroll suave a secciones ─────────────────────────────────
            document.querySelectorAll('[data-scroll-link]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    const href = link.getAttribute('href');
                    if (!href || !href.startsWith('#')) return;
                    const target = document.querySelector(href);
                    if (!target) return;
                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            // ── Navegación activa por IntersectionObserver ────────────────
            const navLinks = Array.from(document.querySelectorAll('[data-nav-link]'));
            const sectionIds = navLinks.map((link) => link.dataset.navLink).filter(Boolean);

            const setActiveNav = (id) => {
                navLinks.forEach((link) => {
                    const isActive = link.dataset.navLink === id;
                    link.classList.toggle('border-brand-primary', isActive);
                    link.classList.toggle('text-brand-primary', isActive);
                    link.classList.toggle('border-transparent', !isActive);
                    link.classList.toggle('text-slate-500', !isActive);
                    link.classList.toggle('text-slate-900', isActive);
                });
            };

            if (sectionIds.length > 0 && 'IntersectionObserver' in window) {
                const observer = new IntersectionObserver(
                    (entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) setActiveNav(entry.target.id);
                        });
                    },
                    { rootMargin: '-20% 0px -70% 0px', threshold: 0 }
                );
                sectionIds.forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) observer.observe(el);
                });
            }

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
