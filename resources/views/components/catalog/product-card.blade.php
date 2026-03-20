@props(['product'])

@php
    $activeVariants = $product->activeVariantsCollection();
    $hasVariants = $activeVariants->isNotEmpty();
    $minPrice = $hasVariants ? (float) ($activeVariants->min('price') ?? 0) : (float) $product->price;
    $maxPrice = $hasVariants ? (float) ($activeVariants->max('price') ?? 0) : $minPrice;
    $isRangePrice = $maxPrice > $minPrice;
    $detailUrl = route('products.show', $product);
    $coverPhoto = $product->primaryPhoto
        ?? ($product->relationLoaded('photos') ? $product->photos->first() : null);
@endphp

<article class="group relative flex h-full cursor-pointer flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-panel">
    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
        <div class="absolute left-3 top-3 z-10">
            <x-ui.badge variant="neutral" class="!rounded-full !px-2 !py-0.5 !text-xs !font-medium !normal-case !tracking-normal">
                {{ $product->category?->name ?? 'Sin categoría' }}
            </x-ui.badge>
        </div>

        @if($coverPhoto)
            <img
                src="{{ \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($coverPhoto->path) }}"
                alt="{{ $product->name }}"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover object-center transition duration-300 group-hover:scale-[1.04]"
            >
        @else
            <div class="flex h-full items-center justify-center bg-gradient-to-br from-slate-50 to-slate-200">
                <div class="rounded-full border border-slate-300 bg-white p-5 shadow-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="m8 13 2.5-2.5 2.5 2.5 3-3 2 2"></path>
                        <circle cx="9" cy="9" r="1.2"></circle>
                    </svg>
                </div>
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-4">
        {{-- Stretched link: el ::after cubre toda la tarjeta (article es relative) --}}
        <h3 class="min-h-[2.75rem] overflow-hidden text-base font-semibold leading-tight text-slate-900 [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:2]">
            <a
                href="{{ $detailUrl }}"
                class="focus-ring rounded after:absolute after:inset-0 after:z-0 after:content-['']"
                tabindex="0"
                aria-label="Ver detalle de {{ $product->name }}"
            >{{ $product->name }}</a>
        </h3>

        <dl class="mt-2 space-y-1 text-xs text-slate-600">
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

        <div class="relative z-10 mt-3 flex items-end justify-between gap-2">
            <div>
                @if($isRangePrice)
                    <p class="text-xl font-semibold tabular-nums tracking-tight text-slate-950 sm:text-2xl">
                        ${{ number_format($minPrice, 0, ',', '.') }} – ${{ number_format($maxPrice, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-500">Precio según variante</p>
                @else
                    <p class="text-xl font-semibold tabular-nums tracking-tight text-slate-950 sm:text-2xl">
                        ${{ number_format($minPrice, 0, ',', '.') }}
                    </p>
                @endif
            </div>

            <div class="relative z-10 flex items-center gap-2">
                @if($hasVariants)
                    <a href="{{ $detailUrl }}" class="relative z-10 inline-flex h-10 items-center justify-center rounded-lg border border-brand-primary bg-brand-primary px-3 text-xs font-semibold text-white transition hover:bg-brand-hover focus-ring">
                        Elegir
                    </a>
                @else
                    <form action="{{ route('cart.store') }}" method="POST" class="relative z-10">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="unit_label" value="unidad">
                        <input type="hidden" name="qty" value="1">
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
                    </form>
                @endif
            </div>
        </div>
    </div>
</article>
