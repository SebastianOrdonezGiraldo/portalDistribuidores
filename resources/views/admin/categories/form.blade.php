<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-extrabold text-slate-900">
            {{ $category->exists ? 'Editar categoría' : 'Nueva categoría' }}
        </h1>
    </x-slot>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="space-y-3">
            @csrf
            @if($category->exists)
                @method('PUT')
            @endif

            <label class="block text-sm">
                <span class="mb-1 block font-semibold text-slate-700">Nombre</span>
                <input type="text" name="name" required value="{{ old('name', $category->name) }}" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-semibold text-slate-700">Slug</span>
                <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-semibold text-slate-700">Categoría padre</span>
                <select name="parent_id" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
                    <option value="">Ninguna</option>
                    @foreach($allCategories as $item)
                        <option value="{{ $item->id }}" @selected(old('parent_id', $category->parent_id) == $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-semibold text-slate-700">Sinónimos (coma separados)</span>
                <input type="text" name="synonyms" value="{{ old('synonyms', $synonyms) }}" placeholder="ej: guantes, protección" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
            </label>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block font-semibold text-slate-700">Orden</span>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
                </label>

                <label class="inline-flex items-center gap-2 pt-7 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true)) class="rounded border-slate-300 text-[#8DC543] focus:ring-[#8DC543]">
                    Activa
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Guardar</button>
                <a href="{{ route('admin.categories.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </section>
</x-app-layout>

