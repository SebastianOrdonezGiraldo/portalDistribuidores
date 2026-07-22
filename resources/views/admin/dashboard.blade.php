<x-app-layout>
    @php
        $ordersKpi = $kpis[0] ?? null;
        $distributorsKpi = $kpis[2] ?? null;

        $syncIssueCount = $contapymeSync['healthy']
            ? 0
            : max(
                1,
                (int) $contapymeSync['last_run_error_count'],
                (int) $contapymeSync['failed'] + (int) $contapymeSync['missing_contapyme'],
            );
        $attentionTotal = (int) $operationalAlerts->sum('count') + $syncIssueCount;
        $statusCounts = collect($orderStatusTotals);
        $dashboardOrders = $recentOrders->take(5);

        $orderTabs = [
            ['label' => 'Todos', 'status' => null, 'count' => (int) ($totals['orders'] ?? 0)],
            ['label' => 'En revisión', 'status' => 'pending_approval', 'count' => (int) $statusCounts->get('pending_approval', 0)],
            ['label' => 'Registrados', 'status' => 'submitted', 'count' => (int) $statusCounts->get('submitted', 0)],
            ['label' => 'Vendidos', 'status' => 'sold', 'count' => (int) $statusCounts->get('sold', 0)],
            ['label' => 'Despachados', 'status' => 'dispatched', 'count' => (int) $statusCounts->get('dispatched', 0)],
            ['label' => 'Cancelados', 'status' => 'cancelled', 'count' => (int) $statusCounts->get('cancelled', 0)],
        ];

        $syncedPercentage = $contapymeSync['total_managed'] > 0
            ? (int) round(($contapymeSync['synced'] / $contapymeSync['total_managed']) * 100)
            : 0;

        $executiveCards = [
            [
                'label' => 'Pedidos del mes',
                'value' => number_format($currentMonthOrders),
                'trend' => $ordersKpi['trend'] ?? null,
                'hint' => $monthRangeLabel,
                'href' => route('admin.orders.index'),
                'icon' => 'orders',
                'tone' => 'teal',
            ],
            [
                'label' => 'Distribuidores activos',
                'value' => $distributorsKpi['value'] ?? '0',
                'trend' => null,
                'hint' => 'Con acceso operativo vigente',
                'href' => route('admin.distributors.index'),
                'icon' => 'users',
                'tone' => 'teal',
            ],
            [
                'label' => 'Productos sincronizados',
                'value' => number_format($contapymeSync['synced']),
                'trend' => null,
                'hint' => $contapymeSync['total_managed'] > 0
                    ? $syncedPercentage.'% de '.number_format($contapymeSync['total_managed']).' gestionados'
                    : 'Sin productos gestionados por ContaPyme',
                'href' => route('admin.products.index'),
                'icon' => 'products',
                'tone' => 'teal',
            ],
            [
                'label' => 'Alertas operativas',
                'value' => number_format($attentionTotal),
                'trend' => null,
                'hint' => $attentionTotal > 0 ? 'Requieren atención' : 'Sin incidencias detectadas',
                'href' => route('admin.orders.index'),
                'icon' => 'alert',
                'tone' => $attentionTotal > 0 ? 'amber' : 'green',
            ],
        ];

        $dashboardAlerts = $operationalAlerts
            ->map(fn (array $alert) => array_merge($alert, ['meta' => 'Revisión actual']));

        if (! $contapymeSync['healthy']) {
            $dashboardAlerts->push([
                'variant' => 'warning',
                'title' => 'Sincronización ContaPyme',
                'description' => $contapymeSync['total_managed'] > 0
                    ? 'Revisa los productos sin sincronizar y el resultado del último intento.'
                    : 'No hay productos gestionados con un estado de sincronización verificable.',
                'count' => $syncIssueCount,
                'meta' => $contapymeSync['last_attempt_at']?->diffForHumans() ?? 'Sin ejecución registrada',
            ]);
        }

        $systemHealthy = $contapymeSync['healthy'];
        $syncFailureTotal = (int) $contapymeSync['failed'] + (int) $contapymeSync['missing_contapyme'];
    @endphp

    <div class="admin-dashboard-page">
        <nav class="admin-dashboard-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Inicio</a>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            <span>Dashboard</span>
        </nav>

        <header class="admin-dashboard-welcome">
            <div>
                <h1>Bienvenido, {{ auth()->user()->name }} <span aria-hidden="true">👋</span></h1>
                <p>Resumen ejecutivo de la operación y salud de la plataforma.</p>
            </div>
            <div class="admin-dashboard-period" aria-label="Periodo actual">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                <span>{{ now()->startOfMonth()->format('d/m/Y') }} – {{ now()->endOfMonth()->format('d/m/Y') }}</span>
            </div>
        </header>

        <div class="admin-dashboard-grid">
            <div class="min-w-0 space-y-4">
                <section class="admin-dashboard-kpis" aria-label="Indicadores principales">
                    @foreach($executiveCards as $card)
                        <x-ui.admin-metric-card
                            :label="$card['label']"
                            :value="$card['value']"
                            :trend="$card['trend']"
                            :hint="$card['hint']"
                            :href="$card['href']"
                            :icon="$card['icon']"
                            :tone="$card['tone']"
                        />
                    @endforeach
                </section>

                <section class="admin-dashboard-panel admin-orders-panel" aria-labelledby="recent-orders-title">
                    <div class="admin-orders-toolbar">
                        <div class="admin-order-tabs" aria-label="Filtrar pedidos por estado">
                            @foreach($orderTabs as $index => $tab)
                                <a
                                    href="{{ $tab['status'] ? route('admin.orders.index', ['status' => $tab['status']]) : route('admin.orders.index') }}"
                                    class="{{ $index === 0 ? 'is-active' : '' }}"
                                >
                                    {{ $tab['label'] }} <span>{{ number_format($tab['count']) }}</span>
                                </a>
                            @endforeach
                        </div>
                        <div class="admin-orders-actions">
                            <a href="{{ route('admin.orders.index') }}" class="admin-dashboard-secondary-button">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16M7 12h10M10 19h4"/></svg>
                                Filtros avanzados
                            </a>
                            <a href="{{ route('admin.orders.index') }}" class="admin-dashboard-primary-button">Ver todos los pedidos</a>
                        </div>
                    </div>

                    <h2 id="recent-orders-title" class="sr-only">Pedidos recientes</h2>

                    @if($dashboardOrders->isEmpty())
                        <div class="admin-dashboard-empty">
                            <span aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                            </span>
                            <h3>Sin pedidos recientes</h3>
                            <p>Las nuevas CTC aparecerán aquí con su estado y trazabilidad.</p>
                        </div>
                    @else
                        <div class="admin-dashboard-table-shell table-mobile-cards">
                            <table class="table-base admin-dashboard-orders-table">
                                <thead>
                                    <tr>
                                        <th>CTC</th>
                                        <th>Cliente</th>
                                        <th>Estado</th>
                                        <th>Total</th>
                                        <th>Fecha</th>
                                        <th>Actualizado</th>
                                        <th><span class="sr-only">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dashboardOrders as $order)
                                        <tr>
                                            <td data-label="CTC" class="font-semibold text-slate-900">{{ $order->oc_number }}</td>
                                            <td data-label="Cliente">
                                                <p class="font-medium text-slate-900">{{ $order->company_name }}</p>
                                                <p class="text-[0.7rem] text-slate-500">{{ $order->company_nit ?: ($order->distributor?->name ?? 'Sin NIT') }}</p>
                                            </td>
                                            <td data-label="Estado"><x-ui.status-badge :status="$order->status" /></td>
                                            <td data-label="Total" class="font-medium tabular-nums text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                                            <td data-label="Fecha">
                                                <p>{{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                                <p class="text-[0.7rem] text-slate-500">{{ $order->created_at?->diffForHumans() }}</p>
                                            </td>
                                            <td data-label="Actualizado">{{ $order->updated_at?->format('d/m/Y H:i') }}</td>
                                            <td data-label="Acciones" data-no-label="true" class="text-right">
                                                <a href="{{ route('admin.orders.show', $order) }}" class="admin-dashboard-row-action" aria-label="Ver pedido {{ $order->oc_number }}" title="Ver pedido">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="admin-orders-footer">
                            <p>Mostrando <strong>{{ $dashboardOrders->count() }}</strong> de <strong>{{ number_format($totals['orders']) }}</strong> pedidos</p>
                            <a href="{{ route('admin.orders.index') }}">Abrir gestión completa <span aria-hidden="true">→</span></a>
                        </div>
                    @endif
                </section>

                <section class="admin-dashboard-panel admin-sync-strip" aria-labelledby="sync-health-title">
                    <div class="admin-sync-identity">
                        <span class="admin-sync-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7h-5V2"/><path d="M20 2 9.5 12.5a5 5 0 0 0 7 7L21 15"/><path d="M4 17h5v5"/><path d="m4 22 10.5-10.5a5 5 0 0 0-7-7L3 9"/></svg>
                        </span>
                        <div>
                            <p class="text-xs text-slate-500">Salud de sincronización</p>
                            <h2 id="sync-health-title">ContaPyme</h2>
                        </div>
                    </div>

                    <dl class="admin-sync-metrics">
                        <div>
                            <dt>Estado</dt>
                            <dd class="{{ $systemHealthy ? 'is-good' : 'is-warning' }}">
                                <span></span>{{ $syncRunning ? 'En progreso' : ($systemHealthy ? 'Sincronizado' : 'Requiere atención') }}
                            </dd>
                        </div>
                        <div>
                            <dt>Última sincronización</dt>
                            <dd>{{ $contapymeSync['last_success_at']?->diffForHumans() ?? 'Nunca' }}</dd>
                        </div>
                        <div>
                            <dt>Registros procesados</dt>
                            <dd>{{ number_format($contapymeSync['synced']) }}</dd>
                        </div>
                        <div>
                            <dt>Fallidos</dt>
                            <dd>{{ number_format($syncFailureTotal) }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('admin.products.index') }}" class="admin-dashboard-secondary-button">Ver detalles</a>

                    @if($contapymeSync['last_run_summary'])
                        <div class="admin-sync-diagnostic">
                            <p>{{ $contapymeSync['last_run_summary'] }}</p>
                            @if($contapymeSync['last_run_id'])
                                <span>Ejecución: {{ $contapymeSync['last_run_id'] }}</span>
                            @endif
                        </div>
                    @endif
                </section>
            </div>

            <aside class="admin-dashboard-aside">
                <section class="admin-dashboard-panel admin-system-card" aria-labelledby="system-health-title">
                    <div class="admin-dashboard-panel-heading">
                        <h2 id="system-health-title">Salud del sistema</h2>
                        <span class="admin-system-state {{ $systemHealthy ? 'is-good' : 'is-warning' }}">
                            <span></span>{{ $systemHealthy ? 'Operativo' : 'Atención' }}
                        </span>
                    </div>

                    <div class="admin-system-list">
                        <div>
                            <span class="admin-system-item-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v6c0 1.7 3.1 3 7 3s7-1.3 7-3V5"/><path d="M5 11v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/></svg>
                            </span>
                            <p>Base de datos</p>
                            <strong class="text-emerald-600">OK</strong>
                        </div>
                        <div>
                            <span class="admin-system-item-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4.4 7.7 7.6 4.4 7.6-4.4"/></svg>
                            </span>
                            <p>Catálogo</p>
                            <strong>{{ number_format($totals['products']) }}</strong>
                        </div>
                        <div>
                            <span class="admin-system-item-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/><path d="m17 17 2 2 4-4"/></svg>
                            </span>
                            <p>Mapeos validados</p>
                            <strong>{{ number_format($contapymeSync['mapping_validated']) }}/{{ number_format($contapymeSync['mapping_total']) }}</strong>
                        </div>
                        <div>
                            <span class="admin-system-item-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7h-5V2"/><path d="M20 2 9.5 12.5a5 5 0 0 0 7 7L21 15"/><path d="M4 17h5v5"/></svg>
                            </span>
                            <p>Sincronización</p>
                            <strong class="{{ $systemHealthy ? 'text-emerald-600' : 'text-amber-600' }}">{{ $syncRunning ? 'En curso' : ($systemHealthy ? 'OK' : 'Revisar') }}</strong>
                        </div>
                    </div>

                    <div class="admin-system-footer">
                        <span>Verificación al cargar esta vista</span>
                        <a href="{{ route('admin.dashboard') }}" aria-label="Actualizar estado" title="Actualizar estado">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-5V2"/><path d="M20 2a9 9 0 1 0 2 9"/></svg>
                        </a>
                    </div>
                </section>

                <section class="admin-dashboard-panel admin-alerts-card" aria-labelledby="recent-alerts-title">
                    <div class="admin-dashboard-panel-heading">
                        <h2 id="recent-alerts-title">Alertas recientes</h2>
                        <a href="{{ route('admin.orders.index') }}">Ver todas</a>
                    </div>

                    <div class="admin-alerts-list">
                        @forelse($dashboardAlerts->take(3) as $alert)
                            <div class="admin-alert-item admin-alert-item--{{ $alert['variant'] }}">
                                <span class="admin-alert-icon" aria-hidden="true">
                                    @if($alert['variant'] === 'info')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                                    @endif
                                </span>
                                <div>
                                    <h3>{{ $alert['title'] }}</h3>
                                    <p>{{ $alert['description'] }}</p>
                                </div>
                                <span class="admin-alert-meta">{{ number_format($alert['count']) }} {{ $alert['count'] === 1 ? 'caso' : 'casos' }}<br>{{ $alert['meta'] }}</span>
                            </div>
                        @empty
                            <div class="admin-alert-empty">
                                <span>✓</span>
                                <div>
                                    <h3>Sin alertas críticas</h3>
                                    <p>No hay incidencias que requieran atención inmediata.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="admin-dashboard-panel admin-quick-actions" aria-labelledby="quick-actions-title">
                    <div class="admin-dashboard-panel-heading">
                        <h2 id="quick-actions-title">Acciones rápidas</h2>
                    </div>
                    <div class="admin-quick-actions-list">
                        <a href="{{ route('admin.products.create') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4.4 7.7 7.6 4.4 7.6-4.4"/></svg>
                            Nuevo producto
                        </a>
                        <a href="{{ route('admin.distributors.create') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                            Nuevo distribuidor
                        </a>
                        <a href="{{ route('admin.users.create') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/><path d="M19 5v6M22 8h-6"/></svg>
                            Nuevo usuario
                        </a>
                        <form method="POST" action="{{ route('admin.contapyme.sync') }}" data-confirm="Se encolará una sincronización completa de stock con ContaPyme. ¿Deseas continuar?">
                            @csrf
                            <button type="submit" @disabled($syncRunning)>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7h-5V2"/><path d="M20 2a9 9 0 1 0 2 9"/></svg>
                                {{ $syncRunning ? 'Sincronización en curso' : 'Sincronizar ContaPyme' }}
                            </button>
                        </form>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
