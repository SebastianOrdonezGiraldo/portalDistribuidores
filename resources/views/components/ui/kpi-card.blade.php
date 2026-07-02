@props(['label', 'value', 'trend' => null, 'hint' => null, 'href' => null, 'accent' => null])

@php
    $iconBgColors = [
        'brand'   => 'bg-brand-primary/20 text-brand-dark',
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-700',
        'danger'  => 'bg-red-50 text-red-700',
        'info'    => 'bg-sky-50 text-sky-700',
        'neutral' => 'bg-brand-mist text-brand-primary',
    ];
    $iconBgClass = $iconBgColors[$accent ?? 'neutral'] ?? $iconBgColors['neutral'];

    $trendClass = 'mt-1 text-xs text-slate-500';
    if (is_string($trend)) {
        $trimmedTrend = ltrim($trend);
        if (str_starts_with($trimmedTrend, '+')) {
            $trendClass = 'inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700';
        } elseif (str_starts_with($trimmedTrend, '-')) {
            $trendClass = 'inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700';
        }
    }

    $cardBase = 'card card-metric overflow-hidden p-5 transition hover:-translate-y-0.5 hover:shadow-soft';
    $linkExtra = $href ? 'block cursor-pointer hover:border-brand-primary/40' : '';
@endphp

@php
    $defaultIcon = match ($accent ?? 'neutral') {
        'success' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'warning' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'info' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        'danger' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        'neutral' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        default => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
    };
@endphp

@if($href)
    <a href="{{ $href }}" class="{{ $cardBase }} {{ $linkExtra }} group/kpi">
        <div class="flex items-start justify-between gap-2">
            <div class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl {{ $iconBgClass }}">
                @isset($icon)
                    {{ $icon }}
                @else
                    {!! $defaultIcon !!}
                @endisset
            </div>
            <div class="flex flex-shrink-0 items-start gap-1">
                @if($trend)
                    <span class="{{ $trendClass }}">{{ $trend }}</span>
                @endif
                <span class="text-slate-400 transition group-hover/kpi:text-brand-dark" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            </div>
        </div>
        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <p class="mt-1 font-display text-3xl font-semibold tabular-nums text-brand-ink">{{ $value }}</p>
        @if($hint && !$trend)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </a>
@else
    <div class="{{ $cardBase }}">
        <div class="flex items-start justify-between gap-2">
            <div class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl {{ $iconBgClass }}">
                @isset($icon)
                    {{ $icon }}
                @else
                    {!! $defaultIcon !!}
                @endisset
            </div>
            @if($trend)
                <span class="{{ $trendClass }}">{{ $trend }}</span>
            @endif
        </div>
        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <p class="mt-1 font-display text-3xl font-semibold tabular-nums text-brand-ink">{{ $value }}</p>
        @if($hint && !$trend)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>
@endif
