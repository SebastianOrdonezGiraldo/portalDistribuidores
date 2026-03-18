@props([
    'id',
    'title',
    'subtitle'    => '',
    'products',
    'emptyMessage' => 'No hay productos disponibles.',
])

<section id="{{ $id }}" class="scroll-mt-32">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-950">{{ $title }}</h2>
            @if($subtitle)
                <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if($products->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($products as $product)
                <x-catalog.product-card :product="$product" />
            @endforeach
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
