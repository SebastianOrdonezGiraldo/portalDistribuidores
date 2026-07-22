<x-app-layout>
    @php
        $formatMoney = static fn (float|int|string|null $value): string => '$'.number_format((float) ($value ?? 0), 0, ',', '.');
        $baseQuickFilters = request()->except(['page', 'status', 'status_group', 'relation', 'tier']);
        $totalForPct = max(1, (int) $metrics['total_distributors']);
        $pct = static fn (int $value): int => (int) round(($value / $totalForPct) * 100);

        $quickFilters = [
            ['label' => 'Todos', 'params' => $baseQuickFilters, 'active' => empty($filters['status']) && empty($filters['status_group']) && empty($filters['relation']) && empty($filters['tier']), 'count' => $metrics['total_distributors']],
            ['label' => 'Activos', 'params' => array_merge($baseQuickFilters, ['status' => 'active']), 'active' => $filters['status'] === 'active', 'count' => $metrics['active_distributors']],
            ['label' => 'Con usuarios', 'params' => array_merge($baseQuickFilters, ['relation' => 'with_users']), 'active' => $filters['relation'] === 'with_users', 'count' => $metrics['with_users']],
            ['label' => 'Con pedidos', 'params' => array_merge($baseQuickFilters, ['relation' => 'with_orders']), 'active' => $filters['relation'] === 'with_orders', 'count' => $metrics['with_orders']],
            ['label' => 'ICM Plata', 'params' => array_merge($baseQuickFilters, ['tier' => 'plata']), 'active' => $filters['tier'] === 'plata', 'count' => $metrics['silver_distributors']],
            ['label' => 'ICM Oro', 'params' => array_merge($baseQuickFilters, ['tier' => 'oro']), 'active' => $filters['tier'] === 'oro', 'count' => $metrics['gold_distributors']],
        ];
    @endphp

    <x-slot name="header">
        <x-ui.page-header
            title="Empresas distribuidoras"
            subtitle="Administra el directorio comercial, niveles ICM y cuentas de acceso vinculadas de cada empresa distribuidora."
        >
            <x-slot name="actions">
                <a href="{{ route('admin.distributors.create') }}" class="btn btn-primary w-full justify-center gap-2 sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Nueva empresa distribuidora
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card
            label="Empresas activas"
            :value="number_format($metrics['active_distributors'])"
            :hint="$pct((int) $metrics['active_distributors']).'% activas'"
            accent="success"
            compact
        >
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg>
            </x-slot>
        </x-ui.kpi-card>

        <x-ui.kpi-card
            label="ICM Plata"
            :value="number_format($metrics['silver_distributors'])"
            :hint="$pct((int) $metrics['silver_distributors']).'% del total'"
            accent="neutral"
            compact
        >
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </x-slot>
        </x-ui.kpi-card>

        <x-ui.kpi-card
            label="ICM Oro"
            :value="number_format($metrics['gold_distributors'])"
            :hint="$pct((int) $metrics['gold_distributors']).'% del total'"
            accent="warning"
            compact
        >
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12l4 7-10 13L2 10z"/><path d="M11 3 8 10l4 13 4-13-3-7"/><path d="M2 10h20"/></svg>
            </x-slot>
        </x-ui.kpi-card>

        <x-ui.kpi-card
            label="Con pedidos"
            :value="number_format($metrics['with_orders'])"
            :hint="$pct((int) $metrics['with_orders']).'% del total'"
            accent="info"
            compact
        >
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </x-slot>
        </x-ui.kpi-card>
    </section>

    <x-ui.card class="mt-4 p-4">
        <div class="flex flex-wrap items-center gap-2">
            @foreach($quickFilters as $chip)
                <a href="{{ route('admin.distributors.index', $chip['params']) }}"
                   class="filter-chip {{ $chip['active'] ? 'is-active' : '' }}">
                    {{ $chip['label'] }}
                    <span class="filter-chip__count">{{ number_format((int) $chip['count']) }}</span>
                </a>
            @endforeach
        </div>
    </x-ui.card>

    <x-ui.filter-bar method="GET" action="{{ route('admin.distributors.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <div class="xl:col-span-2">
            <label class="form-label" for="distributors-q">Buscar empresa</label>
            <x-ui.input id="distributors-q" name="q" :value="$filters['q']" placeholder="Buscar por nombre de empresa..." />
        </div>

        <div>
            <label class="form-label" for="distributors-status">Estado</label>
            <x-ui.select id="distributors-status" name="status">
                <option value="">Todos</option>
                @foreach($statusOptions as $statusKey => $statusLabel)
                    <option value="{{ $statusKey }}" @selected($filters['status'] === $statusKey)>{{ $statusLabel }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="distributors-tier">Nivel</label>
            <x-ui.select id="distributors-tier" name="tier">
                <option value="">Todos</option>
                @foreach($tierOptions as $tierKey => $tierLabel)
                    <option value="{{ $tierKey }}" @selected($filters['tier'] === $tierKey)>{{ $tierLabel }}</option>
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
            <label class="form-label" for="distributors-sort">Ordenar por</label>
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
                de <strong class="text-slate-700">{{ number_format($distributors->total()) }}</strong> empresas
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">Aplicar</x-ui.button>
                <a href="{{ route('admin.distributors.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Limpiar</a>
            </div>
        </div>
    </x-ui.filter-bar>

    <section class="mt-4">
        @if($distributors->isEmpty())
            <x-ui.empty-state title="No se encontraron empresas distribuidoras" description="Ajusta filtros o crea una nueva empresa para continuar.">
                <x-slot name="action">
                    <a href="{{ route('admin.distributors.create') }}" class="btn btn-primary">Nueva empresa distribuidora</a>
                </x-slot>
            </x-ui.empty-state>
        @else
            <div class="table-wrap distributor-index-table">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Estado</th>
                            <th>Nivel</th>
                            <th>Cuenta de acceso</th>
                            <th>Pedidos</th>
                            <th>Creación / Actualización</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    @foreach($distributors as $distributor)
                        @php
                            $statusValue = $distributor->status->value;
                            $recentOrders = $distributor->recent_orders ?? collect();
                            $latestOrderAt = $distributor->latest_order_at
                                ? \Illuminate\Support\Carbon::parse($distributor->latest_order_at)
                                : null;
                        @endphp
                        <tbody x-data="{ expanded: false }" class="align-top" x-bind:class="expanded ? 'is-expanded' : ''">
                            <tr>
                                <td data-label="Empresa" data-full="true">
                                    <p class="font-semibold text-slate-900">{{ $distributor->name }}</p>
                                    <p class="text-xs text-slate-500">ID #{{ $distributor->id }}</p>
                                </td>
                                <td data-label="Estado"><x-ui.status-badge :status="$statusValue" /></td>
                                <td data-label="Nivel"><x-ui.tier-badge :tier="$distributor->tier" size="sm" /></td>
                                <td data-label="Cuenta de acceso">
                                    @if($distributor->user)
                                        <div class="space-y-0.5">
                                            <p class="text-sm font-medium text-slate-900">{{ $distributor->user->email }}</p>
                                            <p class="text-xs text-slate-500">{{ $distributor->user->name }}</p>
                                            <a href="{{ route('admin.users.edit', $distributor->user) }}" class="distributor-account-link">
                                                {{ (int) $distributor->user_count === 1 ? '1 cuenta vinculada' : number_format((int) $distributor->user_count).' cuentas' }}
                                            </a>
                                        </div>
                                    @else
                                        <div class="space-y-0.5">
                                            <p class="text-sm text-slate-500">Sin cuenta vinculada</p>
                                            <a href="{{ route('admin.users.create', ['role' => 'distributor', 'distributor_id' => $distributor->id]) }}" class="distributor-account-link">Crear cuenta de acceso</a>
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Pedidos">
                                    <p class="font-semibold text-slate-900">{{ number_format((int) $distributor->orders_count) }}</p>
                                    <p class="text-xs text-slate-500">{{ $latestOrderAt?->format('d/m/Y') ?? 'Sin pedidos' }}</p>
                                </td>
                                <td data-label="Creación / Actualización">
                                    <p class="text-sm text-slate-900">{{ $distributor->created_at?->format('d/m/Y H:i') }}</p>
                                    <p class="text-xs text-slate-500">Act. {{ $distributor->updated_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td data-label="Acciones" class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                class="btn btn-secondary !px-3 !py-1.5 text-xs"
                                                @click="expanded = !expanded"
                                                x-bind:aria-expanded="expanded.toString()">
                                            <span x-text="expanded ? 'Ocultar detalle' : 'Ver pedidos'"></span>
                                        </button>
                                        <x-ui.action-menu>
                                            <a href="{{ route('admin.distributors.edit', $distributor) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Editar empresa</a>
                                            <a href="{{ route('admin.orders.index', ['distributor_id' => $distributor->id]) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Ver pedidos</a>
                                            <x-admin.distributors.tier-change-form :distributor="$distributor" variant="menu" />
                                            <form action="{{ route('admin.distributors.status', $distributor) }}" method="POST" data-confirm="{{ $statusValue === 'active' ? '¿Suspender '.$distributor->name.'?' : '¿Activar '.$distributor->name.'?' }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $statusValue === 'active' ? 'suspended' : 'active' }}">
                                                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-slate-50">
                                                    {{ $statusValue === 'active' ? 'Suspender' : 'Activar' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.distributors.destroy', $distributor) }}" method="POST" data-confirm="¿Eliminar {{ $distributor->name }}? Esta acción no se puede deshacer.">
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
                                    <x-admin.distributors.expanded-row
                                        :distributor="$distributor"
                                        :recent-orders="$recentOrders"
                                        :status-value="$statusValue"
                                        :format-money="$formatMoney"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>

            <div class="mt-4">
                <x-ui.pagination :paginator="$distributors" />
            </div>
        @endif
    </section>
</x-app-layout>
