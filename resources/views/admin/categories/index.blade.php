<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Categorías" subtitle="Estructura jerárquica del catálogo con control operativo y taxonomía de búsqueda.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Resultados: {{ number_format($metrics['filtered_total']) }}</span>
                    <span class="stat-pill">Activas: {{ number_format($metrics['active_categories']) }}</span>
                    <span class="stat-pill">Con productos: {{ number_format($metrics['with_products']) }}</span>
                    <span class="stat-pill">Sinónimos: {{ number_format($metrics['synonyms']) }}</span>
                    <span class="stat-pill">Filtros activos: {{ $activeFiltersCount }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Nueva categoría</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card label="Categorías Totales" :value="number_format($metrics['total_categories'])" hint="Nodos registrados en taxonomía" />
        <x-ui.kpi-card label="Activas" :value="number_format($metrics['active_categories'])" hint="Visibles en flujo comercial" />
        <x-ui.kpi-card label="Con Productos" :value="number_format($metrics['with_products'])" hint="Categorías con catálogo asignado" />
        <x-ui.kpi-card label="Sinónimos" :value="number_format($metrics['synonyms'])" hint="Términos de búsqueda asociados" />
    </section>

    <x-ui.filter-bar method="GET" action="{{ route('admin.categories.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
        <div class="xl:col-span-2">
            <label class="form-label" for="categories-q">Buscar</label>
            <x-ui.input id="categories-q" name="q" :value="$filters['q']" placeholder="Nombre, slug o sinónimo" />
        </div>

        <div>
            <label class="form-label" for="categories-status">Estado</label>
            <x-ui.select id="categories-status" name="status">
                <option value="">Todos</option>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status === 'active' ? 'Activas' : 'Inactivas' }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="categories-with-products">Productos asociados</label>
            <x-ui.select id="categories-with-products" name="with_products">
                <option value="">Todos</option>
                @foreach($withProductsOptions as $option)
                    <option value="{{ $option }}" @selected($filters['with_products'] === $option)>{{ $option === 'yes' ? 'Con productos' : 'Sin productos' }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="categories-sort">Orden</label>
            <x-ui.select id="categories-sort" name="sort">
                @foreach($sortOptions as $option)
                    <option value="{{ $option }}" @selected($filters['sort'] === $option)>
                        @if($option === 'tree') Jerárquico
                        @elseif($option === 'name_asc') Nombre A-Z
                        @elseif($option === 'name_desc') Nombre Z-A
                        @elseif($option === 'updated_desc') Actualización reciente
                        @else Actualización antigua
                        @endif
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="xl:col-span-5 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
            <p class="text-xs text-slate-500">Modo jerárquico con subcategorías anidadas y acciones rápidas.</p>
            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="primary">Aplicar</x-ui.button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Limpiar</a>
            </div>
        </div>
    </x-ui.filter-bar>

    <section class="mt-4">
        @if($categories->isEmpty())
            <x-ui.empty-state title="No hay categorías para este filtro" description="Ajusta filtros o crea una categoría nueva para continuar.">
                <x-slot name="action">
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Crear categoría</a>
                </x-slot>
            </x-ui.empty-state>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Estado</th>
                        <th>Productos</th>
                        <th>Sinónimos</th>
                        <th>Orden</th>
                        <th>Actualización</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        @include('admin.categories._node', ['category' => $category, 'depth' => 0])
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </section>
</x-app-layout>
