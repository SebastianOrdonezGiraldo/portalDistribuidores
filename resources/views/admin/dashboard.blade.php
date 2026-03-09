<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dashboard Operativo" subtitle="Visión consolidada de pedidos, catálogo y alertas para la operación B2B diaria.">
            <x-slot name="actions">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Ver pedidos</a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Nuevo producto</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <x-ui.kpi-card :label="$kpi['label']" :value="$kpi['value']" :hint="$kpi['hint']" :href="$kpi['href']" />
        @endforeach
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
                        <x-ui.button type="button" variant="secondary" size="sm" data-bulk-copy>Copiar CTC</x-ui.button>
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
                                        <td><input type="checkbox" class="form-checkbox" data-bulk-row value="{{ $order->oc_number }}"></td>
                                        <td class="font-semibold text-slate-900">{{ $order->oc_number }}</td>
                                        <td>
                                            <p class="font-medium text-slate-900">{{ $order->company_name }}</p>
                                            <p class="text-xs text-slate-500">{{ $order->distributor?->name ?? 'Distribuidor' }}</p>
                                        </td>
                                        <td><x-ui.status-badge :status="$order->status" /></td>
                                        <td class="font-medium">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                                        <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-right">
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
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary justify-start">Gestión de pedidos</a>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary justify-start">Catálogo de productos</a>
                    <a href="{{ route('admin.distributors.index') }}" class="btn btn-secondary justify-start">Distribuidores</a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary justify-start">Usuarios y roles</a>
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Estado de Pedidos</h2>
                <div class="mt-4 space-y-2">
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <x-ui.status-badge status="draft" />
                        <span class="text-sm font-semibold text-slate-900">{{ $orderStatusTotals['draft'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <x-ui.status-badge status="submitted" />
                        <span class="text-sm font-semibold text-slate-900">{{ $orderStatusTotals['submitted'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <x-ui.status-badge status="sending" />
                        <span class="text-sm font-semibold text-slate-900">{{ $orderStatusTotals['sending'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <x-ui.status-badge status="sent" />
                        <span class="text-sm font-semibold text-slate-900">{{ $orderStatusTotals['sent'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <x-ui.status-badge status="failed" />
                        <span class="text-sm font-semibold text-slate-900">{{ $orderStatusTotals['failed'] ?? 0 }}</span>
                    </div>
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
                <ul class="mt-4 space-y-3">
                    @forelse($recentEvents as $event)
                        <li class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-slate-900">{{ $event['title'] }}</p>
                                <x-ui.status-badge :status="$event['status']" class="shrink-0" />
                            </div>
                            <p class="mt-1 text-xs text-slate-600">{{ $event['description'] }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $event['created_at']?->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Sin actividad reciente.</li>
                    @endforelse
                </ul>
            </x-ui.card>
        </div>
    </section>
</x-app-layout>
