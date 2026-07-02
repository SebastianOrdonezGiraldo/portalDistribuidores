@props(['padding' => 'md', 'variant' => 'default'])

@php
    $paddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-5',
        'lg' => 'p-6',
    ];

    $variants = [
        'default' => '',
        'subtle' => 'card-subtle',
        'metric' => 'card-metric',
        'command' => 'card-command',
        'highlight' => 'card-highlight',
    ];
@endphp

<section {{ $attributes->merge(['class' => trim('card '.($variants[$variant] ?? '').' '.($paddings[$padding] ?? $paddings['md']))]) }}>
    @if (isset($header))
        <header class="card-header">
            {{ $header }}
        </header>
    @endif

    @if (isset($footer))
        <div class="{{ isset($header) ? 'p-5' : '' }}">{{ $slot }}</div>
        <footer class="border-t border-slate-200 px-5 py-4">{{ $footer }}</footer>
    @else
        {{ $slot }}
    @endif
</section>
