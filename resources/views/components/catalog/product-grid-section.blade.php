@props([
    'id',
    'title' => null,
    'subtitle' => '',
    'products',
    'emptyMessage' => 'No hay productos disponibles.',
    'listKey' => null,
])

{{--
Component contract:
- Props: id, optional title/subtitle, products paginator, emptyMessage, and optional listKey.
- Slots: none.
- Use for: product lists that support AJAX pagination/load-more through data-product-list attributes.
--}}
@php
    $pageName = method_exists($products, 'getPageName') ? $products->getPageName() : 'page';
    $listKey = $listKey ?? $id;
    $baseQuery = request()->except([$pageName, 'list']);
    $baseQuery = array_filter(
        $baseQuery,
        static fn ($value) => ! is_null($value) && $value !== ''
    );
@endphp

<section id="{{ $id }}" class="scroll-mt-32">
    @if($title || $subtitle)
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                @if($title)
                    <h2 class="text-lg font-bold text-slate-950">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    @endif

    @if($products->count() > 0)
        <div
            data-product-list
            data-list="{{ $listKey }}"
            data-load-url="{{ request()->url() }}"
            data-page-param="{{ $pageName }}"
            data-current-page="{{ $products->currentPage() }}"
            data-next-page="{{ $products->currentPage() + 1 }}"
            data-has-more="{{ $products->hasMorePages() ? 'true' : 'false' }}"
            data-base-query="{{ http_build_query($baseQuery) }}"
        >
            <div data-product-grid class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @include('catalog._products-partial', ['products' => $products, 'listKey' => $listKey])
            </div>

            <div data-product-list-controls class="mt-6 space-y-4">
                @include('catalog._product-list-controls', ['products' => $products])
            </div>
        </div>
    @else
        <div class="flex items-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
            <p class="text-sm text-slate-500">{{ $emptyMessage }}</p>
        </div>
    @endif
</section>
