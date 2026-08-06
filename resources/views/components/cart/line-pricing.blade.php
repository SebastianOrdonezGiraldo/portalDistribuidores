{{--
Component contract:
- Props: unitPrice, silverUnitPrice, vatLabel, goldPricingApplied, lineSavings, isGold.
- Presentation only; no commercial rule evaluation.
--}}
@props([
    'unitPrice',
    'silverUnitPrice',
    'vatLabel',
    'goldPricingApplied' => false,
    'lineSavings' => 0,
    'isGold' => false,
])

@php
    $money = fn ($value) => '$'.number_format((float) $value, 0, ',', '.');
    $showGoldLinePricing = $goldPricingApplied && (float) $silverUnitPrice > (float) $unitPrice;
    $showSilverAppliedLabel = $isGold && ! $goldPricingApplied;
@endphp

<span class="cart-review-cell-label lg:hidden">Precio unitario</span>
@if($showGoldLinePricing)
    <p class="text-xs text-slate-400 line-through">{{ $money($silverUnitPrice) }}</p>
@endif
<p class="text-sm font-semibold text-slate-900">{{ $money($unitPrice) }}</p>
<p class="text-[0.7rem] text-slate-400">{{ $vatLabel }}</p>
@if($showGoldLinePricing && (float) $lineSavings > 0.5)
    <span class="cart-review-oro-chip mt-1.5">
        Precio Oro aplicado
    </span>
@elseif($showSilverAppliedLabel)
    <span class="cart-review-plata-chip mt-1.5">
        Precio Plata aplicado
    </span>
@endif
