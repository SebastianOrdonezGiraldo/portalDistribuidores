<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-extrabold text-slate-900">Distribuidores</h1>
            <a href="{{ route('admin.distributors.create') }}" class="btn-primary">Nuevo distribuidor</a>
        </div>
    </x-slot>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                        <th class="pb-2 pr-3">Nombre</th>
                        <th class="pb-2 pr-3">Estado</th>
                        <th class="pb-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($distributors as $distributor)
                        <tr>
                            <td class="py-2 pr-3 font-semibold text-slate-800">{{ $distributor->name }}</td>
                            <td class="py-2 pr-3">{{ $distributor->status }}</td>
                            <td class="py-2 text-right">
                                <a href="{{ route('admin.distributors.edit', $distributor) }}" class="text-[#BC2983] underline">Editar</a>
                                <form action="{{ route('admin.distributors.destroy', $distributor) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-slate-500 underline" onclick="return confirm('¿Eliminar distribuidor?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-slate-500">Sin distribuidores.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $distributors->links() }}</div>
    </section>
</x-app-layout>

