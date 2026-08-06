{{--
Component contract:
- Props: subtotal (pesos), vatLabel.
--}}
@props([
    'subtotal',
    'vatLabel',
])

@php
    $money = fn ($value) => '$'.number_format((float) $value, 0, ',', '.');
@endphp

<span class="cart-review-cell-label lg:hidden">Total</span>
<p class="text-base font-bold text-slate-950" data-cart-subtotal-amount>{{ $money($subtotal) }}</p>
<p class="text-[0.7rem] text-slate-400">{{ $vatLabel }}</p>
