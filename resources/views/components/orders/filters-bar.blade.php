@props([
    'filters',
    'statusOptions',
    'activeFiltersCount' => 0,
])

<x-ui.filter-bar method="GET" action="{{ route('empresa.orders.index') }}" {{ $attributes->merge(['class' => 'grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-12']) }}>
    <input type="hidden" name="group" value="{{ $filters['group'] ?? 'all' }}">

    <div class="min-w-0 xl:col-span-3">
        <label for="orders-search" class="form-label">Buscar pedido</label>
        <div class="relative">
            <svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <x-ui.input id="orders-search" name="q" :value="$filters['q']" placeholder="Buscar CTC, empresa o contacto…" class="pl-10" />
        </div>
    </div>

    <div class="min-w-0 xl:col-span-2">
        <label for="orders-status" class="form-label">Estado específico</label>
        <x-ui.select id="orders-status" name="status">
            <option value="">Ver todos los estados</option>
            @foreach($statusOptions as $statusValue)
                <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>
                    {{ \App\Modules\Shared\Enums\OrderStatus::tryFrom($statusValue)?->label() ?? ucfirst($statusValue) }}
                </option>
            @endforeach
        </x-ui.select>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:col-span-2 xl:col-span-3">
        <div class="min-w-0">
            <label for="orders-date-from" class="form-label">Desde</label>
            <x-ui.input id="orders-date-from" type="date" name="date_from" :value="$filters['date_from']" />
        </div>

        <div class="min-w-0">
            <label for="orders-date-to" class="form-label">Hasta</label>
            <x-ui.input id="orders-date-to" type="date" name="date_to" :value="$filters['date_to']" />
        </div>
    </div>

    <div class="flex gap-2 xl:col-span-2 xl:pb-px">
        <x-ui.button type="submit" variant="secondary" size="lg" class="min-h-11 flex-1 justify-center">
            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
            Aplicar
        </x-ui.button>
        @if($activeFiltersCount > 0)
            <a href="{{ route('empresa.orders.index') }}" class="btn btn-ghost min-h-11 flex-1 justify-center whitespace-nowrap px-4" aria-label="Limpiar todos los filtros">
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
                Limpiar
            </a>
        @endif
    </div>

    <a href="{{ route('checkout.show') }}" class="btn btn-primary min-h-11 w-full whitespace-nowrap sm:self-end xl:col-span-2 xl:mb-px">
        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo pedido
    </a>
</x-ui.filter-bar>
