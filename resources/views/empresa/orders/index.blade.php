{{--
View contract:
- Source: App\Modules\Company\Http\Controllers\CompanyOrderController::index.
- Expects: $orders paginator, $filters, $statusOptions, $statusSummary, $groupSummary and $metrics.
- Owns: composition of reusable order-history presentation components.
- Notes: distributor scoping, status values and transition rules remain in backend policies/services.
--}}
<x-app-layout>
    @php
        $activeFiltersCount = collect([
            $filters['q'],
            $filters['status'],
            $filters['date_from'],
            $filters['date_to'],
        ])->filter(fn ($value) => filled($value))->count()
            + (($filters['group'] ?? 'all') !== 'all' ? 1 : 0);
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Mis pedidos" subtitle="Consulta cotizaciones, estados y trazabilidad en un solo lugar.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">{{ number_format($metrics['total']) }} pedidos</span>
                    <span class="stat-pill">${{ number_format($metrics['amount'], 0, ',', '.') }} acumulado</span>
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-orders.filters-bar
        :filters="$filters"
        :status-options="$statusOptions"
        :active-filters-count="$activeFiltersCount"
    />

    <x-ui.card padding="none" class="mt-4 overflow-hidden">
        <div class="px-4 pt-4 sm:px-5 sm:pt-5">
            <x-orders.status-tabs :group-summary="$groupSummary" :filters="$filters" />
        </div>

        @if($orders->isEmpty())
            <div class="p-4 sm:p-5">
                <x-ui.empty-state-panel
                    class="border-0 shadow-none"
                    eyebrow="Historial comercial"
                    :title="$activeFiltersCount > 0 ? 'No encontramos pedidos con esos filtros' : 'Tu historial de pedidos aún está vacío'"
                    :description="$activeFiltersCount > 0 ? 'Prueba con otra búsqueda, cambia el estado o amplía el rango de fechas.' : 'Cuando generes cotizaciones o pedidos, aquí podrás revisar su estado y trazabilidad completa.'"
                >
                    <x-slot name="icon">
                        <svg aria-hidden="true" class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 7V5a4 4 0 1 1 8 0v2"/><path d="M6 9h12l-1 10H7z"/><path d="M9.5 13.5h5"/><path d="M9.5 17h3"/></svg>
                    </x-slot>
                    <x-slot name="action">
                        @if($activeFiltersCount > 0)
                            <a href="{{ route('empresa.orders.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('checkout.show') }}" class="btn btn-primary">Crear primer pedido</a>
                        @endif
                    </x-slot>
                </x-ui.empty-state-panel>
            </div>
        @else
            <div class="space-y-3 bg-slate-50/60 p-3 sm:p-5" aria-live="polite">
                @foreach($orders as $order)
                    <x-orders.card :order="$order" />
                @endforeach
            </div>

            <div class="border-t border-slate-200 px-4 py-4 sm:px-5">
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.card>
</x-app-layout>
