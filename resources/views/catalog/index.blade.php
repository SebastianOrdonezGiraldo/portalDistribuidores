@push('head')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="robots" content="{{ $robotsContent }}">
@endpush

<x-app-layout>
    @php
        $resultsTotal = method_exists($products, 'total') ? $products->total() : $products->count();
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Catálogo de Productos" subtitle="Búsqueda rápida con filtros por categoría y carga directa al carrito de pedido.">
        </x-ui.page-header>
    </x-slot>

    <x-ui.filter-bar method="GET" action="{{ route('catalog.index') }}" class="grid gap-4 p-5 md:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-5">
            <label class="form-label" for="catalog-term">Buscar producto</label>
            <x-ui.input id="catalog-term" name="term" :value="$search->term" placeholder="Nombre, categoría o sinónimo" />
        </div>

        <div class="lg:col-span-4">
            <label class="form-label" for="catalog-category">Categoría</label>
            <x-ui.select id="catalog-category" name="category_id">
                <option value="">Todas las categorías</option>
                @foreach($categories as $category)
                    @include('catalog._category-option', ['category' => $category, 'depth' => 0, 'selected' => $search->categoryId])
                @endforeach
            </x-ui.select>
        </div>

        <div class="flex items-end gap-2 md:col-span-2 lg:col-span-3 lg:justify-end">
            <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto">Buscar</x-ui.button>
            <a href="{{ route('catalog.index') }}" class="btn btn-secondary shrink-0">Limpiar</a>
        </div>

        <div class="md:col-span-2 lg:col-span-12 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3 text-sm">
            <x-ui.checkbox name="include_children" value="1" :checked="$search->includeChildren" label="Incluir subcategorías" />
            <p class="text-slate-600">Resultados encontrados: <strong class="text-slate-900">{{ $resultsTotal }}</strong></p>
        </div>
    </x-ui.filter-bar>

    <section class="mt-4">
        @if($products->isEmpty())
            <x-ui.empty-state title="No encontramos productos" description="Prueba con una búsqueda distinta o habilita subcategorías para ampliar resultados.">
                <x-slot name="action">
                    <a href="{{ route('catalog.index') }}" class="btn btn-primary">Ver todo el catálogo</a>
                </x-slot>
            </x-ui.empty-state>
        @else
            <x-catalog.product-grid-section
                id="catalog-results"
                :products="$products"
                list-key="catalog"
                :priority-count="4"
            />
        @endif
    </section>
</x-app-layout>
