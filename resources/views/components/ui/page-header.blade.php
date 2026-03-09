@props(['title', 'subtitle' => null])

<header {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="space-y-1">
        <h1 class="text-balance text-2xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>
        @if($subtitle)
            <p class="max-w-2xl text-sm text-slate-600">{{ $subtitle }}</p>
        @endif
        @if (isset($meta))
            <div class="pt-1 text-xs text-slate-500">{{ $meta }}</div>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</header>
