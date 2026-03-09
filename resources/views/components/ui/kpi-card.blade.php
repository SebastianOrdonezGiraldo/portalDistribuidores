@props(['label', 'value', 'trend' => null, 'hint' => null, 'href' => null])

@php
    $container = 'card p-4';
    $linkClass = $href ? 'transition hover:-translate-y-0.5 hover:border-slate-300' : '';
@endphp

@if($href)
    <a href="{{ $href }}" class="{{ $container }} {{ $linkClass }}">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $value }}</p>
        @if($trend)
            <p class="mt-1 text-xs text-slate-500">{{ $trend }}</p>
        @endif
        @if($hint)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </a>
@else
    <div class="{{ $container }}">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $value }}</p>
        @if($trend)
            <p class="mt-1 text-xs text-slate-500">{{ $trend }}</p>
        @endif
        @if($hint)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>
@endif
