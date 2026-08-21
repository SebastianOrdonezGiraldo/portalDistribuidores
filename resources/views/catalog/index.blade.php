@push('head')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="robots" content="{{ $robotsContent }}">
@endpush

{{--
View contract:
- Source: App\Modules\Catalog\Http\Controllers\CatalogController::__invoke.
- Expects: $products paginator, $categories tree, $search ProductSearchQuery, $canonicalUrl, $robotsContent,
  optional $distributorTier, $showTierExperience, $tierMetrics for authenticated distributors.
- Owns: filter chips, category links, sort controls, and catalog layout.
- Notes: search, ranking, validation, and AJAX payloads stay in CatalogController/SearchEngineInterface.
--}}
@php
    $showTierExperience = (bool) ($showTierExperience ?? false);
    $distributorTier = $distributorTier ?? null;
    $tierMetrics = $tierMetrics ?? null;
    $tierPricingMode = $showTierExperience && $distributorTier
        ? $distributorTier->pricingMode()
        : 'single';
@endphp
<x-app-layout>
    {{-- Presentation state derived from the search query and category tree; no catalog query logic belongs here. --}}
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

                <div class="catalog-header-category">
                    <label class="sr-only" for="catalog-header-category">Categoría</label>
                    <x-ui.select id="catalog-header-category" name="category_id" onchange="this.form.submit()">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $category)
                            @include('catalog._category-option', ['category' => $category, 'depth' => 0, 'selected' => $search->categoryId])
                        @endforeach
                    </x-ui.select>
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
                        <x-ui.select id="catalog-category">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $category)
                                @include('catalog._category-option', ['category' => $category, 'depth' => 0, 'selected' => $search->categoryId])
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div>
                        <label class="form-label" for="catalog-include-children">Alcance</label>
                        <x-ui.select id="catalog-include-children">
                            <option value="1" @selected($search->includeChildren)>Incluir subcategorías</option>
                            <option value="0" @selected(! $search->includeChildren)>Solo categoría seleccionada</option>
                        </x-ui.select>
                    </div>

                    <div>
                        <label class="form-label" for="catalog-per-page">Productos por página</label>
                        <x-ui.select id="catalog-per-page">
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

    <section
        class="catalog-page"
        x-data="{
            filtersOpen: false
        }"
        @catalog-filters.window="filtersOpen = true"
    >
        @if($showTierExperience && $distributorTier && $tierMetrics)
            <div class="catalog-tier-stack">
                <x-tier.welcome-banner
                    :tier="$distributorTier"
                    :user-name="auth()->user()?->name"
                    :missed-savings-amount="$distributorTier->showUpgradeCta() && $tierMetrics->savingsCents > 0
                        ? '$'.number_format((float) $tierMetrics->savingsDecimal(), 0, ',', '.')
                        : null"
                />
                <x-tier.metrics-grid :tier="$distributorTier" :metrics="$tierMetrics" />
            </div>
        @endif

        <div class="catalog-trust-strip" aria-label="Beneficios del portal">
            <div><span class="catalog-trust-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h12v11H3z"/><path d="M15 10h3l3 3v4h-6z"/><circle cx="7" cy="19" r="1.6"/><circle cx="18" cy="19" r="1.6"/><path d="M3 10h12"/></svg></span><span><strong>Compra mayorista</strong><small>Precios para distribuidores</small></span></div>
            <div><span class="catalog-trust-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 12h8M12 8l4 4-4 4"/></svg></span><span><strong>Envíos nacionales</strong><small>Entrega a todo el país</small></span></div>
            <div><span class="catalog-trust-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 15a4 4 0 0 1-4 4h-1l-3 2v-2h-2a4 4 0 0 1-4-4V9a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4z"/><path d="M10 11h.01M15 11h.01"/></svg></span><span><strong>Asesoría especializada</strong><small>Te ayudamos a elegir</small></span></div>
            <div><span class="catalog-trust-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 15h6M9 18h4M9 12h3"/></svg></span><span><strong>Soporte técnico</strong><small>Documentos por producto</small></span></div>
        </div>

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

        <div class="catalog-marketplace-layout">
            <aside class="catalog-sidebar" aria-label="Categorías y filtros">
                <div class="catalog-sidebar-card">
                    <div class="catalog-sidebar-heading">
                        <p>Categorías</p>
                        <span>{{ number_format($flatCategories->count()) }}</span>
                    </div>
                    <nav class="catalog-sidebar-categories" aria-label="Categorías del catálogo">
                        <a href="{{ $allCategoriesUrl }}" class="{{ $search->categoryId === null ? 'is-active' : '' }}">Todas las categorías</a>
                        @foreach($flatCategories->take(12) as $category)
                            <a href="{{ $categoryUrlFor($category->id) }}" class="{{ $search->categoryId === $category->id ? 'is-active' : '' }}">
                                <span>{{ $category->name }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" /></svg>
                            </a>
                        @endforeach
                    </nav>
                </div>

                <form method="GET" action="{{ route('catalog.index') }}" class="catalog-sidebar-card catalog-sidebar-filter-form">
                    @if(filled($search->term))<input type="hidden" name="term" value="{{ $search->term }}">@endif
                    <div class="catalog-sidebar-heading" data-portal-tour-target="filters-desktop">
                        <p>Filtros</p>
                        @if($activeFiltersCount > 0)<span class="catalog-sidebar-filter-count">{{ $activeFiltersCount }}</span>@endif
                    </div>
                    <label class="form-label" for="catalog-sidebar-category">Categoría</label>
                    <x-ui.select id="catalog-sidebar-category" name="category_id">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $category)
                            @include('catalog._category-option', ['category' => $category, 'depth' => 0, 'selected' => $search->categoryId])
                        @endforeach
                    </x-ui.select>
                    <label class="form-label mt-4" for="catalog-sidebar-scope">Alcance</label>
                    <x-ui.select id="catalog-sidebar-scope" name="include_children">
                        <option value="1" @selected($search->includeChildren)>Incluir subcategorías</option>
                        <option value="0" @selected(! $search->includeChildren)>Solo categoría seleccionada</option>
                    </x-ui.select>
                    <label class="form-label mt-4" for="catalog-sidebar-sort">Ordenar por</label>
                    <x-ui.select id="catalog-sidebar-sort" name="sort">
                        @foreach($sortOptions as $sortKey => $sortLabel)
                            <option value="{{ $sortKey }}" @selected($selectedSort === $sortKey)>{{ $sortLabel }}</option>
                        @endforeach
                    </x-ui.select>
                    <label class="form-label mt-4" for="catalog-sidebar-per-page">Productos por página</label>
                    <x-ui.select id="catalog-sidebar-per-page" name="per_page">
                        <option value="20" @selected($search->perPage === 20)>20 productos</option>
                        <option value="30" @selected($search->perPage === 30)>30 productos</option>
                        <option value="40" @selected($search->perPage === 40)>40 productos</option>
                        <option value="50" @selected($search->perPage === 50)>50 productos</option>
                    </x-ui.select>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('catalog.index') }}" class="btn btn-secondary flex-1 justify-center">Limpiar</a>
                        <x-ui.button type="submit" variant="primary" class="flex-1 justify-center">Aplicar</x-ui.button>
                    </div>
                </form>
            </aside>

            <div class="min-w-0">
                <header class="catalog-results-heading">
                    <div>
                        <p class="catalog-results-eyebrow">Catálogo especializado</p>
                        <h2>{{ filled($search->term) ? 'Resultados de búsqueda' : 'Productos para tu operación' }}</h2>
                        <p>{{ number_format($resultsTotal, 0, ',', '.') }} productos disponibles para consulta y pedido.</p>
                    </div>
                </header>

                @if($products->isEmpty())
                    <x-ui.empty-state-panel
                        eyebrow="Búsqueda sin coincidencias"
                        title="No encontramos productos para esta búsqueda"
                        description="Prueba con otro término, cambia la categoría o ajusta los filtros laterales para ampliar los resultados del catálogo."
                    >
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <circle cx="11" cy="11" r="6.5" />
                                <path d="m20 20-3.5-3.5" />
                                <path d="M9 11h4" />
                            </svg>
                        </x-slot>
                        <x-slot name="action">
                            <a href="{{ route('catalog.index') }}" class="btn btn-primary">Ver todo el catálogo</a>
                            @if($activeFiltersCount > 0 || filled($search->term))
                                <a href="{{ route('catalog.index') }}" class="btn btn-secondary">Quitar filtros</a>
                            @endif
                        </x-slot>
                    </x-ui.empty-state-panel>
                @else
                    <x-catalog.product-grid-section
                        id="catalog-results"
                        :products="$products"
                        list-key="catalog"
                        :pricing-mode="$tierPricingMode"
                        :tier="$distributorTier"
                        :show-load-more="false"
                    />
                @endif

            </div>
        </div>

        @include('catalog._floating-support-cards')

        <div x-cloak x-show="filtersOpen" x-transition class="fixed inset-0 z-[70] lg:hidden" aria-label="Filtros del catálogo">
            <div class="absolute inset-0 bg-slate-950/45" @click="filtersOpen = false"></div>
            <form method="GET" action="{{ route('catalog.index') }}" class="absolute inset-x-3 bottom-3 max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-panel" @click.stop>
                @if(filled($search->term))<input type="hidden" name="term" value="{{ $search->term }}">@endif
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div><p class="text-base font-bold text-slate-900">Filtros</p><p class="mt-0.5 text-xs text-slate-500">Ajusta los resultados del catálogo.</p></div>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 focus-ring" @click="filtersOpen = false" aria-label="Cerrar filtros">×</button>
                </div>
                <label class="form-label" for="catalog-mobile-filter-category">Categoría</label>
                <x-ui.select id="catalog-mobile-filter-category" name="category_id">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $category)
                        @include('catalog._category-option', ['category' => $category, 'depth' => 0, 'selected' => $search->categoryId])
                    @endforeach
                </x-ui.select>
                <label class="form-label mt-4" for="catalog-mobile-filter-scope">Alcance</label>
                <x-ui.select id="catalog-mobile-filter-scope" name="include_children">
                    <option value="1" @selected($search->includeChildren)>Incluir subcategorías</option>
                    <option value="0" @selected(! $search->includeChildren)>Solo categoría seleccionada</option>
                </x-ui.select>
                <label class="form-label mt-4" for="catalog-mobile-filter-sort">Ordenar por</label>
                <x-ui.select id="catalog-mobile-filter-sort" name="sort">
                    @foreach($sortOptions as $sortKey => $sortLabel)
                        <option value="{{ $sortKey }}" @selected($selectedSort === $sortKey)>{{ $sortLabel }}</option>
                    @endforeach
                </x-ui.select>
                <label class="form-label mt-4" for="catalog-mobile-filter-per-page">Productos por página</label>
                <x-ui.select id="catalog-mobile-filter-per-page" name="per_page">
                    <option value="20" @selected($search->perPage === 20)>20 productos</option>
                    <option value="30" @selected($search->perPage === 30)>30 productos</option>
                    <option value="40" @selected($search->perPage === 40)>40 productos</option>
                    <option value="50" @selected($search->perPage === 50)>50 productos</option>
                </x-ui.select>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <a href="{{ route('catalog.index') }}" class="btn btn-secondary justify-center">Limpiar</a>
                    <x-ui.button type="submit" variant="primary" class="justify-center">Aplicar</x-ui.button>
                </div>
            </form>
        </div>
    </section>
</x-app-layout>
