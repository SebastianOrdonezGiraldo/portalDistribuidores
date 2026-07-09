@props([
    'dense' => false,
    'stacked' => true,
])

{{--
Component contract:
- Props: dense text mode and stacked mobile-card mode.
- Slots: optional head/body slots or a complete table body in the default slot.
- Use for: responsive data tables in admin and company views.
--}}
@php
    $wrapperClass = 'table-wrap'.($stacked ? ' table-mobile-cards' : '');
@endphp

<div class="{{ $wrapperClass }}">
    <table {{ $attributes->merge(['class' => 'table-base '.($dense ? 'text-xs' : '')]) }}>
        @if (isset($head) || isset($body))
            @isset($head)
                <thead>{{ $head }}</thead>
            @endisset

            @isset($body)
                <tbody>{{ $body }}</tbody>
            @endisset
        @else
            {{ $slot }}
        @endif
    </table>
</div>
