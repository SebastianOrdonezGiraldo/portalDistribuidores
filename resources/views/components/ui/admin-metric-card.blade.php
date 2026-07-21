@props([
    'label',
    'value',
    'href' => null,
    'hint' => null,
    'trend' => null,
    'tone' => 'teal',
    'icon' => 'orders',
])

@php
    $trimmedTrend = is_string($trend) ? trim($trend) : null;
    $trendTone = $trimmedTrend && str_starts_with($trimmedTrend, '+')
        ? 'positive'
        : ($trimmedTrend && str_starts_with($trimmedTrend, '-') ? 'negative' : 'neutral');

    $trendValue = $trimmedTrend;
    $trendContext = null;

    if ($trimmedTrend && preg_match('/^([+-]?\d+(?:[.,]\d+)?%)\s*(.*)$/u', $trimmedTrend, $matches)) {
        $trendValue = $matches[1];
        $trendContext = filled($matches[2] ?? null) ? trim($matches[2]) : null;
    }
@endphp

@if($href)
    <a href="{{ $href }}" class="admin-metric-card admin-metric-card--{{ $tone }}">
        <div class="admin-metric-card__heading">
            <span class="admin-metric-card__icon" aria-hidden="true">
                @switch($icon)
                    @case('orders')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.4 10.4A2 2 0 0 0 9.35 16H17a2 2 0 0 0 1.94-1.52L21 7H6"/></svg>
                        @break
                    @case('users')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                        @break
                    @case('products')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4.4 7.7 7.6 4.4 7.6-4.4M12 12.1V21"/></svg>
                        @break
                    @default
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                @endswitch
            </span>
            <span class="admin-metric-card__label">{{ $label }}</span>
        </div>

        <p class="admin-metric-card__value">{{ $value }}</p>

        @if($trimmedTrend)
            <p class="admin-metric-card__caption">
                <span class="admin-metric-card__trend admin-metric-card__trend--{{ $trendTone }}">{{ $trendValue }}</span>
                @if($trendContext)
                    <span>{{ $trendContext }}</span>
                @endif
            </p>
        @elseif($hint)
            <p class="admin-metric-card__caption">{{ $hint }}</p>
        @endif
    </a>
@else
    <div class="admin-metric-card admin-metric-card--{{ $tone }}">
        <div class="admin-metric-card__heading">
            <span class="admin-metric-card__icon" aria-hidden="true">
                @switch($icon)
                    @case('orders')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.4 10.4A2 2 0 0 0 9.35 16H17a2 2 0 0 0 1.94-1.52L21 7H6"/></svg>
                        @break
                    @case('users')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                        @break
                    @case('products')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4.4 7.7 7.6 4.4 7.6-4.4M12 12.1V21"/></svg>
                        @break
                    @default
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                @endswitch
            </span>
            <span class="admin-metric-card__label">{{ $label }}</span>
        </div>

        <p class="admin-metric-card__value">{{ $value }}</p>

        @if($trimmedTrend)
            <p class="admin-metric-card__caption">
                <span class="admin-metric-card__trend admin-metric-card__trend--{{ $trendTone }}">{{ $trendValue }}</span>
                @if($trendContext)
                    <span>{{ $trendContext }}</span>
                @endif
            </p>
        @elseif($hint)
            <p class="admin-metric-card__caption">{{ $hint }}</p>
        @endif
    </div>
@endif
