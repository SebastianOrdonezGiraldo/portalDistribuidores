@props([
    'title',
    'subtitle' => null,
    'eyebrow' => 'Centro operativo',
    'variant' => 'default',
])

<header {{ $attributes->merge(['class' => 'operational-header mb-5']) }}>
    <div class="operational-header-main">
        @if($eyebrow)
            <p class="operational-header-eyebrow">{{ $eyebrow }}</p>
        @endif

        <h1 class="operational-header-title text-balance">{{ $title }}</h1>
        @if($subtitle)
            <p class="operational-header-subtitle">{{ $subtitle }}</p>
        @endif
        @if (isset($meta))
            <div class="operational-header-meta">{{ $meta }}</div>
        @endif
        @if (isset($flow))
            <div class="mt-4">{{ $flow }}</div>
        @endif
    </div>

    @if (isset($actions))
        <div class="operational-header-actions">
            {{ $actions }}
        </div>
    @endif
</header>
