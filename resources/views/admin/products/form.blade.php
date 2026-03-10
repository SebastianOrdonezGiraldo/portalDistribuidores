<x-app-layout>
    @php
        $isEdit = $product->exists;
        $isActiveRaw = old('is_active', $product->is_active ?? true);
        $isActiveChecked = filter_var($isActiveRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $selectedCategoryId = old('category_id', $product->category_id);
        $selectedCategory = $categories->firstWhere('id', (int) $selectedCategoryId);
        $initialCategoryName = $selectedCategory?->name ?? $product->category?->name ?? 'Sin categoría seleccionada';
    @endphp

    <x-slot name="header">
        <x-ui.page-header :title="$isEdit ? 'Editar Producto' : 'Nuevo Producto'" subtitle="Configura información comercial, contenido y documentos del catálogo.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Modo: {{ $isEdit ? 'Edición' : 'Creación' }}</span>
                    @if($isEdit)
                        <span class="stat-pill">Última edición: {{ $product->updated_at?->diffForHumans() }}</span>
                        <span class="stat-pill">Activo: {{ ($isActiveChecked ?? false) ? 'Sí' : 'No' }}</span>
                    @endif
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Volver al catálogo</a>
                @if($isEdit)
                    <a href="{{ route('products.show', $product) }}" target="_blank" rel="noopener" class="btn btn-secondary">Ver como distribuidor</a>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form method="POST"
          enctype="multipart/form-data"
          action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
          data-loading-form
          data-unsaved-guard
          data-product-form
          data-sku-check-url="{{ route('admin.products.check-sku') }}"
          data-sku-ignore="{{ $isEdit ? $product->id : '' }}"
          class="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <x-ui.card class="p-5" id="identificacion-producto">
                <h2 class="card-title">Identificación del Producto</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="name">Nombre *</label>
                        <x-ui.input id="name" name="name" :value="old('name', $product->name)" required data-preview-name />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <label class="form-label" for="sku">SKU *</label>
                        <x-ui.input id="sku" name="sku" :value="old('sku', $product->sku)" required data-sku-input />
                        <p class="form-help" data-sku-feedback>Escribe un SKU único para evitar conflictos en catálogo y pedidos.</p>
                        <x-input-error :messages="$errors->get('sku')" />
                    </div>
                    <div>
                        <label class="form-label" for="category_id">Categoría *</label>
                        <x-ui.select id="category_id" name="category_id" required data-preview-category>
                            <option value="">Seleccionar</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-input-error :messages="$errors->get('category_id')" />
                    </div>
                    <div>
                        <label class="form-label" for="price">Precio *</label>
                        <x-ui.input id="price" name="price" type="number" min="0" step="0.01" :value="old('price', $product->price)" required data-live-price />
                        <p class="form-help">
                            Monto en moneda local, sin separador de miles.
                            <span class="ml-1 font-semibold text-slate-700" data-live-price-output>$0</span>
                        </p>
                        <x-input-error :messages="$errors->get('price')" />
                    </div>
                    <div>
                        <label class="form-label" for="stock">Stock</label>
                        <x-ui.input id="stock" name="stock" type="number" min="0" step="0.01" :value="old('stock', $product->stock)" data-live-stock />
                        <p class="form-help">
                            Inventario disponible. Déjalo vacío si aún no está confirmado.
                            <span class="ml-1 font-semibold text-slate-700" data-live-stock-output>Sin definir</span>
                        </p>
                        <x-input-error :messages="$errors->get('stock')" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5" id="contenido-comercial">
                <h2 class="card-title">Contenido Comercial</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="form-label" for="description">Descripción</label>
                        <x-ui.textarea id="description" name="description" rows="5" data-preview-description>{{ old('description', $product->description) }}</x-ui.textarea>
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

            <x-ui.card class="p-5" id="media-documentos">
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
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach($product->photos as $photo)
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-xs font-semibold text-slate-700">
                                            Foto {{ $loop->iteration }} {{ $photo->is_primary ? '(Principal)' : '' }}
                                        </p>
                                        <form method="POST" action="{{ route('admin.products.photos.destroy', [$product, $photo]) }}" data-confirm="¿Eliminar esta foto del producto?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-800">Eliminar</button>
                                        </form>
                                    </div>
                                    <img src="{{ asset('storage/'.$photo->path) }}" alt="Foto {{ $loop->iteration }}" class="mt-2 h-28 w-full rounded-xl border border-slate-200 object-cover">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($isEdit && $product->documents->isNotEmpty())
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <p class="text-sm font-medium text-slate-700">Documentos cargados</p>
                        <div class="mt-3 space-y-2">
                            @foreach($product->documents as $document)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                    <a href="{{ asset('storage/'.$document->path) }}" target="_blank" rel="noopener" class="font-medium text-slate-900 hover:underline">{{ $document->filename }}</a>
                                    <form method="POST" action="{{ route('admin.products.documents.destroy', [$product, $document]) }}" data-confirm="¿Eliminar este documento del producto?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-800">Eliminar</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($isEdit && $product->videos->isNotEmpty())
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <p class="text-sm font-medium text-slate-700">Videos cargados</p>
                        <div class="mt-3 space-y-2">
                            @foreach($product->videos as $video)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                    <a href="{{ $video->url }}" target="_blank" rel="noopener" class="font-medium text-slate-900 hover:underline">{{ \Illuminate\Support\Str::limit($video->url, 60) }}</a>
                                    <form method="POST" action="{{ route('admin.products.videos.destroy', [$product, $video]) }}" data-confirm="¿Eliminar este video del producto?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-800">Eliminar</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-ui.card>

            <div class="sticky bottom-3 z-20 rounded-2xl border border-slate-200 bg-white p-3 shadow-panel">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <x-ui.checkbox name="is_active" value="1" :checked="$isActiveChecked ?? false" label="Producto disponible para distribuidores" data-preview-active />
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" name="after_save" value="save" class="btn btn-primary" data-loading-label="Guardando...">Guardar</button>
                        <button type="submit" name="after_save" value="stay" class="btn btn-secondary" data-loading-label="Guardando...">Guardar y seguir editando</button>
                        <button type="submit" name="after_save" value="index" class="btn btn-secondary" data-loading-label="Guardando...">Guardar y volver</button>
                    </div>
                </div>
            </div>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <x-ui.card class="p-5">
                <h2 class="card-title">Estado actual</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><span class="text-slate-500">Modo</span><span class="font-medium text-slate-900">{{ $isEdit ? 'Edición' : 'Creación' }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Activo</span><x-ui.status-badge :status="($isActiveChecked ?? false) ? 'active' : 'inactive'" /></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Precio</span><span class="font-medium text-slate-900" data-live-price-output-sidebar>$0</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Stock</span><span class="font-medium text-slate-900" data-live-stock-output-sidebar>Sin definir</span></div>
                    @if($isEdit)
                        <div class="flex items-center justify-between"><span class="text-slate-500">Última edición</span><span class="font-medium text-slate-900">{{ $product->updated_at?->format('d/m/Y H:i') }}</span></div>
                    @endif
                </div>

                @if($isEdit)
                    <form method="POST" action="{{ route('admin.products.status', $product) }}" class="mt-4 border-t border-slate-200 pt-4" data-confirm="{{ $product->is_active ? '¿Desactivar este producto?' : '¿Activar este producto?' }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="is_active" value="{{ $product->is_active ? 0 : 1 }}">
                        <button type="submit" class="btn {{ $product->is_active ? 'btn-secondary' : 'btn-primary' }} w-full justify-center">
                            {{ $product->is_active ? 'Desactivar producto' : 'Activar producto' }}
                        </button>
                    </form>
                @endif
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Vista previa</h2>
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Nombre</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900" data-preview-name-output>{{ old('name', $product->name) ?: 'Nombre del producto' }}</p>
                    <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">Categoría</p>
                    <p class="mt-1 text-sm text-slate-700" data-preview-category-output>{{ $initialCategoryName }}</p>
                    <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">Descripción</p>
                    <p class="mt-1 text-sm text-slate-700" data-preview-description-output>{{ \Illuminate\Support\Str::limit(old('description', $product->description) ?: 'Sin descripción comercial', 140) }}</p>
                </div>
            </x-ui.card>
        </aside>
    </form>
</x-app-layout>
