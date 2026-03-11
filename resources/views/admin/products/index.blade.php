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
                    <a href="{{ route('admin.products.import.template') }}" class="btn btn-secondary">Descargar plantilla CSV</a>
                    <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="hidden" name="default_action" value="upsert">
                        <input
                            type="file"
                            name="file"
                            accept=".csv,text/csv,application/vnd.ms-excel"
                            required
                            class="block w-48 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/30"
                        >
                        <button type="submit" class="btn btn-secondary">Importar CSV</button>
                    </form>
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Nuevo producto</a>
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('importReport'))
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

    @php
        $baseStatusQuery = request()->except(['page', 'status']);
    @endphp

    <x-ui.card class="mt-4 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.products.index', $baseStatusQuery) }}"
               class="btn {{ empty($filters['status']) ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Todos <span class="ml-1 text-[11px] opacity-80">{{ number_format($metrics['total_products']) }}</span>
            </a>
            <a href="{{ route('admin.products.index', array_merge($baseStatusQuery, ['status' => 'active'])) }}"
               class="btn {{ $filters['status'] === 'active' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Activos <span class="ml-1 text-[11px] opacity-80">{{ number_format($metrics['active_products']) }}</span>
            </a>
            <a href="{{ route('admin.products.index', array_merge($baseStatusQuery, ['status' => 'inactive'])) }}"
               class="btn {{ $filters['status'] === 'inactive' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Inactivos <span class="ml-1 text-[11px] opacity-80">{{ number_format($metrics['inactive_products']) }}</span>
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

    <x-ui.filter-bar method="GET" action="{{ route('admin.products.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-8">
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

        <div class="xl:col-span-8 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
            <div class="text-xs text-slate-500">
                Mostrando <strong class="text-slate-700">{{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</strong>
                de <strong class="text-slate-700">{{ number_format($products->total()) }}</strong> productos
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="primary">Aplicar</x-ui.button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Limpiar</a>
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
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Disponibilidad</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 overflow-hidden rounded-lg bg-slate-100">
                                        @if($product->primaryPhoto)
                                            <img src="{{ \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($product->primaryPhoto->path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full items-center justify-center text-xs text-slate-400">Sin</div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $product->name }}</p>
                                        @if($product->brand)
                                            <p class="text-xs text-slate-500">Marca: {{ $product->brand }}</p>
                                        @endif
                                        <p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit((string) $product->description, 42) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $product->category?->name ?? '-' }}</td>
                            <td class="font-medium text-slate-900">${{ number_format((float) $product->price, 0, ',', '.') }}</td>
                            <td>
                                <x-ui.status-badge :status="$product->is_active ? 'active' : 'inactive'" :label="$product->is_active ? 'Disponible' : 'Inactivo'" />
                            </td>
                            <td class="text-right">
                                <x-ui.action-menu>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Editar</a>
                                    <form action="{{ route('admin.products.status', $product) }}" method="POST" data-confirm="{{ $product->is_active ? '¿Desactivar '.$product->name.'?' : '¿Activar '.$product->name.'?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $product->is_active ? 0 : 1 }}">
                                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-slate-50">
                                            {{ $product->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" data-confirm="¿Eliminar {{ $product->name }}? Esta acción no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-red-700 hover:bg-red-50">Eliminar</button>
                                    </form>
                                </x-ui.action-menu>
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
