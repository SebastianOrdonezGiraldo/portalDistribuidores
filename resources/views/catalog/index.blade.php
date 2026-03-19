<x-app-layout>
    @php
        $resultsTotal = method_exists($products, 'total') ? $products->total() : $products->count();
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Catálogo de Productos" subtitle="Búsqueda rápida con filtros por categoría y carga directa al carrito de pedido.">
        </x-ui.page-header>
    </x-slot>

    <x-ui.filter-bar method="GET" action="{{ route('catalog.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
        <div class="xl:col-span-2">
            <label class="form-label" for="catalog-term">Buscar producto</label>
            <x-ui.input id="catalog-term" name="term" :value="$search->term" placeholder="Nombre, categoría o sinónimo" />
        </div>

        <div class="xl:col-span-2">
            <label class="form-label" for="catalog-category">Categoría</label>
            <x-ui.select id="catalog-category" name="category_id">
                <option value="">Todas las categorías</option>
                @foreach($categories as $category)
                    @include('catalog._category-option', ['category' => $category, 'depth' => 0, 'selected' => $search->categoryId])
                @endforeach
            </x-ui.select>
        </div>

        <div class="flex items-end gap-2">
            <x-ui.button type="submit" variant="primary" class="w-full">Buscar</x-ui.button>
            <a href="{{ route('catalog.index') }}" class="btn btn-secondary">Limpiar</a>
        </div>

        <div class="xl:col-span-5 flex items-center justify-between gap-3 border-t border-slate-200 pt-3 text-sm">
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
            <div
                data-infinite-grid
                data-load-url="{{ route('catalog.index') }}"
                data-next-page="{{ $products->currentPage() + 1 }}"
                data-has-more="{{ $products->hasMorePages() ? 'true' : 'false' }}"
                data-filters="{{ http_build_query(request()->except('page')) }}"
                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach($products as $product)
                    <x-catalog.product-card :product="$product" />
                @endforeach
            </div>

            <div data-infinite-sentinel class="mt-6 flex justify-center py-4">
                <svg class="h-6 w-6 animate-spin text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.568 3 7.212l3-2.921z"></path>
                </svg>
            </div>

            <p data-infinite-end class="mt-6 hidden text-center text-sm text-slate-400">
                Has visto todos los productos
            </p>
        @endif
    </section>
</x-app-layout>
