@props(['padding' => 'md'])

@php
    $paddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-5',
        'lg' => 'p-6',
    ];
@endphp

<section {{ $attributes->merge(['class' => 'card '.($paddings[$padding] ?? $paddings['md'])]) }}>
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
