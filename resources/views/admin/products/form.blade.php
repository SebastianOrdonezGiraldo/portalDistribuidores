<x-app-layout>
    @php
        $isEdit = $product->exists;
        $isActiveRaw = old('is_active', $product->is_active ?? true);
        $isActiveChecked = filter_var($isActiveRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    @endphp

    <x-slot name="header">
        <x-ui.page-header :title="$isEdit ? 'Editar Producto' : 'Nuevo Producto'" subtitle="Configura información comercial, contenido y documentos del catálogo.">
            <x-slot name="actions">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Volver al catálogo</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}" data-loading-form class="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Identificación del Producto</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="name">Nombre *</label>
                        <x-ui.input id="name" name="name" :value="old('name', $product->name)" required />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <label class="form-label" for="sku">SKU *</label>
                        <x-ui.input id="sku" name="sku" :value="old('sku', $product->sku)" required />
                        <x-input-error :messages="$errors->get('sku')" />
                    </div>
                    <div>
                        <label class="form-label" for="category_id">Categoría *</label>
                        <x-ui.select id="category_id" name="category_id" required>
                            <option value="">Seleccionar</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-input-error :messages="$errors->get('category_id')" />
                    </div>
                    <div>
                        <label class="form-label" for="price">Precio *</label>
                        <x-ui.input id="price" name="price" type="number" min="0" step="0.01" :value="old('price', $product->price)" required />
                        <p class="form-help">Monto en moneda local, sin separador de miles.</p>
                        <x-input-error :messages="$errors->get('price')" />
                    </div>
                    <div>
                        <label class="form-label" for="stock">Stock</label>
                        <x-ui.input id="stock" name="stock" type="number" min="0" step="0.01" :value="old('stock', $product->stock)" />
                        <p class="form-help">Inventario disponible. Dejalo vacio si aun no esta confirmado.</p>
                        <x-input-error :messages="$errors->get('stock')" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Contenido Comercial</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="form-label" for="description">Descripción</label>
                        <x-ui.textarea id="description" name="description" rows="5">{{ old('description', $product->description) }}</x-ui.textarea>
                        <p class="form-help">Usa lenguaje claro orientado al distribuidor: aplicación, beneficio y especificación técnica clave.</p>
                        <x-input-error :messages="$errors->get('description')" />
                    </div>

                    <div>
                        <label class="form-label" for="video_url">Video de apoyo</label>
                        <x-ui.input id="video_url" type="url" name="video_url" :value="old('video_url')" placeholder="https://..." />
                        <x-input-error :messages="$errors->get('video_url')" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Media y Documentación</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="photo">Foto principal</label>
                        <x-ui.input id="photo" type="file" name="photo" accept="image/*" />
                        <p class="form-help">JPG/PNG hasta 3MB.</p>
                        <x-input-error :messages="$errors->get('photo')" />
                    </div>
                    <div>
                        <label class="form-label" for="tech_sheet">Ficha técnica (PDF)</label>
                        <x-ui.input id="tech_sheet" type="file" name="tech_sheet" accept="application/pdf" />
                        <p class="form-help">Archivo PDF hasta 5MB.</p>
                        <x-input-error :messages="$errors->get('tech_sheet')" />
                    </div>
                </div>

                @if($isEdit && $product->photos->isNotEmpty())
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <p class="text-sm font-medium text-slate-700">Fotos cargadas</p>
                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach($product->photos as $photo)
                                <img src="{{ asset('storage/'.$photo->path) }}" alt="Foto {{ $loop->iteration }}" class="h-24 w-full rounded-xl border border-slate-200 object-cover">
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-ui.card>

            <div class="sticky bottom-3 z-20 rounded-2xl border border-slate-200 bg-white p-3 shadow-panel">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <x-ui.checkbox name="is_active" value="1" :checked="$isActiveChecked ?? false" label="Producto disponible para distribuidores" />
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancelar</a>
                        <x-ui.button type="submit" variant="primary" data-loading-label="Guardando...">{{ $isEdit ? 'Actualizar producto' : 'Crear producto' }}</x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        <aside class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Estado actual</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><span class="text-slate-500">Modo</span><span class="font-medium text-slate-900">{{ $isEdit ? 'Edición' : 'Creación' }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Activo</span><x-ui.status-badge :status="($isActiveChecked ?? false) ? 'active' : 'inactive'" /></div>
                    @if($isEdit)
                        <div class="flex items-center justify-between"><span class="text-slate-500">Última edición</span><span class="font-medium text-slate-900">{{ $product->updated_at?->format('d/m/Y H:i') }}</span></div>
                    @endif
                </div>
            </x-ui.card>
        </aside>
    </form>
</x-app-layout>
