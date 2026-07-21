@props([
    'groupSummary',
    'filters',
])

@php
    $groups = [
        'all' => 'Todos',
        'active' => 'Activos',
        'delivered' => 'Entregados',
        'negative' => 'Cancelados / Rechazados / Error',
    ];
    $currentGroup = filled($filters['status'] ?? null) ? null : ($filters['group'] ?? 'all');
@endphp

<div {{ $attributes->merge(['class' => 'border-b border-slate-200']) }}>
    <nav class="hidden items-end gap-1 sm:flex" aria-label="Agrupar pedidos por estado">
        @foreach($groups as $value => $label)
            @php
                $isActive = $currentGroup === $value;
                $url = request()->fullUrlWithQuery(['group' => $value, 'status' => null, 'page' => null]);
            @endphp
            <a
                href="{{ $url }}"
                @class([
                    'focus-ring -mb-px inline-flex min-h-11 items-center gap-2 rounded-t-xl border-b-2 px-4 py-2 text-sm font-semibold transition',
                    'border-brand-primary bg-cyan-50/70 text-brand-dark' => $isActive,
                    'border-transparent text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' => ! $isActive,
                ])
                @if($isActive) aria-current="page" @endif
            >
                <span>{{ $label }}</span>
                <span @class([
                    'inline-flex min-w-6 justify-center rounded-full px-1.5 py-0.5 text-[0.7rem] tabular-nums',
                    'bg-brand-primary text-white' => $isActive,
                    'bg-slate-100 text-slate-600' => ! $isActive,
                ])>{{ number_format((int) ($groupSummary[$value] ?? 0)) }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('empresa.orders.index') }}" class="pb-3 sm:hidden">
        @if(filled($filters['q'] ?? null))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
        @if(filled($filters['date_from'] ?? null))<input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">@endif
        @if(filled($filters['date_to'] ?? null))<input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">@endif
        <label for="orders-status-group" class="form-label">Vista de estados</label>
        <x-ui.select id="orders-status-group" name="group" onchange="this.form.submit()">
            @foreach($groups as $value => $label)
                <option value="{{ $value }}" @selected($currentGroup === $value)>
                    {{ $label }} ({{ number_format((int) ($groupSummary[$value] ?? 0)) }})
                </option>
            @endforeach
        </x-ui.select>
    </form>
</div>
