@props(['distributor'])

{{--
Component contract:
- Props: distributor model (with optional tierChangedBy relation).
- Slots: actions (forms/buttons for tier changes).
- Use for: admin tier summary card on edit/detail views.
--}}
@php
    use App\Modules\Shared\Enums\DistributorTier;

    $currentTier = $distributor->tier ?? DistributorTier::Silver;
@endphp

<x-ui.card {{ $attributes->merge(['class' => 'p-5']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="card-title">Nivel comercial</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $currentTier->shortDescription() }}</p>
        </div>
        <x-ui.tier-badge :tier="$currentTier" size="md" />
    </div>

    <p class="mt-4 text-sm text-slate-600">{{ $currentTier->description() }}</p>

    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <dt class="text-xs uppercase tracking-wide text-slate-500">Último cambio</dt>
            <dd class="mt-1 font-semibold text-slate-900">
                @if($distributor->tier_changed_at)
                    {{ $distributor->tier_changed_at->format('d/m/Y H:i') }}
                    <span class="block text-xs font-normal text-slate-500">{{ $distributor->tier_changed_at->diffForHumans() }}</span>
                @else
                    Sin cambios registrados
                @endif
            </dd>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <dt class="text-xs uppercase tracking-wide text-slate-500">Cambiado por</dt>
            <dd class="mt-1 font-semibold text-slate-900">
                @if($distributor->relationLoaded('tierChangedBy') && $distributor->tierChangedBy)
                    {{ $distributor->tierChangedBy->name }}
                    <span class="block text-xs font-normal text-slate-500">{{ $distributor->tierChangedBy->email }}</span>
                @elseif($distributor->tier_changed_by_id)
                    Administrador #{{ $distributor->tier_changed_by_id }}
                @else
                    Aún no aplica
                @endif
            </dd>
        </div>
    </dl>

    @if(trim($slot))
        <div class="mt-4 border-t border-slate-200 pt-4">
            {{ $slot }}
        </div>
    @endif
</x-ui.card>
