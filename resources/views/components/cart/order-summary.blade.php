{{--
Component contract:
- Props from confirmed OrderPricingResult / cart display totals (pesos).
- Presentation only; no commercial rule evaluation.
--}}
@props([
    'productsSubtotal',
    'grossTotal',
    'goldPricingApplied' => false,
    'goldSavings' => 0,
    'isGold' => false,
    'hasPotentialSavings' => false,
    'potentialSavings' => 0,
    'potentialSavingsPct' => 0,
])

@php
    $money = fn ($value) => '$'.number_format((float) $value, 0, ',', '.');
    $vatRate = \App\Modules\Orders\Support\OrderLineVat::DEFAULT_RATE;
    $hasGoldSavings = $goldPricingApplied && (float) $goldSavings > 0.5;
@endphp

<h2 class="text-base font-bold text-slate-900">Resumen del pedido</h2>

<dl class="mt-4 space-y-2.5 text-sm">
    <div class="flex items-center justify-between gap-3">
        <dt class="text-slate-500">Subtotal de productos</dt>
        <dd class="font-medium text-slate-700" data-sum-products-subtotal>{{ $money($productsSubtotal) }}</dd>
    </div>
    @if($hasGoldSavings)
        <div class="flex items-center justify-between gap-3">
            <dt class="font-medium text-amber-600">Ahorro Cliente Oro</dt>
            <dd class="font-semibold text-amber-600">− {{ $money($goldSavings) }}</dd>
        </div>
    @endif
</dl>

<div class="cart-review-total {{ $goldPricingApplied ? 'cart-review-total--gold' : 'cart-review-total--silver' }} mt-4">
    @if($goldPricingApplied)
        <svg class="cart-review-crown" xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m5 16 -1.6 -8 4.6 3.5L12 5l3.9 6.5L20.6 8 19 16z"/></svg>
    @endif
    <p class="relative text-xs font-semibold uppercase tracking-wide text-slate-500">Total del pedido</p>
    <p class="relative mt-1 break-words text-3xl font-bold tracking-tight text-slate-950" data-sum-total>{{ $money($grossTotal) }} <span class="text-base font-semibold text-slate-400">COP</span></p>
    <p class="relative text-xs text-slate-500">IVA incluido ({{ number_format($vatRate * 100, 0) }}% en productos gravados)</p>
</div>

@if(! $isGold && $hasPotentialSavings)
    <div class="cart-review-oro-savings-box mt-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-none text-amber-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m5 16 -1.6 -8 4.6 3.5L12 5l3.9 6.5L20.6 8 19 16z"/></svg>
        <div>
            <p class="text-sm font-semibold text-slate-900">
                Si fueras Cliente Oro ahorrarías:
                <span class="text-amber-700"><span data-sum-potential-savings>{{ $money($potentialSavings) }}</span> COP</span>
            </p>
            <p class="mt-0.5 text-xs text-slate-500">
                Descuento estimado nivel Oro: <span data-sum-potential-savings-pct>{{ $potentialSavingsPct }}</span>%
            </p>
        </div>
    </div>
@endif
