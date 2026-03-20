@props(['active' => false])

@php
    $base = 'group relative flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition focus-ring';
    $state = $active
        ? 'bg-brand-primary/15 font-semibold text-brand-dark border border-brand-primary/30 before:absolute before:left-0 before:top-1/2 before:h-4 before:w-0.5 before:-translate-y-1/2 before:rounded-full before:bg-brand-primary'
        : 'border border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-100 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $base.' '.$state]) }}>
    <span class="inline-flex items-center gap-2">{{ $slot }}</span>
</a>
