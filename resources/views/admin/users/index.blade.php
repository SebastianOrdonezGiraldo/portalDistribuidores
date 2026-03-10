<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Usuarios" subtitle="Administración de accesos internos y cuentas de distribuidores.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Resultados: {{ number_format($metrics['total_users']) }}</span>
                    <span class="stat-pill">Admins: {{ number_format($metrics['admin_users']) }}</span>
                    <span class="stat-pill">Distribuidores: {{ number_format($metrics['distributor_users']) }}</span>
                    <span class="stat-pill">Con distribuidor: {{ number_format($metrics['linked_distributor']) }}</span>
                    <span class="stat-pill">Filtros activos: {{ $activeFiltersCount }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Nuevo usuario</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-ui.kpi-card label="Usuarios Filtrados" :value="number_format($metrics['total_users'])" hint="Resultado actual del listado" />
        <x-ui.kpi-card label="Administradores" :value="number_format($metrics['admin_users'])" hint="Acceso total al panel" />
        <x-ui.kpi-card label="Distribuidores" :value="number_format($metrics['distributor_users'])" hint="Usuarios del canal comercial" />
        <x-ui.kpi-card label="Con Distribuidor" :value="number_format($metrics['linked_distributor'])" hint="Cuenta vinculada a cliente" />
        <x-ui.kpi-card label="Email Verificado" :value="number_format($metrics['verified_users'])" hint="Con verificación completada" />
    </section>

    @php
        $baseQuickFilters = request()->except(['page', 'role', 'distributor_link']);
    @endphp

    <x-ui.card class="mt-4 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.users.index', $baseQuickFilters) }}"
               class="btn {{ empty($filters['role']) && empty($filters['distributor_link']) ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Todos <span class="ml-1 text-[11px] opacity-80">{{ number_format($metrics['total_users']) }}</span>
            </a>
            <a href="{{ route('admin.users.index', array_merge($baseQuickFilters, ['role' => 'admin'])) }}"
               class="btn {{ $filters['role'] === 'admin' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Admins <span class="ml-1 text-[11px] opacity-80">{{ number_format($metrics['admin_users']) }}</span>
            </a>
            <a href="{{ route('admin.users.index', array_merge($baseQuickFilters, ['role' => 'distributor'])) }}"
               class="btn {{ $filters['role'] === 'distributor' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Distribuidores <span class="ml-1 text-[11px] opacity-80">{{ number_format($metrics['distributor_users']) }}</span>
            </a>
            <a href="{{ route('admin.users.index', array_merge($baseQuickFilters, ['distributor_link' => 'linked'])) }}"
               class="btn {{ $filters['distributor_link'] === 'linked' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Con distribuidor
            </a>
            <a href="{{ route('admin.users.index', array_merge($baseQuickFilters, ['distributor_link' => 'unlinked'])) }}"
               class="btn {{ $filters['distributor_link'] === 'unlinked' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Sin distribuidor
            </a>
        </div>
    </x-ui.card>

    <x-ui.filter-bar method="GET" action="{{ route('admin.users.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <div class="xl:col-span-2">
            <label class="form-label" for="users-q">Buscar</label>
            <x-ui.input id="users-q" name="q" :value="$filters['q']" placeholder="Nombre o email" />
        </div>

        <div>
            <label class="form-label" for="users-role">Rol</label>
            <x-ui.select id="users-role" name="role">
                <option value="">Todos</option>
                @foreach($roleOptions as $role)
                    <option value="{{ $role }}" @selected($filters['role'] === $role)>{{ $role === 'admin' ? 'Administrador' : 'Distribuidor' }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="users-distributor-link">Distribuidor vinculado</label>
            <x-ui.select id="users-distributor-link" name="distributor_link">
                <option value="">Todos</option>
                @foreach($distributorLinkOptions as $option)
                    <option value="{{ $option }}" @selected($filters['distributor_link'] === $option)>{{ $option === 'linked' ? 'Con distribuidor' : 'Sin distribuidor' }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="users-distributor-id">Distribuidor</label>
            <x-ui.select id="users-distributor-id" name="distributor_id">
                <option value="">Todos</option>
                @foreach($distributors as $distributor)
                    <option value="{{ $distributor->id }}" @selected((string) $filters['distributor_id'] === (string) $distributor->id)>{{ $distributor->name }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="users-sort">Orden</label>
            <x-ui.select id="users-sort" name="sort">
                @foreach($sortOptions as $sort)
                    <option value="{{ $sort }}" @selected($filters['sort'] === $sort)>
                        @if($sort === 'newest') Más recientes
                        @elseif($sort === 'oldest') Más antiguos
                        @elseif($sort === 'name_asc') Nombre A-Z
                        @elseif($sort === 'name_desc') Nombre Z-A
                        @elseif($sort === 'email_asc') Email A-Z
                        @else Email Z-A
                        @endif
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="users-per-page">Por página</label>
            <x-ui.select id="users-per-page" name="per_page">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected((int) $filters['per_page'] === $option)>{{ $option }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="xl:col-span-6 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
            <div class="text-xs text-slate-500">
                Mostrando <strong class="text-slate-700">{{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }}</strong>
                de <strong class="text-slate-700">{{ number_format($users->total()) }}</strong> usuarios
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="primary">Aplicar</x-ui.button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Limpiar</a>
            </div>
        </div>
    </x-ui.filter-bar>

    <section class="mt-4">
        @if($users->isEmpty())
            <x-ui.empty-state title="No se encontraron usuarios" description="Ajusta filtros o crea un usuario nuevo para continuar.">
                <x-slot name="action">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Crear usuario</a>
                </x-slot>
            </x-ui.empty-state>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Distribuidor</th>
                        <th>Verificación</th>
                        <th>Creación</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>
                                <p class="font-medium text-slate-900">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                            </td>
                            <td>
                                <x-ui.badge :variant="$user->role->value === 'admin' ? 'brand' : 'info'">
                                    {{ $user->role->value === 'admin' ? 'Administrador' : 'Distribuidor' }}
                                </x-ui.badge>
                            </td>
                            <td>{{ $user->distributor?->name ?? '—' }}</td>
                            <td>
                                @if($user->email_verified_at)
                                    <x-ui.badge variant="success">Verificado</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning">Pendiente</x-ui.badge>
                                @endif
                            </td>
                            <td>
                                <p>{{ $user->created_at?->format('d/m/Y H:i') }}</p>
                                <p class="text-[11px] text-slate-500">{{ $user->created_at?->diffForHumans() }}</p>
                            </td>
                            <td class="text-right">
                                <x-ui.action-menu>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Editar</a>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                          data-confirm="¿Eliminar {{ $user->name }}? Esta acción no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-red-700 hover:bg-red-50">Eliminar</button>
                                    </form>
                                </x-ui.action-menu>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="mt-4">
                <x-ui.pagination :paginator="$users" />
            </div>
        @endif
    </section>
</x-app-layout>
