@props(['variant' => 'neutral'])

@php
    $variants = [
        'neutral' => 'border-slate-200 bg-slate-100 text-slate-700',
        'info' => 'border-sky-200 bg-sky-50 text-sky-700',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'danger' => 'border-red-200 bg-red-50 text-red-700',
        'brand' => 'border-brand-primary/30 bg-brand-primary/10 text-[#15565c]',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.($variants[$variant] ?? $variants['neutral'])]) }}>
    {{ $slot }}
</span>
