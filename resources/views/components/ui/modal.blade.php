@props([
    'id',
    'title' => null,
    'description' => null,
])

<div id="{{ $id }}" data-modal {{ $attributes->merge(['class' => 'fixed inset-0 z-[70] hidden items-end justify-center bg-slate-950/60 p-3 sm:items-center sm:p-4']) }} role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="max-h-[calc(100dvh-1.5rem)] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-panel sm:max-h-[calc(100dvh-2rem)] sm:p-6">
        @if($title)
            <h3 id="{{ $id }}-title" class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
        @endif

        @if($description)
            <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
        @endif

        <div class="mt-4">
            {{ $slot }}
        </div>
    </div>
</div>
