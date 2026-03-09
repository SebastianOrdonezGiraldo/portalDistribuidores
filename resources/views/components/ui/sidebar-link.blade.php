@props(['active' => false])

@php
    $base = 'group flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition focus-ring';
    $state = $active
        ? 'bg-brand-primary/15 text-[#0f6268] border border-brand-primary/30'
        : 'border border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-100 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $base.' '.$state]) }}>
    <span class="inline-flex items-center gap-2">{{ $slot }}</span>
</a>
