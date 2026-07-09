@props(['variant' => 'neutral'])

{{--
Component contract:
- Props: variant neutral/info/success/warning/danger/brand.
- Slots: default badge label.
- Use for: small labels that do not imply workflow state transitions.
--}}
@php
    $variants = [
        'neutral' => 'bg-slate-100 text-slate-700',
        'info' => 'bg-sky-100 text-sky-900',
        'success' => 'bg-emerald-100 text-emerald-900',
        'warning' => 'bg-amber-100 text-amber-900',
        'danger' => 'bg-red-100 text-red-900',
        'brand' => 'bg-brand-primary/10 text-brand-dark',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.($variants[$variant] ?? $variants['neutral'])]) }}>
    {{ $slot }}
</span>
