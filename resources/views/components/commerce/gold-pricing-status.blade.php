{{--
Component contract:
- Props: $decision (GoldPricingDecision), $goldPricingApplied (bool), optional $variant.
- Renders presentation of backend gold-threshold flags only; no recalculation.
--}}
@props([
    'decision',
    'goldPricingApplied' => false,
    'variant' => 'row',
])

@php
    /** @var \App\Modules\Orders\Pricing\GoldPricingDecision $decision */
    $moneyFromCents = fn (int $cents) => '$'.number_format(intdiv(max(0, $cents), 100), 0, ',', '.');
    $amount = max(0, $decision->thresholdAmountCents);
    $evaluated = max(0, $decision->evaluatedAmountCents);
    $missing = max(0, $decision->missingAmountCents);
    $progress = $amount > 0 ? min(100, (int) round(($evaluated / $amount) * 100)) : 0;
@endphp

@if(! $decision->ruleEnabled)
    {{-- Umbral desactivado: el contenedor padre decide el mensaje compacto. --}}
@elseif($goldPricingApplied)
    <div {{ $attributes->class('commerce-status-row') }}>
        <div class="commerce-status-row__head">
            <span class="commerce-status-dot commerce-status-dot--ok" aria-hidden="true"></span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-emerald-800">Precios Oro activados</p>
                <p class="mt-0.5 text-xs text-slate-500">Tu pedido ya utiliza precios Oro.</p>
            </div>
        </div>
    </div>
@else
    <div {{ $attributes->class('commerce-status-row') }}>
        <div class="commerce-status-row__head">
            <span class="commerce-status-dot commerce-status-dot--warn" aria-hidden="true"></span>
            <div class="min-w-0 flex-1">
                @if($variant === 'cart-allowed')
                    <p class="text-sm font-semibold text-slate-900">Precios Oro aún no activados</p>
                    <p class="mt-1 text-sm font-bold text-amber-800">
                        Agrega {{ $moneyFromCents($missing) }} más para activar tus precios Oro.
                    </p>
                    <p class="mt-1 text-xs text-slate-600">Si continúas ahora, pagarás precios Plata.</p>
                @else
                    <p class="text-sm font-semibold text-slate-900">Precios Oro</p>
                    <p class="mt-1 text-sm font-bold text-amber-800">
                        Te faltan {{ $moneyFromCents($missing) }} para activar tus precios Oro.
                    </p>
                    <p class="mt-1 text-xs text-slate-600">Por ahora, los productos se calculan con precio Plata.</p>
                @endif
            </div>
        </div>

        <div class="commerce-progress mt-3">
            <div class="commerce-progress__track" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso hacia precios Oro">
                <div class="commerce-progress__bar" style="width: {{ $progress }}%"></div>
            </div>
            <div class="commerce-progress__meta">
                <span>{{ $moneyFromCents($evaluated) }}</span>
                <span>{{ $moneyFromCents($amount) }}</span>
            </div>
        </div>
    </div>
@endif
