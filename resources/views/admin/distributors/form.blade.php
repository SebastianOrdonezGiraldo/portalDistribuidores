<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-extrabold text-slate-900">
            {{ $distributor->exists ? 'Editar distribuidor' : 'Nuevo distribuidor' }}
        </h1>
    </x-slot>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="POST" action="{{ $distributor->exists ? route('admin.distributors.update', $distributor) : route('admin.distributors.store') }}" class="space-y-3">
            @csrf
            @if($distributor->exists)
                @method('PUT')
            @endif

            <label class="block text-sm">
                <span class="mb-1 block font-semibold text-slate-700">Nombre</span>
                <input type="text" name="name" required value="{{ old('name', $distributor->name) }}" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-semibold text-slate-700">Estado</span>
                <select name="status" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
                    <option value="active" @selected(old('status', $distributor->status) === 'active')>Activo</option>
                    <option value="inactive" @selected(old('status', $distributor->status) === 'inactive')>Inactivo</option>
                </select>
            </label>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Guardar</button>
                <a href="{{ route('admin.distributors.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </section>
</x-app-layout>

