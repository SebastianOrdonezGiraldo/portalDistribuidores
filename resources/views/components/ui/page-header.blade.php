@props(['title', 'subtitle' => null])

<header {{ $attributes->merge(['class' => 'mb-5 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between']) }}>
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
        <div class="flex w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
            {{ $actions }}
        </div>
    @endif
</header>
