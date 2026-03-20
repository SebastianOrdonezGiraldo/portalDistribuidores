@props([
    'dense' => false,
    'stacked' => true,
])

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
