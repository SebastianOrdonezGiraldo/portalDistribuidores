<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-extrabold text-slate-900">Categorías</h1>
            <a href="{{ route('admin.categories.create') }}" class="btn-primary">Nueva categoría</a>
        </div>
    </x-slot>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                        <th class="pb-2 pr-3">Nombre</th>
                        <th class="pb-2 pr-3">Slug</th>
                        <th class="pb-2 pr-3">Estado</th>
                        <th class="pb-2 pr-3">Orden</th>
                        <th class="pb-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        @include('admin.categories._node', ['category' => $category, 'depth' => 0])
                    @empty
                        <tr><td colspan="5" class="py-4 text-sm text-slate-500">Sin categorías.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>

