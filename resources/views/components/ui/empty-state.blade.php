@props(['title', 'description', 'compact' => false])

<div {{ $attributes->merge(['class' => 'empty-state '.($compact ? 'p-6' : 'p-10')]) }}>
    @if(isset($icon))
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
            {{ $icon }}
        </div>
    @endif

    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
    <p class="mx-auto mt-1 max-w-xl text-sm text-slate-600">{{ $description }}</p>

    @if(isset($action))
        <div class="mt-4 flex justify-center">
            {{ $action }}
        </div>
    @endif
</div>
