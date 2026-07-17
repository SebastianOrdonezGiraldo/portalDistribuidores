@props([
    'title',
    'description',
    'eyebrow' => null,
])

<x-ui.card {{ $attributes->merge(['class' => 'overflow-hidden border-slate-200 bg-white']) }}>
    <div class="relative isolate overflow-hidden rounded-[inherit]">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.14),_transparent_36%),radial-gradient(circle_at_bottom_right,_rgba(45,212,191,0.12),_transparent_34%),linear-gradient(180deg,_rgba(248,250,252,0.96),_rgba(255,255,255,0.98))]"></div>
        <div class="absolute -left-14 top-8 h-28 w-28 rounded-full bg-cyan-100/70 blur-2xl"></div>
        <div class="absolute -right-10 bottom-0 h-32 w-32 rounded-full bg-sky-100/70 blur-3xl"></div>

        <div class="relative grid gap-6 px-6 py-8 sm:px-8 sm:py-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
            <div class="max-w-2xl">
                @if(filled($eyebrow))
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-cyan-700">{{ $eyebrow }}</p>
                @endif

                <h3 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">{{ $title }}</h3>
                <p class="mt-3 max-w-xl text-sm leading-7 text-slate-600 sm:text-[0.95rem]">{{ $description }}</p>

                @if(isset($action))
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        {{ $action }}
                    </div>
                @endif
            </div>

            <div class="flex justify-start lg:justify-end">
                <div class="flex h-20 w-20 items-center justify-center rounded-[1.75rem] border border-white/70 bg-white/90 text-cyan-700 shadow-[0_18px_45px_-28px_rgba(14,116,144,0.75)] backdrop-blur">
                    @if(isset($icon))
                        {{ $icon }}
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M12 3v18" />
                            <path d="M3 12h18" />
                            <path d="m19 5-2 2" />
                            <path d="m7 17-2 2" />
                            <path d="m19 19-2-2" />
                            <path d="m7 7-2-2" />
                        </svg>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-ui.card>
