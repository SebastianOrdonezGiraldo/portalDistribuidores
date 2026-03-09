@props([
    'id',
    'title' => null,
    'description' => null,
])

<div id="{{ $id }}" data-modal {{ $attributes->merge(['class' => 'fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/50 p-4']) }} role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-panel">
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
