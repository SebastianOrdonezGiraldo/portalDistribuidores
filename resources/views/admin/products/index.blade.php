<x-app-layout>
    @php
        $filters = array_merge([
            'q' => null,
            'category_id' => null,
            'status' => null,
        ], $filters ?? []);
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Catálogo de Productos" subtitle="Gestión de portafolio con filtros rápidos, disponibilidad y acceso a edición.">
            <x-slot name="actions">
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Nuevo producto</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.filter-bar method="GET" action="{{ route('admin.products.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
        <div class="xl:col-span-2">
            <label class="form-label" for="products-q">Buscar</label>
            <x-ui.input id="products-q" name="q" :value="$filters['q']" placeholder="Producto, SKU o descripción" />
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
                <option value="active" @selected($filters['status'] === 'active')>Disponible</option>
                <option value="inactive" @selected($filters['status'] === 'inactive')>Inactivo</option>
            </x-ui.select>
        </div>

        <div class="flex items-end gap-2">
            <x-ui.button type="submit" variant="primary" class="w-full">Aplicar</x-ui.button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Limpiar</a>
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
                        <th>SKU</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Disponibilidad</th>
                        <th>Media</th>
                        <th>Actualización</th>
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
                                            <img src="{{ asset('storage/'.$product->primaryPhoto->path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full items-center justify-center text-xs text-slate-400">Sin</div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit((string) $product->description, 42) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="font-medium text-slate-900">{{ $product->sku }}</td>
                            <td>{{ $product->category?->name ?? '-' }}</td>
                            <td class="font-medium text-slate-900">${{ number_format((float) $product->price, 0, ',', '.') }}</td>
                            <td>
                                <x-ui.status-badge :status="$product->is_active ? 'active' : 'inactive'" :label="$product->is_active ? 'Disponible' : 'Inactivo'" />
                            </td>
                            <td>
                                <x-ui.badge variant="neutral">{{ $product->primaryPhoto ? 'Foto principal' : 'Sin foto' }}</x-ui.badge>
                            </td>
                            <td>{{ $product->updated_at?->format('d/m/Y') }}</td>
                            <td class="text-right">
                                <x-ui.action-menu>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Editar</a>
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
