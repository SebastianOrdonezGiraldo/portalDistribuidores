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
        $categoryUrlFor = static function (int $categoryId) use ($categoryBaseQuery): string {
            $query = array_merge($categoryBaseQuery, ['category_id' => $categoryId]);

            return route(
                'catalog.index',
                array_filter(
                    $query,
                    static fn ($value) => ! is_null($value) && $value !== ''
                )
            );
        };

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

        $activeFiltersCount = $activeFilterChips->count();
    @endphp

    <x-slot name="catalogToolbar">
        <form
            method="GET"
            action="{{ route('catalog.index') }}"
            class="catalog-toolbar"
            x-data="{ filtersOpen: window.matchMedia('(min-width: 768px)').matches ? {{ $hasAdvancedFilters ? 'true' : 'false' }} : false }"
        >
            <div class="catalog-toolbar-meta">
                <nav aria-label="Breadcrumb" class="catalog-toolbar-breadcrumb">
                    <ol class="flex flex-wrap items-center gap-1">
                        <li>
                            <a href="{{ $catalogHomeUrl }}" class="catalog-toolbar-breadcrumb-link">Inicio</a>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li class="catalog-toolbar-breadcrumb-current">Catálogo</li>
                    </ol>
                </nav>

                <p class="catalog-toolbar-results">
                    <span class="tabular-nums">{{ number_format($resultsTotal, 0, ',', '.') }}</span>
                    resultados
                </p>
            </div>

            <div class="catalog-toolbar-controls">
                <div class="catalog-search-field">
                    <label class="sr-only" for="catalog-top-search">Buscar productos</label>
                    <svg xmlns="http://www.w3.org/2000/svg" class="catalog-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input
                        id="catalog-top-search"
                        type="text"
                        name="term"
                        value="{{ $search->term }}"
                        placeholder="Buscar producto, SKU, marca o categoría"
                        class="catalog-search-input"
                    >
                </div>

                <input type="hidden" name="sort" value="{{ $selectedSort }}" data-catalog-sort-hidden>

                <div class="catalog-mobile-actions">
                    <div class="catalog-mobile-sort">
                        <label class="sr-only" for="catalog-sort-mobile">Ordenar por</label>
                        <select
                            id="catalog-sort-mobile"
                            class="catalog-sort-select catalog-sort-select-mobile"
                            onchange="this.form.querySelector('[data-catalog-sort-hidden]').value = this.value; this.form.submit()"
                        >
                            @foreach($sortOptions as $sortKey => $sortLabel)
                                <option value="{{ $sortKey }}" @selected($selectedSort === $sortKey)>{{ $sortLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button
                        type="button"
                        class="btn btn-secondary catalog-mobile-filter-toggle"
                        @click="filtersOpen = !filtersOpen"
                        x-bind:aria-expanded="filtersOpen.toString()"
                        aria-controls="catalog-filters-panel"
                    >
                        Filtros
                        @if($activeFiltersCount > 0)
                            <span class="catalog-filter-count">{{ $activeFiltersCount }}</span>
                        @endif
                    </button>

                    <p class="catalog-mobile-results">
                        <span class="tabular-nums">{{ number_format($resultsTotal, 0, ',', '.') }}</span>
                    </p>
                </div>

                <div class="catalog-sort-field">
                    <label class="sr-only" for="catalog-sort-desktop">Ordenar por</label>
                    <select
                        id="catalog-sort-desktop"
                        class="catalog-sort-select"
                        onchange="this.form.querySelector('[data-catalog-sort-hidden]').value = this.value; this.form.submit()"
                    >
                        @foreach($sortOptions as $sortKey => $sortLabel)
                            <option value="{{ $sortKey }}" @selected($selectedSort === $sortKey)>{{ $sortLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <button
                    type="button"
                    class="btn btn-secondary catalog-filter-toggle"
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

            <div class="catalog-quick-categories">
                <a
                    href="{{ $allCategoriesUrl }}"
                    class="catalog-category-chip {{ $search->categoryId === null ? 'is-active' : '' }}"
                >
                    Todas
                </a>

                @foreach($quickCategories as $category)
                    @php
                        $categoryUrl = $categoryUrlFor($category->id);
                        $isActiveCategory = $search->categoryId === $category->id;
                    @endphp

                    <a
                        href="{{ $categoryUrl }}"
                        class="catalog-category-chip {{ $isActiveCategory ? 'is-active' : '' }}"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>

            <div
                x-cloak
                x-show="filtersOpen"
                x-transition:enter="transition duration-150 ease-out"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-120 ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="catalog-filters-overlay"
                @click="filtersOpen = false"
            ></div>

            <div
                id="catalog-filters-panel"
                x-cloak
                x-show="filtersOpen"
                x-transition:enter="transition duration-150 ease-out"
                x-transition:enter-start="translate-y-2 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition duration-120 ease-in"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-2 opacity-0"
                class="catalog-filters-panel"
            >
                <div class="catalog-filters-head">
                    <div>
                        <p class="catalog-filters-title">Filtros de catálogo</p>
                        <p class="catalog-filters-subtitle">Ajusta categoría, alcance y cantidad de resultados.</p>
                    </div>

                    <button type="button" class="catalog-filters-close" @click="filtersOpen = false" aria-label="Cerrar filtros">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="catalog-filters-grid">
                    <div>
                        <label class="form-label" for="catalog-category">Categoría</label>
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

                <div class="catalog-filters-actions">
                    <a href="{{ route('catalog.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Limpiar</a>
                    <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">Aplicar filtros</x-ui.button>
                </div>
            </div>

            @if($activeFilterChips->isNotEmpty())
                <div class="catalog-active-filters">
                    @foreach($activeFilterChips as $chip)
                        <a
                            href="{{ $chip['href'] }}"
                            class="catalog-active-chip"
                        >
                            {{ $chip['label'] }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </a>
                    @endforeach
                </div>

                <button
                    type="button"
                    class="catalog-active-summary"
                    @click="filtersOpen = true"
                >
                    Filtros activos: {{ $activeFiltersCount }}
                </button>
            @endif
        </form>
    </x-slot>

    <section>
        <div class="catalog-mobile-categories">
            <a
                href="{{ $allCategoriesUrl }}"
                class="catalog-category-chip {{ $search->categoryId === null ? 'is-active' : '' }}"
            >
                Todas
            </a>

            @foreach($quickCategories as $category)
                @php
                    $categoryUrl = $categoryUrlFor($category->id);
                    $isActiveCategory = $search->categoryId === $category->id;
                @endphp

                <a
                    href="{{ $categoryUrl }}"
                    class="catalog-category-chip {{ $isActiveCategory ? 'is-active' : '' }}"
                >
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

        <header class="catalog-section-intro">
            <h1 class="catalog-section-intro-title">Catálogo de Productos</h1>
            <p class="catalog-section-intro-subtitle">Búsqueda rápida con filtros por categoría y carga directa al carrito de pedido.</p>
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
