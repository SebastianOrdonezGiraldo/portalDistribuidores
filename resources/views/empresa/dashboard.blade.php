{{--
View contract:
- Source: App\Modules\Company\Http\Controllers\CompanyDashboardController::__invoke.
- Expects: $distributor, $kpis, $statusDistribution, $recentOrders, $latestOrderWithPdf, and monthly summary values.
- Owns: distributor dashboard presentation and shortcuts into order history/checkout.
- Notes: KPI aggregation and distributor scoping stay in CompanyDashboardController.
--}}
<x-app-layout>
    @php
        $firstName = \Illuminate\Support\Str::before(auth()->user()?->name ?? 'Distribuidor', ' ');
        $activeStatusDistribution = collect($statusDistribution)
            ->filter(fn (array $item) => $item['count'] > 0)
            ->sortByDesc('count')
            ->values();
        $kpiAccents = ['brand', 'success', 'info', 'warning'];
    @endphp

    <x-slot name="header">
        <section class="admin-exec-hero">
            <div class="admin-exec-hero-main">
                <p class="admin-exec-hero-eyebrow">Resumen de tu empresa</p>
                <h1 class="admin-exec-hero-title">Hola, {{ $firstName }} <span aria-hidden="true">👋</span></h1>
                <p class="admin-exec-hero-subtitle">Consulta la actividad reciente de {{ $distributor->name ?? 'tu empresa' }} y continúa con tus tareas principales.</p>
                <div class="admin-exec-hero-meta">
                    <span class="stat-pill">{{ $monthRangeLabel }}</span>
                    <span class="stat-pill">{{ $latestOrderAt ? 'Último pedido '.$latestOrderAt->diffForHumans() : 'Aún no hay pedidos' }}</span>
                </div>
            </div>
            <div class="admin-exec-hero-actions">
                <a href="{{ route('empresa.orders.index') }}" class="btn btn-secondary">Mis pedidos</a>
                @if(auth()->user()?->canCreateOrders())
                    <a href="{{ route('checkout.show') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Nuevo pedido
                    </a>
                @endif
            </div>
        </section>
    </x-slot>

    <section aria-labelledby="dashboard-summary-title">
        <div class="mb-3 flex items-end justify-between gap-4">
            <div>
                <p class="admin-section-eyebrow">Actividad comercial</p>
                <h2 id="dashboard-summary-title" class="admin-section-title">Resumen del mes</h2>
            </div>
            <a href="{{ route('empresa.orders.index') }}" class="hidden text-xs font-semibold text-brand-dark hover:text-brand-primary sm:inline">Ver detalle →</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $index => $kpi)
            <x-ui.kpi-card
                :label="$kpi['label']"
                :value="$kpi['value']"
                :trend="$kpi['trend'] ?? null"
                :hint="$kpi['hint']"
                :href="$kpi['href']"
                :accent="$kpiAccents[$index] ?? 'neutral'"
                compact
            />
        @endforeach
        </div>
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.75fr)_minmax(17rem,0.75fr)]">
        <div class="space-y-4">
            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <p class="admin-section-eyebrow">Seguimiento</p>
                        <h2 class="card-title mt-1">Pedidos recientes</h2>
                    </div>
                    <a href="{{ route('empresa.orders.index') }}" class="btn btn-ghost !px-2 !py-1 text-xs">Ver todos</a>
                </x-slot>

                <div class="p-5 pt-0">
                    @if($recentOrders->isEmpty())
                        <x-ui.empty-state
                            title="Sin pedidos aún"
                            description="Cuando generes cotizaciones aparecerán aquí."
                            compact
                        />
                    @else
                        <x-ui.table>
                            <thead>
                                <tr>
                                    <th>CTC</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Fecha</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                    <tr>
                                        <td data-label="CTC" class="font-semibold text-slate-900">{{ $order->oc_number }}</td>
                                        <td data-label="Estado"><x-ui.status-badge :status="$order->status" /></td>
                                        <td data-label="Total" class="font-medium">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                                        <td data-label="Fecha">
                                            <p class="text-sm">{{ $order->created_at?->format('d/m/Y') }}</p>
                                            <p class="text-xs text-slate-500">{{ $order->created_at?->diffForHumans() }}</p>
                                        </td>
                                        <td data-label="Acciones" class="text-right">
                                            <a href="{{ route('empresa.orders.show', $order) }}" class="btn btn-ghost !px-2 !py-1 text-xs">Ver</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="p-5" aria-labelledby="quick-links-title">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="admin-section-eyebrow">Atajos</p>
                        <h2 id="quick-links-title" class="card-title mt-1">Accesos rápidos</h2>
                    </div>
                    <span class="text-xs text-slate-500">Todo a un clic</span>
                </div>
                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ route('catalog.index') }}" class="group flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-3 transition hover:-translate-y-0.5 hover:border-brand-primary/30 hover:bg-white hover:shadow-sm">
                        <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-brand-primary/10 text-brand-dark">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        </span>
                        <span class="min-w-0"><strong class="block truncate text-sm font-semibold text-slate-800">Catálogo</strong><small class="block truncate text-xs text-slate-500">Explorar productos</small></span>
                        <span class="ml-auto text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-brand-dark">→</span>
                    </a>
                    @if(auth()->user()?->canCreateOrders())
                        <a href="{{ route('checkout.show') }}" class="group flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-3 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-white hover:shadow-sm">
                            <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                            </span>
                            <span class="min-w-0"><strong class="block truncate text-sm font-semibold text-slate-800">Nuevo pedido</strong><small class="block truncate text-xs text-slate-500">Crear cotización</small></span>
                            <span class="ml-auto text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-emerald-600">→</span>
                        </a>
                    @endif
                    <a href="{{ route('empresa.orders.index') }}" class="group flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-3 transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-white hover:shadow-sm">
                        <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                        </span>
                        <span class="min-w-0"><strong class="block truncate text-sm font-semibold text-slate-800">Mis pedidos</strong><small class="block truncate text-xs text-slate-500">Consultar historial</small></span>
                        <span class="ml-auto text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-sky-600">→</span>
                    </a>
                    @can('manageBranches')
                        <a href="{{ route('empresa.branches.index') }}" class="group flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-3 transition hover:-translate-y-0.5 hover:border-violet-200 hover:bg-white hover:shadow-sm">
                            <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </span>
                            <span class="min-w-0"><strong class="block truncate text-sm font-semibold text-slate-800">Sucursales</strong><small class="block truncate text-xs text-slate-500">Gestionar sedes</small></span>
                            <span class="ml-auto text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-violet-600">→</span>
                        </a>
                    @endcan
                    @can('editCompany', \App\Modules\AuthAccess\Models\Distributor::class)
                        <a href="{{ route('empresa.profile.edit') }}" class="group flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-3 transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-sm">
                            <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            </span>
                            <span class="min-w-0"><strong class="block truncate text-sm font-semibold text-slate-800">Datos de empresa</strong><small class="block truncate text-xs text-slate-500">Información y contacto</small></span>
                            <span class="ml-auto text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-slate-700">→</span>
                        </a>
                    @endcan
                    @if($latestOrderWithPdf)
                        <a href="{{ route('empresa.orders.pdf', $latestOrderWithPdf) }}" class="group flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-3 transition hover:-translate-y-0.5 hover:border-red-200 hover:bg-white hover:shadow-sm">
                            <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                            </span>
                            <span class="min-w-0"><strong class="block truncate text-sm font-semibold text-slate-800">Último PDF</strong><small class="block truncate text-xs text-slate-500">Descargar cotización</small></span>
                            <span class="ml-auto text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-red-600">→</span>
                        </a>
                    @endif
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-4">
            <x-ui.card class="p-5">
                <div>
                    <p class="admin-section-eyebrow">Distribución</p>
                    <h2 class="card-title mt-1">Estado de pedidos</h2>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($activeStatusDistribution as $item)
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <x-ui.status-badge :status="$item['status']" />
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $item['count'] }}
                                    <span class="ml-1 text-xs font-medium text-slate-500">{{ $item['percentage'] }}%</span>
                                </p>
                            </div>
                            <div class="status-meter mt-2">
                                <div class="status-meter-fill {{ $item['bar_class'] }}" style="width: {{ $item['fill'] }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center">
                            <p class="text-sm font-medium text-slate-700">Sin pedidos registrados</p>
                            <p class="mt-1 text-xs text-slate-500">Los estados aparecerán cuando generes tu primer pedido.</p>
                        </div>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-brand-primary/10 text-brand-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h1M14 10h1M9 14h1M14 14h1M10 21v-3h4v3"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="admin-section-eyebrow">Tu cuenta</p>
                        <h2 class="truncate text-sm font-semibold text-slate-900">{{ $distributor->name ?? 'Mi empresa' }}</h2>
                    </div>
                </div>
                <dl class="mt-4 divide-y divide-slate-100 text-sm">
                    @if($distributor->nit)
                    <div class="flex items-center justify-between gap-3 py-2">
                        <dt class="text-slate-500">NIT</dt>
                        <dd class="font-medium text-slate-900">{{ $distributor->nit }}</dd>
                    </div>
                    @endif
                    @if($distributor->city)
                    <div class="flex items-center justify-between gap-3 py-2">
                        <dt class="text-slate-500">Ciudad</dt>
                        <dd class="font-medium text-slate-900">{{ $distributor->city }}</dd>
                    </div>
                    @endif
                    @if($distributor->contact_email)
                    <div class="py-2">
                        <dt class="text-slate-500">Contacto</dt>
                        <dd class="mt-1 truncate font-medium text-slate-900" title="{{ $distributor->contact_email }}">{{ $distributor->contact_email }}</dd>
                    </div>
                    @endif
                    @if(!$distributor->nit && !$distributor->city && !$distributor->contact_email)
                    <div class="flex items-center justify-between gap-3 py-2">
                        <dt class="text-slate-500">Razón social</dt>
                        <dd class="truncate text-right font-medium text-slate-900">{{ $distributor->name ?? '—' }}</dd>
                    </div>
                    @endif
                </dl>
                @can('editCompany', \App\Modules\AuthAccess\Models\Distributor::class)
                    <a href="{{ route('empresa.profile.edit') }}" class="btn btn-secondary mt-4 w-full justify-center text-sm">Editar información</a>
                @endcan
            </x-ui.card>
        </div>
    </section>
</x-app-layout>
