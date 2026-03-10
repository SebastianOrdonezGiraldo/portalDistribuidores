<x-app-layout>
    @php
        $isEdit = $category->exists;
        $isActiveRaw = old('is_active', $category->is_active ?? true);
        $isActiveChecked = filter_var($isActiveRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    @endphp

    <x-slot name="header">
        <x-ui.page-header :title="$isEdit ? 'Editar categoría' : 'Nueva categoría'" subtitle="Define jerarquía, términos de búsqueda y estado de publicación en catálogo.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Modo: {{ $isEdit ? 'Edición' : 'Creación' }}</span>
                    @if($isEdit)
                        <span class="stat-pill">Actualizada: {{ $category->updated_at?->diffForHumans() }}</span>
                        <span class="stat-pill">Estado: {{ ($isActiveChecked ?? false) ? 'Activa' : 'Inactiva' }}</span>
                    @endif
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Volver al listado</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form method="POST"
          action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          data-loading-form
          data-unsaved-guard
          class="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Identificación</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="category-name">Nombre *</label>
                        <x-ui.input id="category-name" name="name" :value="old('name', $category->name)" required />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <label class="form-label" for="category-slug">Slug *</label>
                        <x-ui.input id="category-slug" name="slug" :value="old('slug', $category->slug)" required />
                        <p class="form-help">URL amigable para navegación y búsquedas.</p>
                        <x-input-error :messages="$errors->get('slug')" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Jerarquía y Visibilidad</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="category-parent">Categoría padre</label>
                        <x-ui.select id="category-parent" name="parent_id">
                            <option value="">Ninguna (raíz)</option>
                            @foreach($allCategories as $item)
                                <option value="{{ $item->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <p class="form-help">Organiza subcategorías para navegación por niveles.</p>
                        <x-input-error :messages="$errors->get('parent_id')" />
                    </div>

                    <div>
                        <label class="form-label" for="category-sort-order">Orden</label>
                        <x-ui.input id="category-sort-order" type="number" name="sort_order" min="0" :value="old('sort_order', $category->sort_order ?? 0)" />
                        <p class="form-help">Menor valor = mayor prioridad visual.</p>
                        <x-input-error :messages="$errors->get('sort_order')" />
                    </div>
                </div>

                <div class="mt-4 border-t border-slate-200 pt-4">
                    <input type="hidden" name="is_active" value="0">
                    <x-ui.checkbox name="is_active" value="1" :checked="$isActiveChecked ?? false" label="Categoría activa para el catálogo" />
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Sinónimos de búsqueda</h2>
                <div class="mt-4">
                    <label class="form-label" for="category-synonyms">Términos separados por coma</label>
                    <x-ui.textarea
                        id="category-synonyms"
                        name="synonyms"
                        rows="4"
                        placeholder="ej: guantes, protección, material médico">{{ old('synonyms', $synonyms) }}</x-ui.textarea>
                    <p class="form-help">Se normalizan en minúsculas y sin duplicados.</p>
                    <x-input-error :messages="$errors->get('synonyms')" />
                </div>
            </x-ui.card>

            <div class="sticky bottom-3 z-20 rounded-2xl border border-slate-200 bg-white p-3 shadow-panel">
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" name="after_save" value="stay" class="btn btn-secondary" data-loading-label="Guardando...">Guardar y seguir</button>
                    <button type="submit" name="after_save" value="index" class="btn btn-primary" data-loading-label="Guardando...">Guardar y volver</button>
                    @unless($isEdit)
                        <button type="submit" name="after_save" value="new" class="btn btn-secondary" data-loading-label="Guardando...">Guardar y nueva</button>
                    @endunless
                </div>
            </div>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <x-ui.card class="p-5">
                <h2 class="card-title">Resumen</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Modo</span>
                        <span class="font-medium text-slate-900">{{ $isEdit ? 'Edición' : 'Creación' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Estado</span>
                        <x-ui.status-badge :status="($isActiveChecked ?? false) ? 'active' : 'inactive'" />
                    </div>
                    @if($isEdit)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Slug actual</span>
                            <span class="font-medium text-slate-900">{{ $category->slug }}</span>
                        </div>
                    @endif
                </div>

                @if($isEdit)
                    <form method="POST" action="{{ route('admin.categories.status', $category) }}" class="mt-4 border-t border-slate-200 pt-4" data-confirm="{{ $category->is_active ? '¿Desactivar esta categoría?' : '¿Activar esta categoría?' }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="is_active" value="{{ $category->is_active ? 0 : 1 }}">
                        <button type="submit" class="btn {{ $category->is_active ? 'btn-secondary' : 'btn-primary' }} w-full justify-center">
                            {{ $category->is_active ? 'Desactivar categoría' : 'Activar categoría' }}
                        </button>
                    </form>
                @endif
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Recomendaciones</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>Usa nombres cortos y claros para el comercial.</li>
                    <li>Evita slugs duplicados o demasiado largos.</li>
                    <li>Define sinónimos que realmente use el cliente al buscar.</li>
                    <li>No elimines categorías con estructura activa; mejor desactívalas.</li>
                </ul>
            </x-ui.card>
        </aside>
    </form>
</x-app-layout>
