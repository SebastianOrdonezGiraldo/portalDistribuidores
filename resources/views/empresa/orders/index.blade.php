{{--
View contract:
- Source: App\Modules\Company\Http\Controllers\CompanyOrderController::index.
- Expects: $orders paginator, $filters, $statusOptions, $statusSummary, and $metrics for the current distributor.
- Owns: company order history, filters, status summary, and table actions.
- Notes: distributor scoping and authorization stay in CompanyOrderController/Order policy.
--}}
<x-app-layout>
    {{-- Active filter count is a local presentation summary for the page header and empty state. --}}
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
                <a href="{{ route('empresa.dashboard') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Inicio</a>
                <a href="{{ route('checkout.show') }}" class="btn btn-primary w-full justify-center sm:w-auto">Nuevo pedido</a>
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
    <x-ui.filter-bar method="GET" action="{{ route('empresa.orders.index') }}" class="mt-4 flex flex-wrap gap-3">
        <div class="w-full sm:flex-1 sm:min-w-[16rem]">
            <x-ui.input
                name="q"
                :value="$filters['q']"
                placeholder="Buscar CTC, empresa o contacto…"
            />
        </div>
        <div class="w-full sm:w-56">
            <x-ui.select name="status">
                <option value="">Todos los estados</option>
                @foreach($statusOptions as $statusValue)
                    <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>
                        {{ \App\Modules\Shared\Enums\OrderStatus::tryFrom($statusValue)?->label() ?? ucfirst($statusValue) }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>
        <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">Filtrar</x-ui.button>
        @if($activeFiltersCount > 0)
            <a href="{{ route('empresa.orders.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Limpiar</a>
        @endif
    </x-ui.filter-bar>

    {{-- Tabla --}}
    <x-ui.card class="mt-4">
        @if($orders->isEmpty())
            <div class="p-5">
                <x-ui.empty-state-panel
                    eyebrow="Historial comercial"
                    :title="$activeFiltersCount > 0 ? 'No encontramos pedidos con esos filtros' : 'Tu historial de pedidos aún está vacío'"
                    :description="$activeFiltersCount > 0 ? 'Prueba con otra búsqueda, cambia el estado o limpia los filtros para ampliar los resultados.' : 'Cuando tu empresa genere cotizaciones o pedidos, aquí podrás revisarlos con fecha, estado y trazabilidad completa.'"
                >
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M8 7V5a4 4 0 1 1 8 0v2" />
                            <path d="M6 9h12l-1 10H7z" />
                            <path d="M9.5 13.5h5" />
                            <path d="M9.5 17h3" />
                        </svg>
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
                            <td data-label="CTC" class="font-semibold text-slate-900">{{ $order->oc_number }}</td>
                            <td data-label="Empresa / Contacto" data-full="true">
                                <p class="font-medium text-slate-900">{{ $order->company_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->contact_name }}</p>
                            </td>
                            <td data-label="Estado" data-full="true">
                                <x-ui.status-badge :status="$order->status" />
                                @if($order->status->isRejected() && $order->approval_note)
                                    <p class="mt-0.5 text-xs text-red-600 italic">{{ Str::limit($order->approval_note, 60) }}</p>
                                @endif
                            </td>
                            <td data-label="Total" class="font-medium text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                            <td data-label="Generado por" class="text-sm text-slate-600">{{ $order->user?->name ?? '—' }}</td>
                            <td data-label="Fecha">
                                <p class="text-sm">{{ $order->created_at?->format('d/m/Y') }}</p>
                                <p class="text-xs text-slate-500">{{ $order->created_at?->format('H:i') }}</p>
                            </td>
                            <td data-label="Acciones" class="text-right">
                                <div class="flex w-full flex-wrap items-center justify-end gap-1">
                                    <a href="{{ route('empresa.orders.show', $order) }}" class="btn btn-ghost !px-2 !py-1 text-xs">Ver</a>
                                    @if($order->pdf_path)
                                        <a href="{{ route('empresa.orders.pdf', $order) }}" class="btn btn-ghost !px-2 !py-1 text-xs">PDF</a>
                                    @endif
                                    @if(auth()->user()?->canReorder() && $order->status->value !== 'pending_approval')
                                        <form method="POST" action="{{ route('empresa.orders.reorder', $order) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost !px-2 !py-1 text-xs" title="Volver a cotizar">↺</button>
                                        </form>
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
