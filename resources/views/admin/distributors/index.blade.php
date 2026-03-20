<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Distribuidores" subtitle="Gestión comercial de cuentas, usuarios vinculados y actividad operativa.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Resultados: {{ number_format($metrics['total_distributors']) }}</span>
                    <span class="stat-pill">Activos: {{ number_format($metrics['active_distributors']) }}</span>
                    <span class="stat-pill">Con usuarios: {{ number_format($metrics['with_users']) }}</span>
                    <span class="stat-pill">Con pedidos: {{ number_format($metrics['with_orders']) }}</span>
                    <span class="stat-pill">Filtros activos: {{ $activeFiltersCount }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.distributors.create') }}" class="btn btn-primary w-full justify-center sm:w-auto">Nuevo distribuidor</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-ui.kpi-card label="Distribuidores Filtrados" :value="number_format($metrics['total_distributors'])" hint="Resultado actual del listado" />
        <x-ui.kpi-card label="Activos" :value="number_format($metrics['active_distributors'])" hint="Con acceso habilitado" />
        <x-ui.kpi-card label="Inactivos" :value="number_format($metrics['inactive_distributors'])" hint="Sin operación comercial" />
        <x-ui.kpi-card label="Con Usuarios" :value="number_format($metrics['with_users'])" hint="Cuentas vinculadas en portal" />
        <x-ui.kpi-card label="Con Pedidos" :value="number_format($metrics['with_orders'])" hint="Historial de compras registrado" />
    </section>

    @php
        $baseQuickFilters = request()->except(['page', 'status', 'relation']);
    @endphp

    <x-ui.card class="mt-4 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.distributors.index', $baseQuickFilters) }}"
               class="btn {{ empty($filters['status']) && empty($filters['relation']) ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Todos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['total_distributors']) }}</span>
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['status' => 'active'])) }}"
               class="btn {{ $filters['status'] === 'active' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Activos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['active_distributors']) }}</span>
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['status' => 'inactive'])) }}"
               class="btn {{ $filters['status'] === 'inactive' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Inactivos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['inactive_distributors']) }}</span>
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['relation' => 'with_users'])) }}"
               class="btn {{ $filters['relation'] === 'with_users' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Con usuarios
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['relation' => 'with_orders'])) }}"
               class="btn {{ $filters['relation'] === 'with_orders' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Con pedidos
            </a>
        </div>
    </x-ui.card>

    <x-ui.filter-bar method="GET" action="{{ route('admin.distributors.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <div class="xl:col-span-2">
            <label class="form-label" for="distributors-q">Buscar</label>
            <x-ui.input id="distributors-q" name="q" :value="$filters['q']" placeholder="Nombre del distribuidor" />
        </div>

        <div>
            <label class="form-label" for="distributors-status">Estado</label>
            <x-ui.select id="distributors-status" name="status">
                <option value="">Todos</option>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status === 'active' ? 'Activo' : 'Inactivo' }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="distributors-relation">Relación</label>
            <x-ui.select id="distributors-relation" name="relation">
                <option value="">Todas</option>
                @foreach($relationOptions as $relation)
                    <option value="{{ $relation }}" @selected($filters['relation'] === $relation)>
                        @if($relation === 'with_users') Con usuarios
                        @elseif($relation === 'without_users') Sin usuarios
                        @elseif($relation === 'with_orders') Con pedidos
                        @else Sin pedidos
                        @endif
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="distributors-sort">Orden</label>
            <x-ui.select id="distributors-sort" name="sort">
                @foreach($sortOptions as $sort)
                    <option value="{{ $sort }}" @selected($filters['sort'] === $sort)>
                        @if($sort === 'newest') Más recientes
                        @elseif($sort === 'oldest') Más antiguos
                        @elseif($sort === 'name_asc') Nombre A-Z
                        @elseif($sort === 'name_desc') Nombre Z-A
                        @elseif($sort === 'users_desc') Más usuarios
                        @else Más pedidos
                        @endif
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="distributors-per-page">Por página</label>
            <x-ui.select id="distributors-per-page" name="per_page">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected((int) $filters['per_page'] === $option)>{{ $option }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="xl:col-span-6 flex flex-col gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="w-full text-xs text-slate-500">
                Mostrando <strong class="text-slate-700">{{ $distributors->firstItem() ?? 0 }}-{{ $distributors->lastItem() ?? 0 }}</strong>
                de <strong class="text-slate-700">{{ number_format($distributors->total()) }}</strong> distribuidores
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">Aplicar</x-ui.button>
                <a href="{{ route('admin.distributors.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Limpiar</a>
            </div>
        </div>
    </x-ui.filter-bar>

    <section class="mt-4">
        @if($distributors->isEmpty())
            <x-ui.empty-state title="No se encontraron distribuidores" description="Ajusta filtros o crea un nuevo distribuidor para continuar.">
                <x-slot name="action">
                    <a href="{{ route('admin.distributors.create') }}" class="btn btn-primary">Crear distribuidor</a>
                </x-slot>
            </x-ui.empty-state>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Distribuidor</th>
                        <th>Estado</th>
                        <th>Usuarios</th>
                        <th>Pedidos</th>
                        <th>Creación</th>
                        <th>Actualización</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distributors as $distributor)
                        <tr>
                            <td data-label="Distribuidor" data-full="true">
                                <p class="font-medium text-slate-900">{{ $distributor->name }}</p>
                                <p class="text-xs text-slate-500">ID #{{ $distributor->id }}</p>
                            </td>
                            <td data-label="Estado"><x-ui.status-badge :status="$distributor->status" /></td>
                            <td data-label="Usuarios" class="font-medium text-slate-900">{{ number_format((int) $distributor->users_count) }}</td>
                            <td data-label="Pedidos" class="font-medium text-slate-900">{{ number_format((int) $distributor->orders_count) }}</td>
                            <td data-label="Creación">
                                <p>{{ $distributor->created_at?->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-slate-500">{{ $distributor->created_at?->diffForHumans() }}</p>
                            </td>
                            <td data-label="Actualización">{{ $distributor->updated_at?->format('d/m/Y H:i') }}</td>
                            <td data-label="Acciones" class="text-right">
                                <x-ui.action-menu>
                                    <a href="{{ route('admin.distributors.edit', $distributor) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Editar</a>

                                    <form action="{{ route('admin.distributors.status', $distributor) }}" method="POST"
                                          data-confirm="{{ $distributor->status === 'active' ? '¿Desactivar '.$distributor->name.'?' : '¿Activar '.$distributor->name.'?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $distributor->status === 'active' ? 'inactive' : 'active' }}">
                                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-slate-50">
                                            {{ $distributor->status === 'active' ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.distributors.destroy', $distributor) }}" method="POST"
                                          data-confirm="¿Eliminar {{ $distributor->name }}? Esta acción no se puede deshacer.">
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
                <x-ui.pagination :paginator="$distributors" />
            </div>
        @endif
    </section>
</x-app-layout>
