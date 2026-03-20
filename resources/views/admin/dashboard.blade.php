<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dashboard Operativo" subtitle="Visión consolidada de pedidos, catálogo y alertas para la operación B2B diaria.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Periodo: {{ $monthRangeLabel }}</span>
                    <span class="stat-pill">Pedidos del mes: {{ number_format($currentMonthOrders) }}</span>
                    <span class="stat-pill">
                        Última orden: {{ $latestOrderAt?->diffForHumans() ?? 'Sin registros' }}
                    </span>
                    <span class="stat-pill">
                        Catálogo: {{ $latestCatalogUpdateAt?->diffForHumans() ?? 'Sin cambios recientes' }}
                    </span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Ver pedidos</a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Nuevo producto</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @php
        $kpiAccents = ['brand', 'success', 'info', 'warning'];
    @endphp
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $index => $kpi)
            <x-ui.kpi-card
                :label="$kpi['label']"
                :value="$kpi['value']"
                :trend="$kpi['trend'] ?? null"
                :hint="$kpi['hint']"
                :href="$kpi['href']"
                :accent="$kpiAccents[$index % count($kpiAccents)]"
            />
        @endforeach
    </section>

    <section class="mt-4">
        <x-ui.card class="p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="card-title">Panorama Operativo</h2>
                    <p class="mt-1 text-xs text-slate-500">Indicadores de base para gestión administrativa y comercial.</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost !px-2 !py-1 text-xs">Actualizar vista</a>
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($operationalSummary as $item)
                    <a href="{{ $item['href'] }}" class="stat-chip">
                        <div>
                            <p class="stat-chip-label">{{ $item['label'] }}</p>
                            <p class="stat-chip-hint">{{ $item['hint'] }}</p>
                        </div>
                        <p class="stat-chip-value">{{ $item['value'] }}</p>
                    </a>
                @endforeach
            </div>
        </x-ui.card>
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-[1.8fr_1fr]">
        <div class="space-y-4">
            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <h2 class="card-title">Pedidos Recientes</h2>
                        <p class="mt-1 text-xs text-slate-500">Últimas CTC registradas por distribuidores y equipo interno.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">Seleccionados: <strong data-bulk-count>0</strong></span>
                        <x-ui.button type="button" variant="secondary" size="sm" data-bulk-copy disabled>Copiar CTC</x-ui.button>
                    </div>
                </x-slot>

                <div data-bulk-table class="p-5 pt-0">
                    @if($recentOrders->isEmpty())
                        <x-ui.empty-state title="Sin pedidos recientes" description="Cuando se registren pedidos aparecerán aquí con trazabilidad y accesos rápidos." compact />
                    @else
                        <x-ui.table>
                            <thead>
                                <tr>
                                    <th class="w-10"><input type="checkbox" class="form-checkbox" data-bulk-master></th>
                                    <th>CTC</th>
                                    <th>Cliente</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Fecha</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                    <tr>
                                        <td data-label="Seleccionar"><input type="checkbox" class="form-checkbox" data-bulk-row value="{{ $order->oc_number }}"></td>
                                        <td data-label="CTC" class="font-semibold text-slate-900">{{ $order->oc_number }}</td>
                                        <td data-label="Cliente">
                                            <p class="font-medium text-slate-900">{{ $order->company_name }}</p>
                                            <p class="text-xs text-slate-500">{{ $order->distributor?->name ?? 'Distribuidor' }}</p>
                                        </td>
                                        <td data-label="Estado"><x-ui.status-badge :status="$order->status" /></td>
                                        <td data-label="Total" class="font-medium">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                                        <td data-label="Fecha">
                                            <p>{{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                            <p class="text-xs text-slate-500">{{ $order->created_at?->diffForHumans() }}</p>
                                        </td>
                                        <td data-label="Acciones" class="text-right">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-ghost !px-2 !py-1 text-xs">Ver</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Accesos Rápidos</h2>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white hover:text-slate-900">
                        <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                        </span>
                        Gestión de pedidos
                    </a>
                    <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white hover:text-slate-900">
                        <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-brand-primary/10 text-brand-dark">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        </span>
                        Catálogo de productos
                    </a>
                    <a href="{{ route('admin.distributors.index') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white hover:text-slate-900">
                        <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        Distribuidores
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white hover:text-slate-900">
                        <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        Usuarios y roles
                    </a>
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Estado de Pedidos</h2>
                <div class="mt-4 space-y-3">
                    @foreach($statusDistribution as $item)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                            <div class="flex items-center justify-between">
                                <x-ui.status-badge :status="$item['status']" />
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $item['count'] }}
                                    <span class="ml-1 text-xs font-medium text-slate-500">({{ $item['percentage'] }}%)</span>
                                </p>
                            </div>
                            <div class="status-meter mt-2">
                                <div class="status-meter-fill {{ $item['bar_class'] }}" style="width: {{ $item['fill'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Alertas Operativas</h2>
                <div class="mt-4 space-y-2">
                    @forelse($operationalAlerts as $alert)
                        <x-ui.alert :variant="$alert['variant']" :title="$alert['title']">
                            <p>{{ $alert['description'] }}</p>
                            <p class="mt-1 text-xs font-semibold">Casos detectados: {{ $alert['count'] }}</p>
                        </x-ui.alert>
                    @empty
                        <x-ui.alert variant="success" title="Sin alertas críticas">
                            No se detectaron incidentes operativos para revisión inmediata.
                        </x-ui.alert>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Actividad Reciente</h2>
                <ul class="mt-4 space-y-0">
                    @forelse($recentEvents as $event)
                        <li class="relative flex gap-3 pb-4 last:pb-0">
                            <div class="flex flex-col items-center">
                                <span class="z-10 mt-0.5 inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>
                                </span>
                                @if(!$loop->last)
                                    <span class="mt-1 w-px flex-1 bg-slate-200"></span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1 pb-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium leading-snug text-slate-900">{{ $event['title'] }}</p>
                                    <x-ui.status-badge :status="$event['status']" class="shrink-0" />
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $event['description'] }}</p>
                                <p class="mt-1 text-xs font-medium text-slate-400">{{ $event['created_at']?->diffForHumans() }}</p>
                            </div>
                        </li>
                    @empty
                        <li>
                            <p class="text-sm text-slate-500">Sin actividad reciente.</p>
                        </li>
                    @endforelse
                </ul>
            </x-ui.card>
        </div>
    </section>
</x-app-layout>
