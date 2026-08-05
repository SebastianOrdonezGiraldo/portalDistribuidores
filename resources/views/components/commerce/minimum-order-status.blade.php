{{--
Component contract:
- Props: $decision (MinimumOrderDecision), optional $variant (cart|checkout|row).
- Renders presentation of backend minimum-order flags only; no recalculation.
--}}
@props([
    'decision',
    'variant' => 'row',
])

@php
    /** @var \App\Modules\Orders\Pricing\MinimumOrderDecision $decision */
    $moneyFromCents = fn (int $cents) => '$'.number_format(intdiv(max(0, $cents), 100), 0, ',', '.');
    $amount = max(0, $decision->minimumAmountCents);
    $evaluated = max(0, $decision->evaluatedAmountCents);
    $missing = max(0, $decision->missingAmountCents);
    $progress = $amount > 0 ? min(100, (int) round(($evaluated / $amount) * 100)) : 0;
    $reached = $decision->allowed;
@endphp

@if(! $decision->enabled)
    {{-- Regla desactivada: sin advertencia de pedido mínimo. --}}
@elseif($variant === 'checkout' && $reached)
    <div {{ $attributes->class('commerce-status-compact commerce-status-compact--ok') }}>
        <span class="commerce-status-dot commerce-status-dot--ok" aria-hidden="true"></span>
        <p class="text-sm font-semibold text-emerald-800">Pedido mínimo alcanzado.</p>
    </div>
@elseif($reached)
    <div {{ $attributes->class('commerce-status-row') }}>
        <div class="commerce-status-row__head">
            <span class="commerce-status-dot commerce-status-dot--ok" aria-hidden="true"></span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-emerald-800">Pedido mínimo alcanzado</p>
                <p class="mt-0.5 text-xs text-slate-500">Ya puedes continuar con tu compra.</p>
            </div>
        </div>
    </div>
@else
    <div {{ $attributes->class('commerce-status-row') }}>
        <div class="commerce-status-row__head">
            <span class="commerce-status-dot commerce-status-dot--warn" aria-hidden="true"></span>
            <div class="min-w-0 flex-1">
                @if($variant === 'cart-silver')
                    <p class="text-sm font-semibold text-slate-900">Completa el pedido mínimo</p>
                    <p class="mt-1 text-xs text-slate-600">
                        El pedido mínimo para Cliente Plata es de {{ $moneyFromCents($amount) }}.
                    </p>
                    <p class="mt-1 text-sm font-bold text-amber-800">
                        Te faltan {{ $moneyFromCents($missing) }} para poder continuar.
                    </p>
                @else
                    <p class="text-sm font-semibold text-slate-900">Pedido mínimo</p>
                    <p class="mt-1 text-sm font-bold text-amber-800">
                        Te faltan {{ $moneyFromCents($missing) }} para alcanzar el pedido mínimo de Cliente {{ $decision->tier?->badgeLabel() ?? 'Plata' }}.
                    </p>
                @endif
            </div>
        </div>

        <div class="commerce-progress mt-3">
            <div class="commerce-progress__track" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso hacia el pedido mínimo">
                <div class="commerce-progress__bar" style="width: {{ $progress }}%"></div>
            </div>
            <div class="commerce-progress__meta">
                <span>{{ $moneyFromCents($evaluated) }}</span>
                <span>{{ $moneyFromCents($amount) }}</span>
            </div>
        </div>
    </div>
@endif
