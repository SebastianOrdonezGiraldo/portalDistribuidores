<x-app-layout>
    @php
        $baseQuickFilters = request()->except(['page', 'role', 'distributor_link']);

        $quickFilters = [
            ['label' => 'Todas', 'params' => $baseQuickFilters, 'active' => empty($filters['role']) && empty($filters['distributor_link']), 'count' => $metrics['total_users']],
            ['label' => 'Admins', 'params' => array_merge($baseQuickFilters, ['role' => 'admin']), 'active' => $filters['role'] === 'admin', 'count' => $metrics['admin_users']],
            ['label' => 'Distribuidores', 'params' => array_merge($baseQuickFilters, ['role' => 'distributor']), 'active' => $filters['role'] === 'distributor', 'count' => $metrics['distributor_users']],
            ['label' => 'Con empresa', 'params' => array_merge($baseQuickFilters, ['distributor_link' => 'linked']), 'active' => $filters['distributor_link'] === 'linked', 'count' => $metrics['linked_distributor']],
            ['label' => 'Sin empresa', 'params' => array_merge($baseQuickFilters, ['distributor_link' => 'unlinked']), 'active' => $filters['distributor_link'] === 'unlinked', 'count' => $metrics['unlinked_accounts']],
        ];
    @endphp

    <x-slot name="header">
        <x-ui.page-header
            title="Cuentas de acceso"
            subtitle="Administra las cuentas de acceso de administradores y distribuidores del portal."
        >
            <x-slot name="actions">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary w-full justify-center gap-2 sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Nueva cuenta de acceso
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-ui.kpi-card label="Cuentas filtradas" :value="number_format($metrics['total_users'])" hint="Resultado actual del listado" accent="brand" compact>
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </x-slot>
        </x-ui.kpi-card>

        <x-ui.kpi-card label="Administradores" :value="number_format($metrics['admin_users'])" hint="Acceso total al panel" accent="info" compact>
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </x-slot>
        </x-ui.kpi-card>

        <x-ui.kpi-card label="Distribuidores" :value="number_format($metrics['distributor_users'])" hint="Cuentas del canal comercial" accent="neutral" compact>
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </x-slot>
        </x-ui.kpi-card>

        <x-ui.kpi-card label="Con empresa" :value="number_format($metrics['linked_distributor'])" hint="Vinculadas a una empresa" accent="success" compact>
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </x-slot>
        </x-ui.kpi-card>

        <x-ui.kpi-card label="Correo verificado" :value="number_format($metrics['verified_users'])" hint="Con verificación completada" accent="warning" compact>
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/><path d="m16 11 2 2 4-4"/></svg>
            </x-slot>
        </x-ui.kpi-card>
    </section>

    <x-ui.card class="mt-4 p-4">
        <div class="flex flex-wrap items-center gap-2">
            @foreach($quickFilters as $chip)
                <a href="{{ route('admin.users.index', $chip['params']) }}"
                   class="filter-chip {{ $chip['active'] ? 'is-active' : '' }}">
                    {{ $chip['label'] }}
                    <span class="filter-chip__count">{{ number_format((int) $chip['count']) }}</span>
                </a>
            @endforeach
        </div>
    </x-ui.card>

    <x-ui.filter-bar method="GET" action="{{ route('admin.users.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <div class="xl:col-span-2">
            <label class="form-label" for="users-q">Buscar</label>
            <x-ui.input id="users-q" name="q" :value="$filters['q']" placeholder="Nombre o correo" />
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
            <label class="form-label" for="users-distributor-link">Empresa vinculada</label>
            <x-ui.select id="users-distributor-link" name="distributor_link">
                <option value="">Todas</option>
                @foreach($distributorLinkOptions as $option)
                    <option value="{{ $option }}" @selected($filters['distributor_link'] === $option)>{{ $option === 'linked' ? 'Con empresa' : 'Sin empresa' }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="users-distributor-id">Empresa distribuidora</label>
            <x-ui.select id="users-distributor-id" name="distributor_id">
                <option value="">Todas</option>
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
                        @elseif($sort === 'email_asc') Correo A-Z
                        @else Correo Z-A
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

        <div class="xl:col-span-6 flex flex-col gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="w-full text-xs text-slate-500">
                Mostrando <strong class="text-slate-700">{{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }}</strong>
                de <strong class="text-slate-700">{{ number_format($users->total()) }}</strong> cuentas
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-ui.button type="submit" variant="primary" class="w-full justify-center gap-2 sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    Aplicar
                </x-ui.button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary w-full justify-center gap-2 sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    Limpiar
                </a>
            </div>
        </div>
    </x-ui.filter-bar>

    <section class="mt-4">
        @if($users->isEmpty())
            <x-ui.empty-state title="No se encontraron cuentas de acceso" description="Ajusta filtros o crea una cuenta nueva para continuar.">
                <x-slot name="action">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Nueva cuenta de acceso</a>
                </x-slot>
            </x-ui.empty-state>
        @else
            <div class="table-wrap user-index-table">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th class="w-10"></th>
                            <th>Cuenta</th>
                            <th>Rol</th>
                            <th>Empresa vinculada</th>
                            <th>Verificación</th>
                            <th>Creación</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    @foreach($users as $user)
                        <tbody x-data="{ expanded: false }" class="align-top" x-bind:class="expanded ? 'is-expanded' : ''">
                            <tr>
                                <td data-no-label="true" class="!px-2">
                                    <button type="button"
                                            class="user-row-toggle"
                                            @click="expanded = !expanded"
                                            x-bind:aria-expanded="expanded.toString()"
                                            x-bind:aria-label="expanded ? 'Ocultar detalle de cuenta' : 'Ver detalle de cuenta'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" x-bind:class="expanded ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                </td>
                                <td data-label="Cuenta" data-full="true">
                                    <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                </td>
                                <td data-label="Rol">
                                    <x-admin.role-badge :role="$user->role" />
                                </td>
                                <td data-label="Empresa vinculada">
                                    @if($user->role->value === 'admin')
                                        <span class="text-sm text-slate-500">Sin empresa</span>
                                    @else
                                        <span class="text-sm font-medium text-slate-900">{{ $user->distributor?->name ?? '—' }}</span>
                                    @endif
                                </td>
                                <td data-label="Verificación">
                                    <x-admin.verification-badge :verified-at="$user->email_verified_at" />
                                </td>
                                <td data-label="Creación">
                                    <p class="text-sm text-slate-900">{{ $user->created_at?->format('d/m/Y H:i') }}</p>
                                    <p class="text-xs text-slate-500">{{ $user->created_at?->diffForHumans() }}</p>
                                </td>
                                <td data-label="Acciones" class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                class="btn btn-secondary !px-3 !py-1.5 text-xs"
                                                @click="expanded = !expanded">
                                            <span x-text="expanded ? 'Ocultar' : 'Acciones'"></span>
                                        </button>
                                        <x-ui.action-menu>
                                            <a href="{{ route('admin.users.edit', $user) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Editar cuenta</a>
                                            @if($user->distributor)
                                                <a href="{{ route('admin.distributors.edit', $user->distributor) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Ver empresa</a>
                                            @endif
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                  data-confirm="¿Eliminar {{ $user->name }}? Esta acción no se puede deshacer.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-red-700 hover:bg-red-50">Eliminar</button>
                                            </form>
                                        </x-ui.action-menu>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="expanded" x-cloak>
                                <td colspan="7" class="!p-0">
                                    <x-admin.users.expanded-row :user="$user" />
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>

            <div class="mt-4">
                <x-ui.pagination :paginator="$users" />
            </div>
        @endif
    </section>
</x-app-layout>
