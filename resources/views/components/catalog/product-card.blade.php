@props([
    'product',
    'isLcpCandidate' => false,
])

@php
    $activeVariants = $product->activeVariantsCollection();
    $hasVariants = $activeVariants->isNotEmpty();
    $minPrice = $hasVariants ? (float) ($activeVariants->min('price') ?? 0) : (float) $product->price;
    $maxPrice = $hasVariants ? (float) ($activeVariants->max('price') ?? 0) : $minPrice;
    $isRangePrice = $maxPrice > $minPrice;
    $detailUrl = route('products.show', $product);
    $coverPhoto = $product->primaryPhoto
        ?? ($product->relationLoaded('photos') ? $product->photos->first() : null);
    $coverPhotoUrl = $coverPhoto
        ? \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($coverPhoto->path)
        : null;
    // En listado de catalogo evitamos I/O de storage por tarjeta durante SSR.
    // Solo usamos dimensiones persistidas; si no existen, aplicamos fallback seguro.
    $coverPhotoWidth = (int) ($coverPhoto?->photo_width ?? 0);
    $coverPhotoHeight = (int) ($coverPhoto?->photo_height ?? 0);
    $coverPhotoDimensions = ($coverPhotoWidth > 0 && $coverPhotoHeight > 0)
        ? ['width' => $coverPhotoWidth, 'height' => $coverPhotoHeight]
        : ['width' => 1200, 'height' => 1200];
    $coverPhotoSrcset = null;
    $coverPhotoSizes = '(min-width: 1280px) 25vw, (min-width: 1024px) 33vw, 50vw';
    $isLcpImage = (bool) $isLcpCandidate;
    $hasStock = is_numeric($product->stock ?? null) && (float) $product->stock > 0;
    $stockLabel = $hasStock ? 'En stock' : 'Agotado';
    $stockLabelClasses = $hasStock
        ? 'text-emerald-700'
        : 'text-red-700';
    $vatLabel = \App\Modules\Orders\Support\OrderLineVat::label((bool) $product->is_vat_excluded);
@endphp

<article data-product-card class="group relative flex h-full cursor-pointer flex-col overflow-hidden rounded-xl border border-brand-primary/10 bg-white shadow-soft transition hover:-translate-y-0.5 hover:border-brand-primary/40 hover:shadow-panel">
    <div class="relative flex aspect-square items-center justify-center overflow-hidden bg-brand-mist/70 p-2 sm:p-3">
        <div class="absolute left-2.5 top-2.5 z-10">
            <x-ui.badge variant="neutral" class="!rounded-full !px-2 !py-0.5 !text-xs !font-medium !normal-case !tracking-normal">
                {{ $product->category?->name ?? 'Sin categoría' }}
            </x-ui.badge>
        </div>

        @if($coverPhoto)
            <img
                src="{{ $coverPhotoUrl }}"
                alt="{{ $product->name }}"
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
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-50 to-slate-200">
                <div class="rounded-full border border-slate-300 bg-white p-4 shadow-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="m8 13 2.5-2.5 2.5 2.5 3-3 2 2"></path>
                        <circle cx="9" cy="9" r="1.2"></circle>
                    </svg>
                </div>
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-2 sm:p-3">
        {{-- Stretched link: el ::after cubre toda la tarjeta (article es relative) --}}
        <h3 class="min-h-[2.5rem] overflow-hidden text-sm font-semibold leading-tight text-slate-900 [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:2]">
            <a
                href="{{ $detailUrl }}"
                class="focus-ring rounded after:absolute after:inset-0 after:z-0 after:content-['']"
                tabindex="0"
                aria-label="Ver detalle de {{ $product->name }}"
            >{{ $product->name }}</a>
        </h3>

        <dl class="mt-1.5 space-y-1 text-xs text-slate-600">
            <div class="flex gap-1.5">
                <dt class="shrink-0 font-medium text-slate-500">SKU</dt>
                <dd class="min-w-0 truncate font-medium text-slate-800">{{ $product->sku }}</dd>
            </div>
            @if($product->brand)
                <div class="flex gap-1.5">
                    <dt class="shrink-0 font-medium text-slate-500">Marca</dt>
                    <dd class="min-w-0 truncate text-slate-700">{{ $product->brand }}</dd>
                </div>
            @endif
        </dl>

        <div class="relative z-10 mt-2 flex items-end justify-between gap-1.5 sm:mt-2.5 sm:gap-2">
            <div class="min-w-0">
                <p class="text-xs font-semibold sm:text-sm {{ $stockLabelClasses }}">{{ $stockLabel }}</p>
                @if($isRangePrice)
                    <p class="text-sm font-semibold tabular-nums tracking-tight text-slate-950 sm:text-base lg:text-lg">
                        ${{ number_format($minPrice, 0, ',', '.') }} – ${{ number_format($maxPrice, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-500">Precio según variante</p>
                @else
                    <p class="text-sm font-semibold tabular-nums tracking-tight text-slate-950 sm:text-base lg:text-lg">
                        ${{ number_format($minPrice, 0, ',', '.') }}
                    </p>
                @endif
                <p class="text-xs text-slate-500">{{ $vatLabel }}</p>
            </div>

            <div class="relative z-10 flex items-center gap-2">
                @if($hasVariants)
                    <a href="{{ $detailUrl }}" class="relative z-10 inline-flex h-10 items-center justify-center rounded-lg border border-brand-primary bg-brand-primary px-3 text-xs font-semibold text-white transition hover:bg-brand-hover focus-ring">
                        Elegir
                    </a>
                @elseif(! $hasStock)
                    <span class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-slate-100 px-3 text-xs font-semibold text-slate-500">
                        No disponible
                    </span>
                @else
                    <form action="{{ route('cart.store') }}" method="POST" class="cart-qty-form relative z-10">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="unit_label" value="unidad">
                        <input type="hidden" name="qty" value="1" class="cart-qty-value">

                        <div class="flex items-center gap-1">
                            {{-- Control de cantidad: solo visible en PC al hacer hover --}}
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

                            {{-- Botón carrito --}}
                            <button
                                type="submit"
                                class="inline-flex h-10 w-10 min-h-[2.5rem] min-w-[2.5rem] items-center justify-center rounded-lg border border-brand-primary bg-brand-primary text-white transition hover:bg-brand-hover focus-ring"
                                aria-label="Agregar {{ $product->name }} al carrito"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="10" cy="20.5" r="1.25"></circle>
                                    <circle cx="17.5" cy="20.5" r="1.25"></circle>
                                    <path d="M3 3h2l2.3 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 7H7.2"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</article>
