<x-app-layout>
    @php
        $formatMoney = static fn (float|int|string|null $value): string => '$'.number_format((float) ($value ?? 0), 0, ',', '.');
        $formatQuantity = function (float|int|string|null $value): string {
            $number = (float) ($value ?? 0);
            $isInteger = abs($number - round($number)) < 0.00001;

            return number_format($number, $isInteger ? 0 : 2, ',', '.');
        };
        $baseQuickFilters = request()->except(['page', 'status', 'status_group', 'relation', 'tier']);
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Empresas distribuidoras" subtitle="Gestión comercial de empresas, cuentas vinculadas y actividad operativa.">
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
                <a href="{{ route('admin.distributors.create') }}" class="btn btn-primary w-full justify-center sm:w-auto">Nueva empresa distribuidora</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card label="Empresas filtradas" :value="number_format($metrics['total_distributors'])" hint="Resultado actual del listado" />
        <x-ui.kpi-card label="Activos" :value="number_format($metrics['active_distributors'])" hint="Con acceso habilitado" accent="success" />
        <x-ui.kpi-card label="Inactivos" :value="number_format($metrics['inactive_distributors'])" hint="Sin operacion comercial" accent="neutral" />
        <x-ui.kpi-card label="ICM Plata" :value="number_format($metrics['silver_distributors'])" hint="Nivel comercial Plata" accent="neutral" />
        <x-ui.kpi-card label="ICM Oro" :value="number_format($metrics['gold_distributors'])" hint="Nivel comercial Oro" accent="warning" />
        <x-ui.kpi-card label="Con Usuarios" :value="number_format($metrics['with_users'])" hint="Cuentas vinculadas en portal" accent="info" />
        <x-ui.kpi-card label="Con Pedidos" :value="number_format($metrics['with_orders'])" hint="Historial de compras registrado" accent="info" />
    </section>

    <x-ui.card class="mt-4 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.distributors.index', $baseQuickFilters) }}"
               class="btn {{ empty($filters['status']) && empty($filters['status_group']) && empty($filters['relation']) ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Todos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['total_distributors']) }}</span>
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['status' => 'active'])) }}"
               class="btn {{ $filters['status'] === 'active' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Activos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['active_distributors']) }}</span>
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['status_group' => 'non_active'])) }}"
               class="btn {{ $filters['status_group'] === 'non_active' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                No activos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['inactive_distributors']) }}</span>
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['relation' => 'with_users'])) }}"
               class="btn {{ $filters['relation'] === 'with_users' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Con usuarios
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['relation' => 'with_orders'])) }}"
               class="btn {{ $filters['relation'] === 'with_orders' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Con pedidos
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['tier' => 'plata'])) }}"
               class="btn {{ $filters['tier'] === 'plata' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                ICM Plata <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['silver_distributors']) }}</span>
            </a>
            <a href="{{ route('admin.distributors.index', array_merge($baseQuickFilters, ['tier' => 'oro'])) }}"
               class="btn {{ $filters['tier'] === 'oro' ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                ICM Oro <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['gold_distributors']) }}</span>
            </a>
        </div>
    </x-ui.card>

    <x-ui.filter-bar method="GET" action="{{ route('admin.distributors.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-7">
        <div class="xl:col-span-2">
            <label class="form-label" for="distributors-q">Buscar</label>
            <x-ui.input id="distributors-q" name="q" :value="$filters['q']" placeholder="Nombre del distribuidor" />
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
            <label class="form-label" for="distributors-relation">Relacion</label>
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
                        @if($sort === 'newest') Mas recientes
                        @elseif($sort === 'oldest') Mas antiguos
                        @elseif($sort === 'name_asc') Nombre A-Z
                        @elseif($sort === 'name_desc') Nombre Z-A
                        @elseif($sort === 'users_desc') Mas usuarios
                        @else Mas pedidos
                        @endif
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="distributors-per-page">Por pagina</label>
            <x-ui.select id="distributors-per-page" name="per_page">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected((int) $filters['per_page'] === $option)>{{ $option }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="xl:col-span-7 flex flex-col gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
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
            <x-ui.empty-state title="No se encontraron empresas distribuidoras" description="Ajusta filtros o crea una nueva empresa para continuar.">
                <x-slot name="action">
                    <a href="{{ route('admin.distributors.create') }}" class="btn btn-primary">Nueva empresa distribuidora</a>
                </x-slot>
            </x-ui.empty-state>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Estado</th>
                        <th>Nivel</th>
                        <th>Cuenta de acceso</th>
                        <th>Pedidos</th>
                        <th>Creacion</th>
                        <th>Actualizacion</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                @foreach($distributors as $distributor)
                    @php
                        $statusValue = $distributor->status->value;
                        $recentOrders = $distributor->recent_orders ?? collect();
                        $statusSummary = collect($distributor->order_status_summary ?? [])->filter(fn ($count) => (int) $count > 0);
                    @endphp
                    <tbody x-data="{ expanded: false }" class="align-top">
                        <tr>
                            <td data-label="Empresa" data-full="true">
                                <p class="font-medium text-slate-900">{{ $distributor->name }}</p>
                                <p class="text-xs text-slate-500">ID #{{ $distributor->id }}</p>
                            </td>
                            <td data-label="Estado"><x-ui.status-badge :status="$statusValue" /></td>
                            <td data-label="Nivel"><x-ui.tier-badge :tier="$distributor->tier" size="sm" /></td>
                            <td data-label="Cuenta de acceso">
                                @if($distributor->user)
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-slate-900">{{ $distributor->user->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $distributor->user->email }}</p>
                                        <a href="{{ route('admin.users.edit', $distributor->user) }}" class="inline-flex text-xs font-medium text-brand-primary hover:underline">Editar cuenta</a>
                                    </div>
                                @else
                                    <div class="space-y-1">
                                        <p class="text-sm text-slate-500">Sin cuenta</p>
                                        <a href="{{ route('admin.users.create', ['role' => 'distributor', 'distributor_id' => $distributor->id]) }}" class="inline-flex text-xs font-medium text-brand-primary hover:underline">Crear cuenta</a>
                                    </div>
                                @endif
                            </td>
                            <td data-label="Pedidos" class="font-medium text-slate-900">{{ number_format((int) $distributor->orders_count) }}</td>
                            <td data-label="Creacion">
                                <p>{{ $distributor->created_at?->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-slate-500">{{ $distributor->created_at?->diffForHumans() }}</p>
                            </td>
                            <td data-label="Actualizacion">{{ $distributor->updated_at?->format('d/m/Y H:i') }}</td>
                            <td data-label="Acciones" class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="btn btn-secondary !px-3 !py-1.5 text-xs" @click="expanded = !expanded" x-bind:aria-expanded="expanded.toString()">
                                        <span x-text="expanded ? 'Ocultar pedidos' : 'Ver pedidos'"></span>
                                    </button>
                                    <x-ui.action-menu>
                                        <a href="{{ route('admin.distributors.edit', $distributor) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Editar</a>
                                        <x-admin.distributors.tier-change-form :distributor="$distributor" variant="menu" />
                                        <form action="{{ route('admin.distributors.status', $distributor) }}" method="POST" data-confirm="{{ $statusValue === 'active' ? 'Suspender '.$distributor->name.'?' : 'Activar '.$distributor->name.'?' }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $statusValue === 'active' ? 'suspended' : 'active' }}">
                                            <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-slate-50">
                                                {{ $statusValue === 'active' ? 'Suspender' : 'Activar' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.distributors.destroy', $distributor) }}" method="POST" data-confirm="Eliminar {{ $distributor->name }}? Esta accion no se puede deshacer.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-red-700 hover:bg-red-50">Eliminar</button>
                                        </form>
                                    </x-ui.action-menu>
                                </div>
                            </td>
                        </tr>
                        <tr x-show="expanded" x-cloak>
                            <td colspan="8" class="bg-slate-50/70 px-0 py-0">
                                <div class="border-t border-slate-200 bg-slate-50 px-4 py-5 sm:px-5">
                                    <div class="grid gap-4 xl:grid-cols-[1.5fr_1fr]">
                                        <div class="space-y-4">
                                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                                <div class="flex flex-wrap items-start justify-between gap-3">
                                                    <div>
                                                        <h3 class="text-base font-semibold text-slate-900">Contexto comercial</h3>
                                                        <p class="mt-1 text-sm text-slate-600">Resumen operativo del distribuidor y sus pedidos recientes.</p>
                                                    </div>
                                                    <x-ui.status-badge :status="$statusValue" />
                                                </div>
                                                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                        <p class="text-xs uppercase tracking-wide text-slate-500">Ultimo pedido</p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $distributor->latest_order_at ? \Illuminate\Support\Carbon::parse($distributor->latest_order_at)->format('d/m/Y H:i') : 'Sin pedidos' }}</p>
                                                    </div>
                                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                        <p class="text-xs uppercase tracking-wide text-slate-500">Monto acumulado</p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $formatMoney($distributor->orders_total_amount) }}</p>
                                                    </div>
                                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                        <p class="text-xs uppercase tracking-wide text-slate-500">Pedidos</p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format((int) $distributor->orders_count) }}</p>
                                                    </div>
                                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                        <p class="text-xs uppercase tracking-wide text-slate-500">Usuario</p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ (int) $distributor->user_count ? 'Sí' : 'No' }}</p>
                                                    </div>
                                                </div>
                                                <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                        <dt class="text-xs uppercase tracking-wide text-slate-500">Contacto</dt>
                                                        <dd class="mt-1 font-semibold text-slate-900">{{ $distributor->contact_name ?: 'Sin contacto principal' }}</dd>
                                                        <p class="text-xs text-slate-500">{{ $distributor->contact_email ?: 'Sin correo registrado' }}</p>
                                                        <p class="text-xs text-slate-500">{{ $distributor->phone ?: 'Sin telefono' }}</p>
                                                    </div>
                                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                        <dt class="text-xs uppercase tracking-wide text-slate-500">Ubicacion</dt>
                                                        <dd class="mt-1 font-semibold text-slate-900">{{ $distributor->city ?: 'Sin ciudad' }}</dd>
                                                        <p class="text-xs text-slate-500">{{ $distributor->address ?: 'Sin direccion registrada' }}</p>
                                                        <p class="text-xs text-slate-500">NIT: {{ $distributor->nit ?: 'Sin NIT' }}</p>
                                                    </div>
                                                </dl>
                                            </div>
                                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div>
                                                        <h3 class="text-base font-semibold text-slate-900">Pedidos recientes</h3>
                                                        <p class="mt-1 text-sm text-slate-600">Ultimos {{ $recentOrdersLimit }} pedidos del distribuidor para revision rapida.</p>
                                                    </div>
                                                    <a href="{{ route('admin.orders.index', ['distributor_id' => $distributor->id]) }}" class="btn btn-secondary !px-3 !py-1.5 text-xs">Ver historial completo</a>
                                                </div>
                                                @if($recentOrders->isEmpty())
                                                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                                                        <p class="text-sm font-semibold text-slate-900">Sin pedidos recientes</p>
                                                        <p class="mt-1 text-sm text-slate-500">Este distribuidor todavia no registra pedidos en el portal.</p>
                                                    </div>
                                                @else
                                                    <div class="mt-4 space-y-3">
                                                        @foreach($recentOrders as $order)
                                                            @php
                                                                $visibleItems = $order->items->take(3);
                                                                $hiddenItemsCount = max(0, $order->items_count - $visibleItems->count());
                                                            @endphp
                                                            <div x-data="{ open: false }" class="rounded-2xl border border-slate-200 bg-slate-50/80">
                                                                <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-start lg:justify-between">
                                                                    <div class="space-y-3">
                                                                        <div class="flex flex-wrap items-center gap-2">
                                                                            <p class="text-sm font-semibold text-slate-900">{{ $order->oc_number }}</p>
                                                                            <x-ui.status-badge :status="$order->status" />
                                                                            @if($order->pdf_path)
                                                                                <x-ui.badge variant="success">PDF listo</x-ui.badge>
                                                                            @else
                                                                                <x-ui.badge variant="warning">PDF pendiente</x-ui.badge>
                                                                            @endif
                                                                        </div>
                                                                        <div class="grid gap-2 text-sm text-slate-600 sm:grid-cols-2 xl:grid-cols-4">
                                                                            <p><span class="font-medium text-slate-900">Fecha:</span> {{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                                                            <p><span class="font-medium text-slate-900">Monto:</span> {{ $formatMoney($order->total_amount) }}</p>
                                                                            <p><span class="font-medium text-slate-900">Usuario:</span> {{ $order->user?->email ?? 'Sin usuario' }}</p>
                                                                            <p><span class="font-medium text-slate-900">Items:</span> {{ number_format((int) $order->items_count) }}</p>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex flex-wrap gap-2 lg:justify-end">
                                                                        <button type="button" class="btn btn-secondary !px-3 !py-1.5 text-xs" @click="open = !open">
                                                                            <span x-text="open ? 'Ocultar mini detalle' : 'Ver mini detalle'"></span>
                                                                        </button>
                                                                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary !px-3 !py-1.5 text-xs">Ver detalle</a>
                                                                        <a href="{{ route('admin.orders.pdf', $order) }}" class="btn btn-primary !px-3 !py-1.5 text-xs">Descargar PDF</a>
                                                                    </div>
                                                                </div>
                                                                <div x-show="open" x-cloak class="border-t border-slate-200 bg-white p-4">
                                                                    <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
                                                                        <div class="space-y-4">
                                                                            <div class="grid gap-3 md:grid-cols-2">
                                                                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                                                    <p class="text-xs uppercase tracking-wide text-slate-500">Empresa</p>
                                                                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $order->company_name }}</p>
                                                                                    <p class="text-xs text-slate-500">NIT: {{ $order->company_nit ?: 'Sin NIT' }}</p>
                                                                                </div>
                                                                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                                                    <p class="text-xs uppercase tracking-wide text-slate-500">Contacto</p>
                                                                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $order->contact_name }}</p>
                                                                                    <p class="text-xs text-slate-500">{{ $order->contact_email ?: 'Sin correo' }}</p>
                                                                                    <p class="text-xs text-slate-500">{{ $order->phone ?: 'Sin telefono' }}</p>
                                                                                </div>
                                                                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 md:col-span-2">
                                                                                    <p class="text-xs uppercase tracking-wide text-slate-500">Direccion y ciudad</p>
                                                                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                                                                        {{ $order->company_address ?: 'Sin direccion' }} - {{ $order->city ?: 'Sin ciudad' }}@if($order->department) ({{ $order->department }}) @endif
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                                                <div class="flex items-center justify-between gap-2">
                                                                                    <p class="text-xs uppercase tracking-wide text-slate-500">Items visibles</p>
                                                                                    <p class="text-xs text-slate-500">{{ $visibleItems->count() }}/{{ number_format((int) $order->items_count) }}</p>
                                                                                </div>
                                                                                @if($visibleItems->isEmpty())
                                                                                    <p class="mt-3 text-sm text-slate-500">Este pedido no tiene items registrados.</p>
                                                                                @else
                                                                                    <div class="mt-3 space-y-2">
                                                                                        @foreach($visibleItems as $item)
                                                                                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                                                                    <div>
                                                                                                        <p class="text-sm font-semibold text-slate-900">{{ $item->product_name_snapshot }}</p>
                                                                                                        <p class="text-xs text-slate-500">{{ $item->sku_snapshot }}</p>
                                                                                                        @if($item->variant_value_snapshot)
                                                                                                            <p class="text-xs text-slate-500">{{ $item->variant_attribute_snapshot ?? 'Variante' }}: {{ $item->variant_value_snapshot }}</p>
                                                                                                        @endif
                                                                                                    </div>
                                                                                                    <div class="text-sm text-slate-600 sm:text-right">
                                                                                                        <p>{{ $formatQuantity($item->qty) }} {{ $item->unit_label }}</p>
                                                                                                        <p>{{ $formatMoney($item->subtotal) }}</p>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                @endif
                                                                                @if($hiddenItemsCount > 0)
                                                                                    <p class="mt-3 text-xs text-slate-500">Hay {{ number_format($hiddenItemsCount) }} item(s) adicional(es). Revisa el pedido completo para ver el detalle total.</p>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <div class="space-y-4">
                                                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                                                <p class="text-xs uppercase tracking-wide text-slate-500">Observaciones</p>
                                                                                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $order->notes ?: 'Sin observaciones registradas.' }}</p>
                                                                            </div>
                                                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                                                <p class="text-xs uppercase tracking-wide text-slate-500">Acciones rapidas</p>
                                                                                <div class="mt-3 flex flex-col gap-2">
                                                                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary w-full justify-center">Ver detalle</a>
                                                                                    <a href="{{ route('admin.orders.pdf', $order) }}" class="btn btn-primary w-full justify-center">Descargar PDF</a>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="space-y-4">
                                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                                <h3 class="text-base font-semibold text-slate-900">Distribucion por estado</h3>
                                                <div class="mt-4 space-y-2">
                                                    @forelse($statusSummary as $status => $count)
                                                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                                            <div class="flex items-center gap-2">
                                                                <x-ui.status-badge :status="$status" />
                                                            </div>
                                                            <span class="text-sm font-semibold text-slate-900">{{ number_format((int) $count) }}</span>
                                                        </div>
                                                    @empty
                                                        <p class="text-sm text-slate-500">Aun no hay estados para mostrar.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                                <h3 class="text-base font-semibold text-slate-900">Lectura rapida</h3>
                                                <ul class="mt-4 space-y-2 text-sm">
                                                    <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                                        <span>Contacto comercial</span>
                                                        <x-ui.badge :variant="$distributor->contact_email ? 'success' : 'warning'">{{ $distributor->contact_email ? 'OK' : 'Falta' }}</x-ui.badge>
                                                    </li>
                                                    <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                                        <span>Ubicacion completa</span>
                                                        <x-ui.badge :variant="($distributor->city && $distributor->address) ? 'success' : 'warning'">{{ ($distributor->city && $distributor->address) ? 'OK' : 'Incompleta' }}</x-ui.badge>
                                                    </li>
                                                    <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                                        <span>Actividad reciente</span>
                                                        <x-ui.badge :variant="$recentOrders->isNotEmpty() ? 'success' : 'warning'">{{ $recentOrders->isNotEmpty() ? 'Activa' : 'Sin pedidos' }}</x-ui.badge>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @endforeach
            </x-ui.table>

            <div class="mt-4">
                <x-ui.pagination :paginator="$distributors" />
            </div>
        @endif
    </section>
</x-app-layout>
