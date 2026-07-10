{{--
View contract:
- Source: App\Modules\Admin\Http\Controllers\ProductAdminController::index.
- Expects: $products paginator, $filters, $indexContextQuery, option lists, $categories, $metrics, $activeFiltersCount.
- Owns: admin product listing, import summary display, filters, KPIs, and row actions.
- Notes: filtering, import, duplication, media persistence, and authorization stay in ProductAdminController/actions/policies.
--}}
@php
    $contapymeSyncStatus = $contapymeSyncStatus ?? null;
    $contapymeSyncState = $contapymeSyncStatus['state'] ?? null;
    $contapymeSyncIsRunning = in_array($contapymeSyncState, ['queued', 'running'], true);
    $contapymeSyncVariant = match ($contapymeSyncState) {
        'completed' => 'success',
        'failed', 'blocked' => 'danger',
        default => 'info',
    };
    $contapymeSyncTitle = match ($contapymeSyncState) {
        'queued' => 'Sincronización en cola',
        'running' => 'Sincronización en curso',
        'completed' => 'Última sincronización completada',
        'failed' => 'Última sincronización falló',
        'blocked' => 'Sincronización bloqueada',
        default => 'Sincronización ContaPyme',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Catálogo de Productos" subtitle="Gestión de portafolio con filtros rápidos, disponibilidad y acceso a edición.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Resultados: {{ number_format($metrics['total_products']) }}</span>
                    <span class="stat-pill">Activos: {{ number_format($metrics['active_products']) }}</span>
                    <span class="stat-pill">Inactivos: {{ number_format($metrics['inactive_products']) }}</span>
                    <span class="stat-pill">Sin stock: {{ number_format($metrics['without_stock']) }}</span>
                    <span class="stat-pill">Filtros activos: {{ $activeFiltersCount }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <div class="flex flex-wrap items-center gap-2">
                    <form action="{{ route('admin.contapyme.sync', $indexContextQuery) }}" method="POST"
                          onsubmit="var btn=this.querySelector('button'); if (btn) { btn.disabled=true; btn.textContent='Encolando...' }">
                        @csrf
                        <button type="submit" class="btn btn-secondary w-full justify-center sm:w-auto" @disabled($contapymeSyncIsRunning)>
                            {{ $contapymeSyncIsRunning ? 'Sincronización en curso' : 'Sincronizar stock ContaPyme' }}
                        </button>
                    </form>
                    <a href="{{ route('admin.products.inventory.pdf', collect($indexContextQuery)->except('page')->all()) }}" class="btn btn-secondary w-full justify-center sm:w-auto">Descargar saldos PDF</a>
                    <a href="{{ route('admin.products.import.template') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Descargar plantilla CSV</a>
                    <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                        @csrf
                        <input type="hidden" name="default_action" value="upsert">
                        <input
                            type="file"
                            name="file"
                            accept=".csv,text/csv,application/vnd.ms-excel"
                            required
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/30 sm:w-56"
                        >
                        <button type="submit" class="btn btn-secondary w-full justify-center sm:w-auto">Importar CSV</button>
                    </form>
                    <a href="{{ route('admin.products.create', $indexContextQuery) }}" class="btn btn-primary w-full justify-center sm:w-auto">Nuevo producto</a>
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sync completado" class="mb-4">
            <p>{{ session('success') }}</p>
        </x-ui.alert>
    @elseif(session('error'))
        <x-ui.alert variant="danger" title="Sync falló" class="mb-4">
            <p>{{ session('error') }}</p>
        </x-ui.alert>
    @endif

    @if($contapymeSyncStatus)
        <x-ui.alert :variant="$contapymeSyncVariant" :title="$contapymeSyncTitle" class="mb-4">
            <p>{{ $contapymeSyncStatus['message'] }}</p>
            @if($contapymeSyncStatus['summary'])
                <p class="mt-1 text-xs font-semibold">{{ $contapymeSyncStatus['summary'] }}</p>
            @endif
        </x-ui.alert>
    @endif

    @if(session('importReport'))
        {{-- Import report is flashed by the CSV import action; this view only summarizes the result. --}}
        @php
            $importReport = session('importReport');
        @endphp
        <x-ui.card class="mb-4 border border-slate-200 bg-slate-50/70 p-4">
            <p class="text-sm font-semibold text-slate-900">
                Resumen de importación:
                {{ (int) ($importReport['created'] ?? 0) }} creados,
                {{ (int) ($importReport['updated'] ?? 0) }} actualizados,
                {{ (int) ($importReport['skipped'] ?? 0) }} omitidos,
                {{ count($importReport['errors'] ?? []) }} errores.
            </p>

            @if(!empty($importReport['errors']))
                <div class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Primeros errores detectados</p>
                    <ul class="mt-2 space-y-1 text-xs text-red-700">
                        @foreach(array_slice($importReport['errors'], 0, 8) as $error)
                            <li>
                                Fila {{ $error['row'] ?? '-' }}{{ !empty($error['sku']) ? ' (SKU '.$error['sku'].')' : '' }}:
                                {{ $error['message'] ?? 'Error de validación' }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-ui.card>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-ui.kpi-card
            label="Productos Filtrados"
            :value="number_format($metrics['total_products'])"
            hint="Resultado del listado actual"
        />
        <x-ui.kpi-card
            label="Activos"
            :value="number_format($metrics['active_products'])"
            hint="Visibles para distribuidores"
        />
        <x-ui.kpi-card
            label="Con Foto"
            :value="number_format($metrics['with_photo'])"
            hint="Productos con imagen principal"
        />
        <x-ui.kpi-card
            label="Inactivos"
            :value="number_format($metrics['inactive_products'])"
            hint="No visibles en catálogo"
        />
        <x-ui.kpi-card
            label="Sin Stock"
            :value="number_format($metrics['without_stock'])"
            hint="Stock 0, negativo o no definido"
        />
    </section>

    {{-- Status shortcut URLs and stock formatting are presentation-only helpers for the current listing. --}}
    @php
        $baseStatusQuery = collect($indexContextQuery)->except(['page', 'status'])->all();
        $formatStock = static function ($value): string {
            if (! is_numeric($value)) {
                return 'Sin definir';
            }

            $numeric = (float) $value;

            if (abs($numeric - round($numeric)) < 0.00001) {
                return number_format((float) round($numeric), 0, ',', '.');
            }

            return number_format($numeric, 2, ',', '.');
        };
    @endphp

    <x-ui.card class="mt-4 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.products.index', $baseStatusQuery) }}"
               class="btn {{ empty($filters['status']) ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Todos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['total_products']) }}</span>
            </a>
            <a href="{{ route('admin.products.index', array_merge($baseStatusQuery, ['status' => 'active'])) }}"
               class="btn {{ $filters['status'] === 'active' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Activos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['active_products']) }}</span>
            </a>
            <a href="{{ route('admin.products.index', array_merge($baseStatusQuery, ['status' => 'inactive'])) }}"
               class="btn {{ $filters['status'] === 'inactive' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Inactivos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['inactive_products']) }}</span>
            </a>
            <a href="{{ route('admin.products.index', array_merge($baseStatusQuery, ['media' => 'without_photo'])) }}"
               class="btn {{ $filters['media'] === 'without_photo' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Sin foto
            </a>
            <a href="{{ route('admin.products.index', array_merge($baseStatusQuery, ['stock' => 'no_stock'])) }}"
               class="btn {{ $filters['stock'] === 'no_stock' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Sin stock
            </a>
        </div>
    </x-ui.card>

    <x-ui.filter-bar id="products-filter-form" method="GET" action="{{ route('admin.products.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-8">
        <div class="xl:col-span-2">
            <label class="form-label" for="products-q">Buscar</label>
            <x-ui.input id="products-q" name="q" :value="$filters['q']" placeholder="Producto, marca, SKU o descripción" />
        </div>

        <div>
            <label class="form-label" for="products-category">Categoría</label>
            <x-ui.select id="products-category" name="category_id">
                <option value="">Todas</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="products-status">Disponibilidad</label>
            <x-ui.select id="products-status" name="status">
                <option value="">Todos</option>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status === 'active' ? 'Disponible' : 'Inactivo' }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="products-media">Media</label>
            <x-ui.select id="products-media" name="media">
                <option value="">Todos</option>
                @foreach($mediaOptions as $mediaOption)
                    <option value="{{ $mediaOption }}" @selected($filters['media'] === $mediaOption)>
                        @if($mediaOption === 'with_photo') Con foto
                        @elseif($mediaOption === 'without_photo') Sin foto
                        @elseif($mediaOption === 'with_sheet') Con ficha técnica
                        @else Con video
                        @endif
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="products-stock">Stock</label>
            <x-ui.select id="products-stock" name="stock">
                <option value="">Todos</option>
                @foreach($stockOptions as $stockOption)
                    <option value="{{ $stockOption }}" @selected($filters['stock'] === $stockOption)>
                        @if($stockOption === 'in_stock') Con stock
                        @elseif($stockOption === 'no_stock') Sin stock
                        @else Sin definir
                        @endif
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="products-sort">Orden</label>
            <x-ui.select id="products-sort" name="sort">
                <option value="newest" @selected($filters['sort'] === 'newest')>Más recientes</option>
                <option value="oldest" @selected($filters['sort'] === 'oldest')>Más antiguos</option>
                <option value="name_asc" @selected($filters['sort'] === 'name_asc')>Nombre A-Z</option>
                <option value="name_desc" @selected($filters['sort'] === 'name_desc')>Nombre Z-A</option>
                <option value="price_desc" @selected($filters['sort'] === 'price_desc')>Mayor precio</option>
                <option value="price_asc" @selected($filters['sort'] === 'price_asc')>Menor precio</option>
                <option value="stock_desc" @selected($filters['sort'] === 'stock_desc')>Mayor stock</option>
                <option value="stock_asc" @selected($filters['sort'] === 'stock_asc')>Menor stock</option>
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="products-per-page">Por página</label>
            <x-ui.select id="products-per-page" name="per_page">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected((int) $filters['per_page'] === $option)>{{ $option }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="xl:col-span-8 flex flex-col gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-xs text-slate-500">
                Mostrando <strong class="text-slate-700">{{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</strong>
                de <strong class="text-slate-700">{{ number_format($products->total()) }}</strong> productos
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <button type="submit" form="products-filter-form" class="btn btn-primary w-full justify-center sm:w-auto">Buscar</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Limpiar</a>
            </div>
        </div>
    </x-ui.filter-bar>

    <section class="mt-4">
        @if($products->isEmpty())
            <x-ui.empty-state title="No hay productos para este filtro" description="Prueba con otra combinación de categoría, estado o término de búsqueda.">
                <x-slot name="action">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-primary">Ver catálogo completo</a>
                </x-slot>
            </x-ui.empty-state>
        @else
            <form
                action="{{ route('admin.products.bulk-action') }}"
                method="POST"
                class="mb-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-3"
                data-bulk-form
                data-confirm="Aplicar accion en lote a los productos seleccionados?"
            >
                @csrf
                <input type="hidden" name="action" value="" data-bulk-action-input>
                <div data-bulk-selected-inputs></div>
                @foreach($indexContextQuery as $key => $value)
                    <input type="hidden" name="index_context[{{ $key }}]" value="{{ $value }}">
                @endforeach

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-600">
                        Seleccionados: <strong data-bulk-count>0</strong>
                    </p>
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                        <button
                            type="submit"
                            class="btn btn-secondary w-full justify-center sm:w-auto"
                            data-bulk-action-trigger
                            data-bulk-action-value="deactivate"
                            data-bulk-confirm="Desactivar los productos seleccionados?"
                            data-bulk-submit
                            disabled
                        >
                            Desactivar seleccionados
                        </button>
                        <button
                            type="submit"
                            class="btn btn-secondary w-full justify-center sm:w-auto"
                            data-bulk-action-trigger
                            data-bulk-action-value="activate"
                            data-bulk-confirm="Activar los productos seleccionados?"
                            data-bulk-submit
                            disabled
                        >
                            Activar seleccionados
                        </button>
                        <button
                            type="submit"
                            class="btn btn-danger w-full justify-center sm:w-auto"
                            data-bulk-action-trigger
                            data-bulk-action-value="delete"
                            data-bulk-confirm="Eliminar los productos seleccionados? Esta accion no se puede deshacer."
                            data-bulk-submit
                            disabled
                        >
                            Eliminar seleccionados
                        </button>
                    </div>
                </div>
            </form>

            <x-ui.table data-bulk-table>
                <thead>
                    <tr>
                        <th class="w-10"><input type="checkbox" class="form-checkbox" data-bulk-master></th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Disponibilidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        @php
                            $hasActiveVariants = (int) ($product->active_variants_count ?? 0) > 0;
                            $activeVariants = $product->variants ?? collect();

                        @endphp
                        <tr>
                            <td data-label="Seleccionar">
                                <input type="checkbox" class="form-checkbox" data-bulk-row value="{{ $product->id }}">
                            </td>
                            <td data-label="Producto" data-full="true">
                                <div class="flex items-center gap-3">
                                    <x-ui.product-thumb :product="$product" size="sm" />
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $product->name }}</p>
                                        <p class="font-mono text-xs font-semibold text-slate-600">SKU: {{ $product->sku }}</p>
                                        @if($product->brand)
                                            <p class="text-xs text-slate-500">Marca: {{ $product->brand }}</p>
                                        @endif
                                        <p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit((string) $product->description, 42) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Categoría">{{ $product->category?->name ?? '-' }}</td>
                            <td data-label="Precio" class="font-medium text-slate-900">
                                ${{ number_format((float) $product->price, 0, ',', '.') }}
                                <span class="mt-1 block text-xs font-normal text-slate-500">
                                    {{ $product->is_vat_excluded ? 'Excluido de IVA' : 'IVA incluido' }}
                                </span>
                            </td>
                            <td data-label="Stock">
                                @if(! $hasActiveVariants)
                                    @if($product->isStockManagedByContaPyme())
                                        <div class="space-y-1">
                                            <p class="font-semibold text-slate-800">{{ $formatStock($product->stock) }}</p>
                                            <p class="text-xs text-slate-500" title="Actualizado desde ContaPyme">
                                                Gestionado por ContaPyme
                                            </p>
                                        </div>
                                    @else
                                    <form
                                        action="{{ route('admin.products.stock', $product) }}"
                                        method="POST"
                                        class="space-y-2"
                                        data-loading-form
                                    >
                                        @csrf
                                        @method('PATCH')
                                        @foreach($indexContextQuery as $key => $value)
                                            <input type="hidden" name="index_context[{{ $key }}]" value="{{ $value }}">
                                        @endforeach

                                        <label class="sr-only" for="stock-product-{{ $product->id }}">
                                            Stock de {{ $product->name }}
                                        </label>
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                            <x-ui.input
                                                id="stock-product-{{ $product->id }}"
                                                name="stock"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                    inputmode="decimal"
                                                    class="no-number-spinner w-full sm:w-28"
                                                    :value="is_numeric($product->stock) ? rtrim(rtrim(number_format((float) $product->stock, 2, '.', ''), '0'), '.') : ''"
                                                    placeholder="Sin definir"
                                                />
                                                <button
                                                    type="submit"
                                                    class="btn btn-secondary w-full justify-center sm:w-auto"
                                                    data-loading-label="Guardando..."
                                                >
                                                    Guardar
                                                </button>
                                            </div>
                                            <p class="text-xs text-slate-500">
                                                Actual: <span class="font-semibold text-slate-700">{{ $formatStock($product->stock) }}</span>
                                            </p>
                                        </form>
                                    @endif
                                @else
                                    <details class="rounded-xl border border-slate-200 bg-slate-50/70 p-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-slate-700">
                                            Editar variantes ({{ $activeVariants->count() }})
                                        </summary>

                                        <div class="mt-2 space-y-2">
                                            <p class="text-xs text-slate-500">
                                                Total actual: <span class="font-semibold text-slate-700">{{ $formatStock($product->stock) }}</span>
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                Atributo: <span class="font-semibold text-slate-700">{{ $product->variantAttribute?->name ?? 'Variante' }}</span>
                                            </p>

                                            <form
                                                    action="{{ route('admin.products.variants.stock', $product) }}"
                                                    method="POST"
                                                    class="space-y-2"
                                                    data-loading-form
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    @foreach($indexContextQuery as $key => $value)
                                                        <input type="hidden" name="index_context[{{ $key }}]" value="{{ $value }}">
                                                    @endforeach

                                                    @foreach($activeVariants as $variantIndex => $variant)
                                                        <input type="hidden" name="variants[{{ $variantIndex }}][id]" value="{{ $variant->id }}">
                                                        <div class="grid grid-cols-[minmax(0,1fr)_6.5rem] items-center gap-2">
                                                            <label class="text-xs text-slate-700" for="variant-stock-{{ $product->id }}-{{ $variant->id }}">
                                                                {{ $variant->attributeValue?->value ?? 'Variante #'.$variant->id }}
                                                            </label>
                                                            <x-ui.input
                                                                id="variant-stock-{{ $product->id }}-{{ $variant->id }}"
                                                                name="variants[{{ $variantIndex }}][stock]"
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                inputmode="decimal"
                                                                class="no-number-spinner"
                                                                :value="is_numeric($variant->stock) ? rtrim(rtrim(number_format((float) $variant->stock, 2, '.', ''), '0'), '.') : ''"
                                                                placeholder="-"
                                                            />
                                                        </div>
                                                    @endforeach

                                                    <div class="flex flex-col gap-2 pt-1">
                                                        <button
                                                            type="submit"
                                                            class="btn btn-secondary w-full justify-center"
                                                            data-loading-label="Guardando..."
                                                        >
                                                            Guardar variantes
                                                        </button>
                                                        <a
                                                            href="{{ route('admin.products.edit', array_merge(['product' => $product], $indexContextQuery)) }}"
                                                            class="btn btn-ghost w-full justify-center"
                                                        >
                                                            Abrir editor completo
                                                        </a>
                                                    </div>
                                                </form>
                                        </div>
                                    </details>
                                @endif
                            </td>
                            <td data-label="Disponibilidad">
                                <x-ui.status-badge :status="$product->is_active ? 'active' : 'inactive'" :label="$product->is_active ? 'Disponible' : 'Inactivo'" />
                            </td>
                            <td data-label="Acciones">
                                <div class="product-row-actions">
                                    <a
                                        href="{{ route('admin.products.edit', array_merge(['product' => $product], $indexContextQuery)) }}"
                                        class="product-row-action-icon"
                                        title="Editar producto"
                                        aria-label="Editar {{ $product->name }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                        </svg>
                                    </a>

                                    <form
                                        action="{{ route('admin.products.duplicate', $product) }}"
                                        method="POST"
                                        data-confirm="Duplicar {{ $product->name }} como copia inactiva?"
                                    >
                                        @csrf
                                        @foreach($indexContextQuery as $key => $value)
                                            <input type="hidden" name="index_context[{{ $key }}]" value="{{ $value }}">
                                        @endforeach
                                        <button
                                            type="submit"
                                            class="product-row-action-icon"
                                            title="Duplicar producto"
                                            aria-label="Duplicar {{ $product->name }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                            </svg>
                                        </button>
                                    </form>

                                    @if($product->is_active)
                                        <a
                                            href="{{ route('products.show', $product) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="product-row-action-icon"
                                            title="Ver como distribuidor"
                                            aria-label="Ver {{ $product->name }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                    @else
                                        <span
                                            class="product-row-action-icon is-disabled"
                                            title="Activa el producto para previsualizarlo"
                                            aria-hidden="true"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </span>
                                    @endif

                                    <form action="{{ route('admin.products.status', $product) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $product->is_active ? 0 : 1 }}">
                                        <button
                                            type="submit"
                                            class="product-status-switch {{ $product->is_active ? 'is-on' : 'is-off' }}"
                                            title="{{ $product->is_active ? 'Desactivar producto' : 'Activar producto' }}"
                                            aria-label="{{ $product->is_active ? 'Desactivar '.$product->name : 'Activar '.$product->name }}"
                                        >
                                            <span class="product-status-switch-thumb"></span>
                                        </button>
                                    </form>

                                    <form
                                        action="{{ route('admin.products.destroy', $product) }}"
                                        method="POST"
                                        data-confirm="Eliminar {{ $product->name }}? Esta accion no se puede deshacer."
                                    >
                                        @csrf
                                        @method('DELETE')
                                        @foreach($indexContextQuery as $key => $value)
                                            <input type="hidden" name="index_context[{{ $key }}]" value="{{ $value }}">
                                        @endforeach
                                        <button
                                            type="submit"
                                            class="product-row-action-icon text-red-600 hover:text-red-700"
                                            title="Eliminar producto"
                                            aria-label="Eliminar {{ $product->name }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 6h18"></path>
                                                <path d="M8 6V4h8v2"></path>
                                                <path d="M19 6l-1 14H6L5 6"></path>
                                                <path d="M10 11v6"></path>
                                                <path d="M14 11v6"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="mt-4">
                <x-ui.pagination :paginator="$products" />
            </div>
        @endif
    </section>
</x-app-layout>
