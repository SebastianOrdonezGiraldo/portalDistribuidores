@props(['label' => 'Acciones'])

<details data-action-menu class="relative inline-block text-left">
    <summary class="btn btn-secondary px-3 text-sm">{{ $label }}</summary>

    <div class="absolute right-0 z-30 mt-2 w-52 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-panel">
        <div class="space-y-1 text-sm">
            {{ $slot }}
        </div>
    </div>
</details>
