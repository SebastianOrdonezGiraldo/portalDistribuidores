@props(['variant' => 'info', 'title' => null])

@php
    $variants = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-900',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger' => 'border-red-200 bg-red-50 text-red-900',
        'neutral' => 'border-slate-200 bg-slate-50 text-slate-900',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'toast '.($variants[$variant] ?? $variants['info'])]) }}>
    @if($title)
        <p class="mb-1 text-sm font-semibold">{{ $title }}</p>
    @endif
    <div>{{ $slot }}</div>
</div>
