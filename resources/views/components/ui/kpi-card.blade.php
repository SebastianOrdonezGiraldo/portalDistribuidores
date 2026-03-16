@props(['label', 'value', 'trend' => null, 'hint' => null, 'href' => null, 'accent' => null])

@php
    $container = 'card overflow-hidden p-4';
    $linkClass = $href ? 'transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-soft' : '';
    $trendClass = 'mt-1 text-xs text-slate-500';
    $accentClass = $accent ? 'kpi-accent-'.$accent : '';

    if (is_string($trend)) {
        $trimmedTrend = ltrim($trend);

        if (str_starts_with($trimmedTrend, '+')) {
            $trendClass = 'mt-1 text-xs font-medium text-emerald-700';
        } elseif (str_starts_with($trimmedTrend, '-')) {
            $trendClass = 'mt-1 text-xs font-medium text-rose-700';
        }
    }

    $iconBgColors = [
        'brand'   => 'bg-brand-primary/10 text-[#0f6268]',
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-700',
        'danger'  => 'bg-red-50 text-red-700',
        'info'    => 'bg-sky-50 text-sky-700',
        'neutral' => 'bg-slate-100 text-slate-600',
    ];
    $iconBgClass = $iconBgColors[$accent ?? 'neutral'] ?? $iconBgColors['neutral'];
@endphp

@if($href)
    <a href="{{ $href }}" class="{{ $container }} {{ $linkClass }} {{ $accentClass }}">
        @if(isset($icon))
            <div class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $iconBgClass }}">
                {{ $icon }}
            </div>
        @endif
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <p class="mt-1.5 text-2xl font-semibold tabular-nums text-slate-900">{{ $value }}</p>
        @if($trend)
            <p class="{{ $trendClass }}">{{ $trend }}</p>
        @endif
        @if($hint && !$trend)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </a>
@else
    <div class="{{ $container }} {{ $accentClass }}">
        @if(isset($icon))
            <div class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $iconBgClass }}">
                {{ $icon }}
            </div>
        @endif
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <p class="mt-1.5 text-2xl font-semibold tabular-nums text-slate-900">{{ $value }}</p>
        @if($trend)
            <p class="{{ $trendClass }}">{{ $trend }}</p>
        @endif
        @if($hint && !$trend)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>
@endif
