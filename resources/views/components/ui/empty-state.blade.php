@props(['title', 'description', 'compact' => false])

<div {{ $attributes->merge(['class' => 'empty-state '.($compact ? 'p-6' : 'p-10')]) }}>
    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400">
        @if(isset($icon))
            {{ $icon }}
        @else
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                <line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
        @endif
    </div>

    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
    <p class="mx-auto mt-1.5 max-w-xl text-sm text-slate-500">{{ $description }}</p>

    @if(isset($action))
        <div class="mt-5 flex justify-center">
            {{ $action }}
        </div>
    @endif
</div>
