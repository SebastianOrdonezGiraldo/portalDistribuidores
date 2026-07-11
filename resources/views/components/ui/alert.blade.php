@props(['variant' => 'info', 'title' => null, 'dismissible' => true])

{{--
Component contract:
- Props: variant info/success/warning/danger/neutral and optional title.
- Slots: default alert body.
- Use for: inline feedback blocks and toast content.
--}}
@php
    $variants = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-900',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger' => 'border-red-200 bg-red-50 text-red-900',
        'neutral' => 'border-slate-200 bg-slate-50 text-slate-900',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'toast relative '.($variants[$variant] ?? $variants['info'])]) }} data-dismissible-alert>
    @if($dismissible)
        <button
            type="button"
            class="absolute right-3 top-3 inline-flex h-7 w-7 items-center justify-center rounded-full text-current/60 transition hover:bg-black/5 hover:text-current focus:outline-none focus:ring-2 focus:ring-current/30"
            data-dismiss-alert
            aria-label="Cerrar mensaje"
            title="Cerrar mensaje"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M6 6l12 12"></path>
                <path d="M18 6L6 18"></path>
            </svg>
        </button>
    @endif
    <div class="pr-10">
        @if($title)
            <p class="mb-1 text-sm font-semibold">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>
</div>

@once
    <script>
        document.addEventListener('click', (event) => {
            const dismissButton = event.target.closest('[data-dismiss-alert]');
            if (!dismissButton) return;

            dismissButton.closest('[data-dismissible-alert]')?.remove();
        });
    </script>
@endonce
