<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Panel de Empresa"
            :subtitle="$distributor->name ?? 'Mi empresa'"
        >
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Periodo: {{ $monthRangeLabel }}</span>
                    <span class="stat-pill">Pedidos del mes: {{ number_format($currentMonthOrders) }}</span>
                    @if($latestOrderAt)
                        <span class="stat-pill">Último pedido: {{ $latestOrderAt->diffForHumans() }}</span>
                    @endif
                    <span class="stat-pill">Total histórico: {{ number_format($totalOrders) }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('empresa.orders.index') }}" class="btn btn-secondary">Ver historial</a>
                <a href="{{ route('checkout.show') }}" class="btn btn-primary">Nuevo pedido</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    {{-- KPIs --}}
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <x-ui.kpi-card
                :label="$kpi['label']"
                :value="$kpi['value']"
                :trend="$kpi['trend'] ?? null"
                :hint="$kpi['hint']"
                :href="$kpi['href']"
            />
        @endforeach
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-[1.8fr_1fr]">
        <div class="space-y-4">
            {{-- Pedidos recientes --}}
            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <h2 class="card-title">Pedidos Recientes</h2>
                        <p class="mt-1 text-xs text-slate-500">Últimas cotizaciones de tu empresa.</p>
                    </div>
                    <a href="{{ route('empresa.orders.index') }}" class="btn btn-ghost !px-2 !py-1 text-xs">Ver todo</a>
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
                                    <th>Generado por</th>
                                    <th>Fecha</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                    <tr>
                                        <td class="font-semibold text-slate-900">{{ $order->oc_number }}</td>
                                        <td><x-ui.status-badge :status="$order->status" /></td>
                                        <td class="font-medium">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                                        <td class="text-sm text-slate-600">{{ $order->user?->name ?? '—' }}</td>
                                        <td>
                                            <p class="text-sm">{{ $order->created_at?->format('d/m/Y') }}</p>
                                            <p class="text-[11px] text-slate-500">{{ $order->created_at?->diffForHumans() }}</p>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('empresa.orders.show', $order) }}" class="btn btn-ghost !px-2 !py-1 text-xs">Ver</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @endif
                </div>
            </x-ui.card>

            {{-- Accesos rápidos --}}
            <x-ui.card class="p-5">
                <h2 class="card-title">Accesos Rápidos</h2>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <a href="{{ route('empresa.orders.index') }}" class="btn btn-secondary justify-start">Historial de pedidos</a>
                    <a href="{{ route('checkout.show') }}" class="btn btn-secondary justify-start">Nuevo pedido</a>
                    <a href="{{ route('catalog.index') }}" class="btn btn-secondary justify-start">Catálogo de productos</a>
                    @can('editCompany', \App\Modules\AuthAccess\Models\Distributor::class)
                        <a href="{{ route('empresa.profile.edit') }}" class="btn btn-secondary justify-start">Datos de empresa</a>
                    @endcan
                    @can('manageUsers', \App\Modules\AuthAccess\Models\Distributor::class)
                        <a href="{{ route('empresa.users.index') }}" class="btn btn-secondary justify-start">Usuarios de empresa</a>
                    @endcan
                    @if($latestOrderWithPdf)
                        <a href="{{ route('empresa.orders.pdf', $latestOrderWithPdf) }}" class="btn btn-secondary justify-start">Último PDF</a>
                    @endif
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-4">
            {{-- Distribución por estado --}}
            <x-ui.card class="p-5">
                <h2 class="card-title">Estado de Pedidos</h2>
                <div class="mt-4 space-y-3">
                    @forelse($statusDistribution as $item)
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
                    @empty
                        <p class="text-sm text-slate-500">Sin pedidos registrados.</p>
                    @endforelse
                </div>
            </x-ui.card>

            {{-- Info de empresa --}}
            <x-ui.card class="p-5">
                <h2 class="card-title">Mi Empresa</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Razón social</dt>
                        <dd class="font-medium text-slate-900 text-right">{{ $distributor->name ?? '—' }}</dd>
                    </div>
                    @if($distributor->nit)
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">NIT</dt>
                            <dd class="font-medium text-slate-900">{{ $distributor->nit }}</dd>
                        </div>
                    @endif
                    @if($distributor->city)
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Ciudad</dt>
                            <dd class="font-medium text-slate-900">{{ $distributor->city }}</dd>
                        </div>
                    @endif
                    @if($distributor->contact_email)
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Contacto</dt>
                            <dd class="font-medium text-slate-900">{{ $distributor->contact_email }}</dd>
                        </div>
                    @endif
                </dl>
                @can('editCompany', \App\Modules\AuthAccess\Models\Distributor::class)
                    <div class="mt-4">
                        <a href="{{ route('empresa.profile.edit') }}" class="btn btn-secondary w-full justify-center text-sm">Editar datos de empresa</a>
                    </div>
                @endcan
            </x-ui.card>
        </div>
    </section>
</x-app-layout>
