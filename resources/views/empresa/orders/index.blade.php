<x-app-layout>
    @php
        $activeFiltersCount = collect([$filters['q'], $filters['status']])
            ->filter(fn ($v) => filled($v))
            ->count();
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Historial de Pedidos" subtitle="Todas las cotizaciones generadas por tu empresa.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Total: {{ number_format($metrics['total']) }}</span>
                    <span class="stat-pill">Monto: ${{ number_format($metrics['amount'], 0, ',', '.') }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('empresa.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                <a href="{{ route('checkout.show') }}" class="btn btn-primary">Nuevo pedido</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    {{-- Resumen por estado --}}
    <section class="grid gap-2 sm:grid-cols-3 xl:grid-cols-5">
        @foreach($statusSummary as $statusValue => $count)
            <a href="{{ request()->fullUrlWithQuery(['status' => $statusValue, 'page' => null]) }}"
               class="stat-chip {{ $filters['status'] === $statusValue ? 'ring-2 ring-brand-600' : '' }}">
                <div>
                    <p class="stat-chip-label">
                        <x-ui.status-badge :status="$statusValue" />
                    </p>
                </div>
                <p class="stat-chip-value">{{ $count }}</p>
            </a>
        @endforeach
    </section>

    {{-- Filtros --}}
    <x-ui.filter-bar class="mt-4" :active-count="$activeFiltersCount">
        <form method="GET" action="{{ route('empresa.orders.index') }}" class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-48">
                <x-ui.input
                    name="q"
                    :value="$filters['q']"
                    placeholder="Buscar CTC, empresa o contacto…"
                />
            </div>
            <div class="w-44">
                <x-ui.select name="status">
                    <option value="">Todos los estados</option>
                    @foreach($statusOptions as $statusValue)
                        <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>
                            {{ ucfirst($statusValue) }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>
            <x-ui.button type="submit" variant="primary">Filtrar</x-ui.button>
            @if($activeFiltersCount > 0)
                <a href="{{ route('empresa.orders.index') }}" class="btn btn-secondary">Limpiar</a>
            @endif
        </form>
    </x-ui.filter-bar>

    {{-- Tabla --}}
    <x-ui.card class="mt-4">
        @if($orders->isEmpty())
            <div class="p-5">
                <x-ui.empty-state
                    title="Sin resultados"
                    description="{{ $activeFiltersCount > 0 ? 'No se encontraron pedidos con los filtros aplicados.' : 'Aún no hay pedidos registrados para tu empresa.' }}"
                    compact
                />
            </div>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th>CTC</th>
                        <th>Empresa / Contacto</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Generado por</th>
                        <th>Fecha</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td class="font-semibold text-slate-900">{{ $order->oc_number }}</td>
                            <td>
                                <p class="font-medium text-slate-900">{{ $order->company_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->contact_name }}</p>
                            </td>
                            <td><x-ui.status-badge :status="$order->status" /></td>
                            <td class="font-medium text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                            <td class="text-sm text-slate-600">{{ $order->user?->name ?? '—' }}</td>
                            <td>
                                <p class="text-sm">{{ $order->created_at?->format('d/m/Y') }}</p>
                                <p class="text-[11px] text-slate-500">{{ $order->created_at?->format('H:i') }}</p>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('empresa.orders.show', $order) }}" class="btn btn-ghost !px-2 !py-1 text-xs">Ver</a>
                                    @if($order->pdf_path)
                                        <a href="{{ route('empresa.orders.pdf', $order) }}" class="btn btn-ghost !px-2 !py-1 text-xs">PDF</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.card>
</x-app-layout>
