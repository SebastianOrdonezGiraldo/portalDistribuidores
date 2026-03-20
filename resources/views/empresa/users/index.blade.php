<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Usuarios de la Empresa" subtitle="Gestiona quién tiene acceso al portal y con qué permisos.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Total: {{ $users->count() }}</span>
                    <span class="stat-pill">Activos: {{ $users->whereNotNull('email_verified_at')->count() }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('empresa.dashboard') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Dashboard</a>
                <a href="{{ route('empresa.users.create') }}" class="btn btn-primary w-full justify-center sm:w-auto">Nuevo usuario</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        @if($users->isEmpty())
            <div class="p-5">
                <x-ui.empty-state
                    title="Sin usuarios registrados"
                    description="Añade usuarios a tu empresa para que puedan acceder al portal."
                    compact
                />
            </div>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol en empresa</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td data-label="Nombre" data-full="true">
                                <p class="font-medium text-slate-900">{{ $user->name }}</p>
                                @if((int) $user->id === (int) auth()->id())
                                    <p class="text-xs text-brand-dark font-semibold">Tú</p>
                                @endif
                            </td>
                            <td data-label="Email" class="text-sm text-slate-600">{{ $user->email }}</td>
                            <td data-label="Rol en empresa">
                                @if($user->company_role)
                                    <x-ui.badge variant="info">{{ $user->company_role->label() }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral">Sin rol asignado</x-ui.badge>
                                @endif
                            </td>
                            <td data-label="Estado">
                                @if($user->email_verified_at)
                                    <x-ui.badge variant="success">Activo</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning">Inactivo</x-ui.badge>
                                @endif
                            </td>
                            <td data-label="Acciones" class="text-right">
                                <div class="flex w-full flex-wrap items-center justify-end gap-1">
                                    <a href="{{ route('empresa.users.edit', $user) }}" class="btn btn-ghost !px-2 !py-1 text-xs">Editar</a>
                                    @if((int) $user->id !== (int) auth()->id())
                                        <form method="POST" action="{{ route('empresa.users.toggle-active', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-ghost !px-2 !py-1 text-xs {{ $user->email_verified_at ? 'text-rose-600' : 'text-emerald-600' }}">
                                                {{ $user->email_verified_at ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </x-ui.card>

    {{-- Leyenda de roles --}}
    <x-ui.card class="mt-4 p-5">
        <h2 class="card-title">Roles disponibles</h2>
        <div class="mt-3 grid gap-2 sm:grid-cols-3">
            @foreach($companyRoles as $role)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-sm font-semibold text-slate-900">{{ $role->label() }}</p>
                    <p class="mt-1 text-xs text-slate-600">
                        @if($role->value === 'admin_empresa')
                            Gestiona empresa y usuarios. Puede ver historial y crear pedidos.
                        @elseif($role->value === 'usuario_comercial')
                            Puede ver historial, ver detalle y crear pedidos. No gestiona empresa ni usuarios.
                        @else
                            Solo puede ver historial, detalle y descargar PDFs. Sin acceso comercial ni de gestión.
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
    </x-ui.card>
</x-app-layout>
