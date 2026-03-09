<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-extrabold text-slate-900">Usuarios</h1>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">Nuevo usuario</a>
        </div>
    </x-slot>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                        <th class="pb-2 pr-3">Nombre</th>
                        <th class="pb-2 pr-3">Email</th>
                        <th class="pb-2 pr-3">Rol</th>
                        <th class="pb-2 pr-3">Distribuidor</th>
                        <th class="pb-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr>
                            <td class="py-2 pr-3 font-semibold text-slate-800">{{ $user->name }}</td>
                            <td class="py-2 pr-3">{{ $user->email }}</td>
                            <td class="py-2 pr-3">{{ $user->role->value }}</td>
                            <td class="py-2 pr-3">{{ $user->distributor?->name ?? '—' }}</td>
                            <td class="py-2 text-right">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-[#BC2983] underline">Editar</a>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-slate-500 underline" onclick="return confirm('¿Eliminar usuario?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-slate-500">Sin usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $users->links() }}</div>
    </section>
</x-app-layout>

