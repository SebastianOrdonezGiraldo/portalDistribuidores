@push('head')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="robots" content="{{ $robotsContent }}">
@endpush

<x-app-layout>
    @php
        $resultsTotal = method_exists($products, 'total') ? $products->total() : $products->count();
        $sortOptions = [
            \App\Modules\Shared\ValueObjects\ProductSearchQuery::SORT_RELEVANCE => 'Relevancia',
            \App\Modules\Shared\ValueObjects\ProductSearchQuery::SORT_NAME_ASC => 'Nombre (A-Z)',
            \App\Modules\Shared\ValueObjects\ProductSearchQuery::SORT_NAME_DESC => 'Nombre (Z-A)',
            \App\Modules\Shared\ValueObjects\ProductSearchQuery::SORT_STOCK_DESC => 'Mayor stock',
        ];
        $selectedSort = $search->sort ?? \App\Modules\Shared\ValueObjects\ProductSearchQuery::SORT_RELEVANCE;
        $currentSortLabel = $sortOptions[$selectedSort] ?? $sortOptions[\App\Modules\Shared\ValueObjects\ProductSearchQuery::SORT_RELEVANCE];

        $flattenCategories = static function ($nodes) use (&$flattenCategories) {
            return collect($nodes)->flatMap(function ($category) use (&$flattenCategories) {
                $children = $category->children ?? collect();

                return collect([$category])->concat($flattenCategories($children));
            });
        };

        $flatCategories = $flattenCategories(collect($categories));
        $quickCategories = collect($categories)->take(8)->values();
        $selectedCategory = $search->categoryId !== null
            ? $flatCategories->firstWhere('id', $search->categoryId)
            : null;
        $catalogHomeUrl = auth()->check() ? route('dashboard') : route('catalog.index');
        $hasAdvancedFilters = ! $search->includeChildren || $search->perPage !== 20;
        $categoryBaseQuery = request()->except(['category_id', 'page']);
        $allCategoriesUrl = route(
            'catalog.index',
            array_filter(
                $categoryBaseQuery,
                static fn ($value) => ! is_null($value) && $value !== ''
            )
        );

        $activeFilterChips = collect();

        if (filled($search->term)) {
            $activeFilterChips->push([
                'label' => 'Búsqueda: "'.$search->term.'"',
                'href' => route(
                    'catalog.index',
                    array_filter(
                        request()->except(['term', 'page']),
                        static fn ($value) => ! is_null($value) && $value !== ''
                    )
                ),
            ]);
        }

        if ($selectedCategory) {
            $activeFilterChips->push([
                'label' => 'Categoría: '.$selectedCategory->name,
                'href' => route(
                    'catalog.index',
                    array_filter(
                        request()->except(['category_id', 'page']),
                        static fn ($value) => ! is_null($value) && $value !== ''
                    )
                ),
            ]);
        }

        if (! $search->includeChildren) {
            $activeFilterChips->push([
                'label' => 'Solo categoría exacta',
                'href' => route(
                    'catalog.index',
                    array_filter(
                        request()->except(['include_children', 'page']),
                        static fn ($value) => ! is_null($value) && $value !== ''
                    )
                ),
            ]);
        }

        if ($selectedSort !== \App\Modules\Shared\ValueObjects\ProductSearchQuery::SORT_RELEVANCE) {
            $activeFilterChips->push([
                'label' => 'Orden: '.$currentSortLabel,
                'href' => route(
                    'catalog.index',
                    array_filter(
                        request()->except(['sort', 'page']),
                        static fn ($value) => ! is_null($value) && $value !== ''
                    )
                ),
            ]);
        }

        if ($search->perPage !== 20) {
            $activeFilterChips->push([
                'label' => 'Mostrando '.$search->perPage.' por página',
                'href' => route(
                    'catalog.index',
                    array_filter(
                        request()->except(['per_page', 'page']),
                        static fn ($value) => ! is_null($value) && $value !== ''
                    )
                ),
            ]);
        }
    @endphp

    <x-slot name="catalogToolbar">
        <form
            method="GET"
            action="{{ route('catalog.index') }}"
            class="space-y-3"
            x-data="{ filtersOpen: {{ $hasAdvancedFilters ? 'true' : 'false' }} }"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                    <ol class="flex flex-wrap items-center gap-1">
                        <li>
                            <a href="{{ $catalogHomeUrl }}" class="rounded px-1 py-0.5 hover:bg-slate-100 hover:text-slate-700">Inicio</a>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li class="font-semibold text-slate-700">Catálogo</li>
                    </ol>
                </nav>
                <p class="text-xs text-slate-500">
                    Mostrando
                    <strong class="tabular-nums text-slate-900">{{ number_format($resultsTotal, 0, ',', '.') }}</strong>
                    productos
                </p>
            </div>

            <div class="grid gap-2 lg:grid-cols-[minmax(0,1fr)_15rem_auto]">
                <div class="relative">
                    <label class="sr-only" for="catalog-top-search">Buscar productos</label>
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input
                        id="catalog-top-search"
                        type="text"
                        name="term"
                        value="{{ $search->term }}"
                        placeholder="Buscar producto, SKU, marca o categoría"
                        class="form-input py-2.5 pl-9 pr-4"
                    >
                </div>

                <div>
                    <label class="sr-only" for="catalog-sort">Ordenar por</label>
                    <select id="catalog-sort" name="sort" class="form-select py-2.5" onchange="this.form.submit()">
                        @foreach($sortOptions as $sortKey => $sortLabel)
                            <option value="{{ $sortKey }}" @selected($selectedSort === $sortKey)>{{ $sortLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <button
                    type="button"
                    class="btn btn-secondary justify-center lg:self-end"
                    @click="filtersOpen = !filtersOpen"
                    x-bind:aria-expanded="filtersOpen.toString()"
                    aria-controls="catalog-filters-panel"
                >
                    Filtros
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" x-bind:class="filtersOpen ? 'rotate-180' : ''">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </button>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-1">
                <a
                    href="{{ $allCategoriesUrl }}"
                    class="{{ $search->categoryId === null ? 'border-brand-primary bg-brand-primary/10 text-brand-dark' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-800' }} inline-flex shrink-0 items-center rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                >
                    Todas
                </a>

                @foreach($quickCategories as $category)
                    @php
                        $categoryQuery = array_merge($categoryBaseQuery, ['category_id' => $category->id]);
                        $categoryUrl = route(
                            'catalog.index',
                            array_filter(
                                $categoryQuery,
                                static fn ($value) => ! is_null($value) && $value !== ''
                            )
                        );
                        $isActiveCategory = $search->categoryId === $category->id;
                    @endphp

                    <a
                        href="{{ $categoryUrl }}"
                        class="{{ $isActiveCategory ? 'border-brand-primary bg-brand-primary/10 text-brand-dark' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-800' }} inline-flex shrink-0 items-center rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>

            <div
                id="catalog-filters-panel"
                x-cloak
                x-show="filtersOpen"
                x-transition:enter="transition duration-150 ease-out"
                x-transition:enter-start="translate-y-1 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition duration-120 ease-in"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-1 opacity-0"
                class="rounded-xl border border-slate-200 bg-slate-50/90 p-3 sm:p-4"
            >
                <div class="grid gap-3 lg:grid-cols-3">
                    <div>
                        <label class="form-label" for="catalog-category">Categoria</label>
                        <x-ui.select id="catalog-category" name="category_id">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $category)
                                @include('catalog._category-option', ['category' => $category, 'depth' => 0, 'selected' => $search->categoryId])
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div>
                        <label class="form-label" for="catalog-include-children">Alcance</label>
                        <x-ui.select id="catalog-include-children" name="include_children">
                            <option value="1" @selected($search->includeChildren)>Incluir subcategorías</option>
                            <option value="0" @selected(! $search->includeChildren)>Solo categoría seleccionada</option>
                        </x-ui.select>
                    </div>

                    <div>
                        <label class="form-label" for="catalog-per-page">Productos por página</label>
                        <x-ui.select id="catalog-per-page" name="per_page">
                            <option value="20" @selected($search->perPage === 20)>20 productos</option>
                            <option value="30" @selected($search->perPage === 30)>30 productos</option>
                            <option value="40" @selected($search->perPage === 40)>40 productos</option>
                            <option value="50" @selected($search->perPage === 50)>50 productos</option>
                        </x-ui.select>
                    </div>
                </div>

                <div class="mt-3 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('catalog.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Limpiar</a>
                    <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">Aplicar filtros</x-ui.button>
                </div>
            </div>

            @if($activeFilterChips->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 pt-3">
                    @foreach($activeFilterChips as $chip)
                        <a
                            href="{{ $chip['href'] }}"
                            class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                        >
                            {{ $chip['label'] }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </form>
    </x-slot>

    <section>
        <header class="mb-4">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Catálogo de Productos</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Búsqueda rápida con filtros por categoría y carga directa al carrito de pedido.</p>
        </header>

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
            />
        @endif
    </section>
</x-app-layout>
