{{--
Component contract:
- Props: $pricing (OrderPricingResult|null), $tier (DistributorTier), optional $context (cart|checkout).
- Combines minimum-order and gold-pricing status using backend flags only.
--}}
@props([
    'pricing' => null,
    'tier' => null,
    'context' => 'cart',
])

@php
    use App\Modules\Shared\Enums\DistributorTier;

    /** @var \App\Modules\Orders\Pricing\OrderPricingResult|null $pricing */
    $tier = $tier ?? DistributorTier::Silver;
    $isGold = $tier === DistributorTier::Gold;
    $moneyFromCents = fn (int $cents) => '$'.number_format(intdiv(max(0, $cents), 100), 0, ',', '.');

    $min = $pricing?->minimumOrderDecision;
    $gold = $pricing?->goldPricingDecision;
    $checkoutAllowed = $pricing?->checkoutAllowed() ?? true;
    $goldApplied = $pricing?->goldPricingApplied ?? false;
    $minEnabled = (bool) ($min?->enabled);
    $goldEnabled = (bool) ($gold?->ruleEnabled);
    $minReached = (bool) ($min?->allowed);
    $goldReached = $goldApplied;

    $showBlock = false;
    $mode = 'hidden';

    if ($pricing !== null) {
        if ($isGold) {
            if (! $minEnabled && ! $goldEnabled) {
                $mode = 'gold-free';
                $showBlock = $context === 'cart';
            } elseif ($minReached && $goldReached) {
                $mode = 'gold-both-ok';
                $showBlock = true;
            } elseif ($minReached && $goldEnabled && ! $goldReached) {
                $mode = 'gold-min-ok-threshold-pending';
                $showBlock = true;
            } elseif (! $minReached && $goldReached) {
                $mode = 'gold-threshold-ok-min-pending';
                $showBlock = true;
            } elseif (! $minReached || ($goldEnabled && ! $goldReached)) {
                $mode = 'gold-pending';
                $showBlock = true;
            }
        } else {
            if ($minEnabled && ! $minReached) {
                $mode = 'silver-blocked';
                $showBlock = true;
            } elseif ($minEnabled && $minReached) {
                $mode = 'silver-ok';
                $showBlock = $context === 'cart';
            }
        }

        if ($context === 'checkout') {
            if (! $checkoutAllowed) {
                $mode = 'checkout-blocked';
                $showBlock = true;
            } elseif ($isGold && $goldApplied) {
                $mode = 'checkout-gold-applied';
                $showBlock = true;
            } elseif ($isGold && $goldEnabled && ! $goldApplied) {
                $mode = 'checkout-gold-pays-silver';
                $showBlock = true;
            } elseif (! $isGold && $minEnabled && $minReached) {
                $mode = 'checkout-silver-ok';
                $showBlock = true;
            } else {
                $showBlock = false;
            }
        }
    }
@endphp

@if($pricing !== null && $showBlock && $min !== null && $gold !== null)
    @if($context === 'checkout' && $mode === 'checkout-blocked')
        <div {{ $attributes->class('commerce-status-panel commerce-status-panel--blocked') }}>
            <p class="text-base font-semibold text-slate-900">Aún no puedes finalizar el pedido</p>
            <p class="mt-2 text-sm font-bold text-amber-900">
                Te faltan {{ $moneyFromCents($min->missingAmountCents) }} para alcanzar el pedido mínimo de tu nivel.
            </p>
            <a href="{{ route('cart.index') }}" class="btn btn-secondary mt-4 inline-flex justify-center">
                Volver al carrito
            </a>
        </div>
    @elseif($context === 'checkout' && $mode === 'checkout-gold-pays-silver')
        <div {{ $attributes->class('commerce-status-panel commerce-status-panel--notice') }}>
            <p class="text-sm text-amber-950">
                Este pedido cumple el mínimo de compra, pero aún no alcanza el monto requerido para precios Oro. Se aplicarán precios Plata.
            </p>
        </div>
    @elseif($context === 'checkout' && $mode === 'checkout-gold-applied')
        <div {{ $attributes->class('commerce-status-compact commerce-status-compact--ok') }}>
            <span class="commerce-status-dot commerce-status-dot--ok" aria-hidden="true"></span>
            <p class="text-sm font-semibold text-emerald-800">Precios Oro aplicados.</p>
        </div>
    @elseif($context === 'checkout' && $mode === 'checkout-silver-ok')
        <x-commerce.minimum-order-status :decision="$min" variant="checkout" />
    @elseif($mode === 'gold-free')
        <div {{ $attributes->class('commerce-status-panel commerce-status-panel--compact') }}>
            <p class="text-sm font-semibold text-emerald-800">Tus precios Oro están activos.</p>
        </div>
    @elseif($mode === 'gold-both-ok')
        <div {{ $attributes->class('commerce-status-panel') }}>
            <h2 class="text-sm font-bold text-slate-900">Estado de tu pedido</h2>
            <div class="mt-3">
                <div class="commerce-status-row">
                    <div class="commerce-status-row__head">
                        <span class="commerce-status-dot commerce-status-dot--ok" aria-hidden="true"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-emerald-800">Precios Oro activados</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Tu pedido cumple las condiciones comerciales y ya incluye precios Oro.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif($mode === 'silver-ok')
        <div {{ $attributes->class('commerce-status-panel commerce-status-panel--compact') }}>
            <h2 class="sr-only">Estado de tu pedido</h2>
            <x-commerce.minimum-order-status :decision="$min" variant="row" />
        </div>
    @elseif($mode === 'silver-blocked')
        <div {{ $attributes->class('commerce-status-panel') }}>
            <h2 class="text-sm font-bold text-slate-900">Estado de tu pedido</h2>
            <div class="mt-3">
                <x-commerce.minimum-order-status :decision="$min" variant="cart-silver" />
            </div>
        </div>
    @elseif($mode === 'gold-threshold-ok-min-pending')
        <div {{ $attributes->class('commerce-status-panel') }}>
            <h2 class="text-sm font-bold text-slate-900">Estado de tu pedido</h2>
            <div class="mt-3 space-y-3">
                <x-commerce.gold-pricing-status :decision="$gold" :gold-pricing-applied="true" />
                <div class="border-t border-slate-100 pt-3">
                    <div class="commerce-status-row">
                        <div class="commerce-status-row__head">
                            <span class="commerce-status-dot commerce-status-dot--warn" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900">Pedido mínimo pendiente</p>
                                <p class="mt-1 text-sm font-bold text-amber-800">
                                    Te faltan {{ $moneyFromCents($min->missingAmountCents) }} para poder finalizar la compra.
                                </p>
                            </div>
                        </div>
                        @php
                            $amount = max(0, $min->minimumAmountCents);
                            $evaluated = max(0, $min->evaluatedAmountCents);
                            $progress = $amount > 0 ? min(100, (int) round(($evaluated / $amount) * 100)) : 0;
                        @endphp
                        <div class="commerce-progress mt-3">
                            <div class="commerce-progress__track" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="commerce-progress__bar" style="width: {{ $progress }}%"></div>
                            </div>
                            <div class="commerce-progress__meta">
                                <span>{{ $moneyFromCents($evaluated) }}</span>
                                <span>{{ $moneyFromCents($amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div {{ $attributes->class('commerce-status-panel') }}>
            <h2 class="text-sm font-bold text-slate-900">Estado de tu pedido</h2>
            <div class="mt-3 space-y-3">
                @if($minEnabled)
                    <x-commerce.minimum-order-status :decision="$min" variant="row" />
                @endif

                @if($goldEnabled)
                    <div class="{{ $minEnabled ? 'border-t border-slate-100 pt-3' : '' }}">
                        <x-commerce.gold-pricing-status
                            :decision="$gold"
                            :gold-pricing-applied="$goldApplied"
                            :variant="$mode === 'gold-min-ok-threshold-pending' ? 'cart-allowed' : 'row'"
                        />
                    </div>
                @endif
            </div>
        </div>
    @endif
@endif
