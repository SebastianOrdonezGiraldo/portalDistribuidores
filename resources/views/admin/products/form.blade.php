{{--
View contract:
- Source: App\Modules\Admin\Http\Controllers\ProductAdminController::create/edit.
- Expects: $product, $categories, $variantAttributes, and $indexContextQuery.
- Owns: create/edit form rendering, upload fields, preview state, and old-input recovery.
- Notes: validation, upload limits, media attachment, SKU checks, and variant sync stay in requests/actions/services.
--}}
<x-app-layout>
    {{-- Form state normalizes old input, existing product data, variants, and upload limits for create/edit mode. --}}
    @php
        $isEdit = $product->exists;
        $indexContextQuery = $indexContextQuery ?? [];
        $isActiveRaw = old('is_active', $product->is_active ?? true);
        $isActiveChecked = filter_var($isActiveRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $isVatExcludedRaw = old('is_vat_excluded', $product->is_vat_excluded ?? false);
        $isVatExcludedChecked = filter_var($isVatExcludedRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $selectedCategoryId = old('category_id', $product->category_id);
        $selectedCategory = $categories->firstWhere('id', (int) $selectedCategoryId);
        $initialCategoryName = $selectedCategory?->name ?? $product->category?->name ?? 'Sin categoría seleccionada';
        $existingVariantRows = $isEdit
            ? $product->variants->map(fn ($variant) => [
                'value' => $variant->attributeValue?->value,
                'price' => $variant->price,
                'stock' => $variant->stock,
            ])->values()->all()
            : [];
        $variantRows = old('variants', $existingVariantRows);
        $variantRows = is_array($variantRows) ? array_values($variantRows) : [];
        $hasVariantsChecked = filter_var(old('has_variants', $isEdit && $product->variants->isNotEmpty()), FILTER_VALIDATE_BOOLEAN);
        if ($hasVariantsChecked && $variantRows === []) {
            $variantRows[] = ['value' => '', 'price' => '', 'stock' => ''];
        }
        $selectedVariantAttributeId = old('variant_attribute_id', $product->variant_attribute_id);
        $newVariantAttributeName = old('new_variant_attribute_name');
        $uploadLimits = \App\Modules\Catalog\Support\ProductUploadLimits::viewData();
        $mediaUploadError = $errors->first('media_upload');
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
                <a href="{{ route('admin.products.index', $indexContextQuery) }}" class="btn btn-secondary">Volver al catálogo</a>
                @if($isEdit)
                    <a href="{{ route('products.show', $product) }}" target="_blank" rel="noopener" class="btn btn-secondary">Ver como distribuidor</a>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
    <form method="POST"
          enctype="multipart/form-data"
          action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
          data-loading-form
          data-unsaved-guard
          data-product-form
          data-sku-check-url="{{ route('admin.products.check-sku') }}"
          data-sku-ignore="{{ $isEdit ? $product->id : '' }}"
          data-total-max-kb="{{ $uploadLimits['request_max_kb'] }}"
          data-total-max-text="{{ $uploadLimits['request_max_size_label'] }}"
          class="contents">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        @foreach($indexContextQuery as $key => $value)
            <input type="hidden" name="index_context[{{ $key }}]" value="{{ $value }}">
        @endforeach

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
                        <label class="form-label" for="brand">Marca</label>
                        <x-ui.input id="brand" name="brand" :value="old('brand', $product->brand)" placeholder="Ej. 3M, BD, Omron" data-preview-brand />
                        <p class="form-help">Nombre comercial o fabricante del producto.</p>
                        <x-input-error :messages="$errors->get('brand')" />
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
                    <div class="sm:col-span-2">
                        <input type="hidden" name="is_vat_excluded" value="0">
                        <x-ui.checkbox
                            name="is_vat_excluded"
                            value="1"
                            :checked="$isVatExcludedChecked ?? false"
                            label="Producto excluido de IVA"
                        />
                        <p class="form-help">Marca esta opcion si la venta del producto no causa IVA. El precio se conservara tal cual.</p>
                        <x-input-error :messages="$errors->get('is_vat_excluded')" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5" id="variantes-producto">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="card-title">Variantes del producto</h2>
                        <p class="form-help">Solo se permite 1 atributo por producto (por ejemplo: Color).</p>
                    </div>
                    <div class="w-full sm:w-auto">
                        <input type="hidden" name="has_variants" value="0">
                        <x-ui.checkbox
                            name="has_variants"
                            value="1"
                            :checked="$hasVariantsChecked"
                            label="Este producto tiene variantes"
                            data-has-variants-toggle
                        />
                    </div>
                </div>

                <div class="mt-4 space-y-4 {{ $hasVariantsChecked ? '' : 'hidden' }}" data-variant-section>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label" for="variant_attribute_id">Atributo global existente</label>
                            <x-ui.select id="variant_attribute_id" name="variant_attribute_id" data-variant-attribute-select>
                                <option value="">Seleccionar atributo</option>
                                @foreach($variantAttributes as $attribute)
                                    <option value="{{ $attribute->id }}" @selected((string) $selectedVariantAttributeId === (string) $attribute->id)>
                                        {{ $attribute->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                            <p class="form-help">Elige un atributo ya creado o define uno nuevo abajo.</p>
                            <x-input-error :messages="$errors->get('variant_attribute_id')" />
                        </div>

                        <div>
                            <label class="form-label" for="new_variant_attribute_name">Nuevo atributo global</label>
                            <x-ui.input
                                id="new_variant_attribute_name"
                                name="new_variant_attribute_name"
                                :value="$newVariantAttributeName"
                                placeholder="Ej. Color, Talla, Peso"
                            />
                            <p class="form-help">Si no existe, se crea globalmente para futuros productos.</p>
                            <x-input-error :messages="$errors->get('new_variant_attribute_name')" />
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-700">Valores de variante (precio y stock por valor)</p>
                            <button type="button" class="btn btn-secondary" data-variant-add-row>Agregar valor</button>
                        </div>

                        <div class="mt-3 space-y-2" data-variant-rows>
                            @foreach($variantRows as $index => $row)
                                <div class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-[1.4fr_1fr_1fr_auto]" data-variant-row>
                                    <div>
                                        <label class="form-label md:hidden" for="variant-value-{{ $index }}">Valor</label>
                                        <x-ui.input
                                            id="variant-value-{{ $index }}"
                                            name="variants[{{ $index }}][value]"
                                            :value="data_get($row, 'value')"
                                            placeholder="Ej. Rojo, Azul, 15.4 cm"
                                        />
                                        <x-input-error :messages="$errors->get('variants.'.$index.'.value')" />
                                    </div>
                                    <div>
                                        <label class="form-label md:hidden" for="variant-price-{{ $index }}">Precio</label>
                                        <x-ui.input
                                            id="variant-price-{{ $index }}"
                                            name="variants[{{ $index }}][price]"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :value="data_get($row, 'price')"
                                            placeholder="0.00"
                                        />
                                        <x-input-error :messages="$errors->get('variants.'.$index.'.price')" />
                                    </div>
                                    <div>
                                        <label class="form-label md:hidden" for="variant-stock-{{ $index }}">Stock</label>
                                        <x-ui.input
                                            id="variant-stock-{{ $index }}"
                                            name="variants[{{ $index }}][stock]"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :value="data_get($row, 'stock')"
                                            placeholder="Opcional"
                                        />
                                        <x-input-error :messages="$errors->get('variants.'.$index.'.stock')" />
                                    </div>
                                    <div class="flex items-end justify-end">
                                        <button type="button" class="btn btn-ghost !px-3 !py-2 text-xs text-red-700 hover:bg-red-50" data-variant-remove-row>Quitar</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <x-input-error :messages="$errors->get('variants')" />
                    </div>
                </div>

                <template data-variant-template>
                    <div class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-[1.4fr_1fr_1fr_auto]" data-variant-row>
                        <div>
                            <input
                                type="text"
                                name="variants[__INDEX__][value]"
                                placeholder="Ej. Rojo, Azul, 15.4 cm"
                                class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/30"
                            >
                        </div>
                        <div>
                            <input
                                type="number"
                                name="variants[__INDEX__][price]"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/30"
                            >
                        </div>
                        <div>
                            <input
                                type="number"
                                name="variants[__INDEX__][stock]"
                                min="0"
                                step="0.01"
                                placeholder="Opcional"
                                class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/30"
                            >
                        </div>
                        <div class="flex items-end justify-end">
                            <button type="button" class="btn btn-ghost !px-3 !py-2 text-xs text-red-700 hover:bg-red-50" data-variant-remove-row>Quitar</button>
                        </div>
                    </div>
                </template>
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
                <div
                    data-upload-feedback
                    class="{{ $mediaUploadError ? '' : 'hidden' }} rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-900"
                    tabindex="-1"
                >
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <div>
                            <p class="text-sm font-semibold">No pudimos cargar los archivos seleccionados.</p>
                            <p class="mt-1 text-sm" data-upload-feedback-message>{{ $mediaUploadError }}</p>
                        </div>
                    </div>
                </div>
                <h2 class="card-title">Media y Documentación</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Limites vigentes: hasta {{ $uploadLimits['photo_max_files'] }} fotos de {{ $uploadLimits['photo_max_size_label'] }} cada una,
                    1 ficha técnica PDF de {{ $uploadLimits['tech_sheet_max_size_label'] }},
                    1 manual de usuario PDF de {{ $uploadLimits['manual_max_size_label'] }},
                    1 INVIMA PDF de {{ $uploadLimits['invima_max_size_label'] }},
                    1 guía rápida PDF de {{ $uploadLimits['quick_guide_max_size_label'] }},
                    1 documento de calibracion PDF de {{ $uploadLimits['calibration_document_max_size_label'] }}
                    y una carga total maxima de {{ $uploadLimits['request_max_size_label'] }}.
                </p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="photos">Fotos del producto</label>
                        <x-ui.input
                            id="photos"
                            type="file"
                            name="photos[]"
                            accept="image/*"
                            multiple
                            data-max-size-kb="{{ $uploadLimits['photo_max_size_kb'] }}"
                            data-max-size-text="{{ $uploadLimits['photo_max_size_label'] }}"
                            data-upload-label="fotos del producto"
                            data-max-files="{{ $uploadLimits['photo_max_files'] }}"
                        />
                        <p class="form-help">JPG/PNG hasta {{ $uploadLimits['photo_max_size_label'] }} por imagen. Puedes seleccionar varias a la vez.</p>
                        <x-input-error :messages="$errors->get('photos')" />
                        <x-input-error :messages="$errors->get('photos.*')" />
                    </div>
                    <div>
                        <label class="form-label" for="tech_sheet">Ficha técnica (PDF)</label>
                        <x-ui.input
                            id="tech_sheet"
                            type="file"
                            name="tech_sheet"
                            accept="application/pdf"
                            data-max-size-kb="{{ $uploadLimits['tech_sheet_max_size_kb'] }}"
                            data-max-size-text="{{ $uploadLimits['tech_sheet_max_size_label'] }}"
                            data-upload-label="ficha tecnica"
                        />
                        <p class="form-help">Archivo PDF hasta {{ $uploadLimits['tech_sheet_max_size_label'] }}.</p>
                        <x-input-error :messages="$errors->get('tech_sheet')" />
                    </div>
                    <div>
                        <label class="form-label" for="manual">Manual de usuario (PDF)</label>
                        <x-ui.input
                            id="manual"
                            type="file"
                            name="manual"
                            accept="application/pdf"
                            data-max-size-kb="{{ $uploadLimits['manual_max_size_kb'] }}"
                            data-max-size-text="{{ $uploadLimits['manual_max_size_label'] }}"
                            data-upload-label="manual de usuario"
                        />
                        <p class="form-help">Archivo PDF hasta {{ $uploadLimits['manual_max_size_label'] }}.</p>
                        <x-input-error :messages="$errors->get('manual')" />
                    </div>
                    <div>
                        <label class="form-label" for="invima">INVIMA (PDF)</label>
                        <x-ui.input
                            id="invima"
                            type="file"
                            name="invima"
                            accept="application/pdf"
                            data-max-size-kb="{{ $uploadLimits['invima_max_size_kb'] }}"
                            data-max-size-text="{{ $uploadLimits['invima_max_size_label'] }}"
                            data-upload-label="INVIMA"
                        />
                        <p class="form-help">Archivo PDF hasta {{ $uploadLimits['invima_max_size_label'] }}.</p>
                        <x-input-error :messages="$errors->get('invima')" />
                    </div>
                    <div>
                        <label class="form-label" for="quick_guide">Guía rápida del producto (PDF)</label>
                        <x-ui.input
                            id="quick_guide"
                            type="file"
                            name="quick_guide"
                            accept="application/pdf"
                            data-max-size-kb="{{ $uploadLimits['quick_guide_max_size_kb'] }}"
                            data-max-size-text="{{ $uploadLimits['quick_guide_max_size_label'] }}"
                            data-upload-label="guía rápida del producto"
                        />
                        <p class="form-help">Archivo PDF hasta {{ $uploadLimits['quick_guide_max_size_label'] }}.</p>
                        <x-input-error :messages="$errors->get('quick_guide')" />
                    </div>
                    <div>
                        <label class="form-label" for="calibration_document">Documento de calibracion (PDF)</label>
                        <x-ui.input
                            id="calibration_document"
                            type="file"
                            name="calibration_document"
                            accept="application/pdf"
                            data-max-size-kb="{{ $uploadLimits['calibration_document_max_size_kb'] }}"
                            data-max-size-text="{{ $uploadLimits['calibration_document_max_size_label'] }}"
                            data-upload-label="documento de calibracion"
                        />
                        <p class="form-help">Archivo PDF hasta {{ $uploadLimits['calibration_document_max_size_label'] }}.</p>
                        <x-input-error :messages="$errors->get('calibration_document')" />
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
                                        <x-ui.delete-media-button
                                            :action="route('admin.products.photos.destroy', [$product, $photo])"
                                            confirm="¿Eliminar esta foto del producto?"
                                        />
                                    </div>
                                    <img src="{{ \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($photo->path) }}" alt="Foto {{ $loop->iteration }}" class="mt-2 h-28 w-full rounded-xl border border-slate-200 object-cover">
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
                                    @php
                                        $documentUrl = $document->isProtected()
                                            ? route('admin.products.documents.download', [$product, $document])
                                            : \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($document->path);
                                    @endphp
                                    <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="min-w-0 font-medium text-slate-900 hover:underline">
                                        <span class="block truncate">{{ $document->filename }}</span>
                                        <span class="mt-0.5 block text-xs font-medium text-slate-500">{{ $document->typeLabel() }}</span>
                                    </a>
                                    <x-ui.delete-media-button
                                        :action="route('admin.products.documents.destroy', [$product, $document])"
                                        confirm="¿Eliminar este documento del producto?"
                                    />
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
                                    <x-ui.delete-media-button
                                        :action="route('admin.products.videos.destroy', [$product, $video])"
                                        confirm="¿Eliminar este video del producto?"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-ui.card>

            <div class="sticky bottom-3 z-20 rounded-2xl border border-slate-200 bg-white p-3 shadow-panel">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <input type="hidden" name="is_active" value="0">
                    <x-ui.checkbox name="is_active" value="1" :checked="$isActiveChecked ?? false" label="Producto disponible para distribuidores" data-preview-active />
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                        <a href="{{ route('admin.products.index', $indexContextQuery) }}" class="btn btn-secondary w-full justify-center sm:w-auto">Cancelar</a>
                        <button type="submit" name="after_save" value="save" class="btn btn-primary w-full justify-center sm:w-auto" data-loading-label="Guardando...">Guardar</button>
                        <button type="submit" name="after_save" value="stay" class="btn btn-secondary w-full justify-center sm:w-auto" data-loading-label="Guardando...">Guardar y seguir editando</button>
                        <button type="submit" name="after_save" value="index" class="btn btn-secondary w-full justify-center sm:w-auto" data-loading-label="Guardando...">Guardar y volver</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start" id="product-form-sidebar">
            <x-ui.card class="p-5">
                <h2 class="card-title">Estado actual</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><span class="text-slate-500">Modo</span><span class="font-medium text-slate-900">{{ $isEdit ? 'Edición' : 'Creación' }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Activo</span><x-ui.status-badge :status="($isActiveChecked ?? false) ? 'active' : 'inactive'" /></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">IVA</span><span class="font-medium text-slate-900">{{ ($isVatExcludedChecked ?? false) ? 'Excluido de IVA' : 'IVA incluido' }}</span></div>
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
                    <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">Marca</p>
                    <p class="mt-1 text-sm text-slate-700" data-preview-brand-output>{{ old('brand', $product->brand) ?: 'Sin marca' }}</p>
                    <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">Categoría</p>
                    <p class="mt-1 text-sm text-slate-700" data-preview-category-output>{{ $initialCategoryName }}</p>
                    <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">Descripción</p>
                    <p class="mt-1 text-sm text-slate-700" data-preview-description-output>{{ \Illuminate\Support\Str::limit(old('description', $product->description) ?: 'Sin descripción comercial', 140) }}</p>
                </div>
            </x-ui.card>
    </aside>
    </div>
</x-app-layout>
