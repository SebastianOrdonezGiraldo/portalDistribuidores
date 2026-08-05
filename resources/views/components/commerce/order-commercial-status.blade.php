{{--
Component contract:
- Props: $pricing (OrderPricingResult|null), $tier (DistributorTier), optional $context (cart|checkout).
- Combines minimum-order status using backend flags only.
- Gold carts only surface the pedido mínimo (never a gold-threshold activation state).
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
    $checkoutAllowed = $pricing?->checkoutAllowed() ?? true;
    $minEnabled = (bool) ($min?->enabled);
    $minReached = (bool) ($min?->allowed);

    $showBlock = false;
    $mode = 'hidden';

    if ($pricing !== null) {
        if ($isGold) {
            if ($minEnabled && ! $minReached) {
                $mode = 'gold-min-pending';
                $showBlock = true;
            } elseif ($minEnabled && $minReached) {
                $mode = 'gold-min-ok';
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
            } elseif ($isGold && $minEnabled && $minReached) {
                $mode = 'checkout-gold-min-ok';
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

@if($pricing !== null && $showBlock && $min !== null)
    @if($context === 'checkout' && $mode === 'checkout-blocked')
        <div {{ $attributes->class('commerce-status-panel commerce-status-panel--blocked') }}>
            <p class="text-base font-semibold text-slate-900">Aún no puedes finalizar el pedido</p>
            <p class="mt-2 text-sm font-bold text-amber-900">
                Te faltan {{ $moneyFromCents($min->missingAmountCents) }} para completar el pedido mínimo.
            </p>
            <a href="{{ route('cart.index') }}" class="btn btn-secondary mt-4 inline-flex justify-center">
                Volver al carrito
            </a>
        </div>
    @elseif($context === 'checkout' && $mode === 'checkout-gold-min-ok')
        <x-commerce.minimum-order-status :decision="$min" variant="checkout" />
    @elseif($context === 'checkout' && $mode === 'checkout-silver-ok')
        <x-commerce.minimum-order-status :decision="$min" variant="checkout" />
    @elseif($mode === 'gold-min-ok')
        <div {{ $attributes->class('commerce-status-panel commerce-status-panel--compact') }}>
            <h2 class="sr-only">Estado de tu pedido</h2>
            <x-commerce.minimum-order-status :decision="$min" variant="cart-gold" />
        </div>
    @elseif($mode === 'gold-min-pending')
        <div {{ $attributes->class('commerce-status-panel') }}>
            <h2 class="text-sm font-bold text-slate-900">Estado de tu pedido</h2>
            <div class="mt-3">
                <x-commerce.minimum-order-status :decision="$min" variant="cart-gold" />
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
    @endif
@endif
