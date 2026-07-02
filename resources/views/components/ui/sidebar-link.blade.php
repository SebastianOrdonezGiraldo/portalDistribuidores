@props(['active' => false, 'hint' => null, 'tone' => 'default'])

@php
    $base = 'group relative flex min-h-[2.625rem] items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition focus-ring';
    $toneClass = match ($tone) {
        'command' => 'text-brand-aubergine hover:border-brand-accent/25 hover:bg-brand-accent/10 hover:text-brand-aubergine',
        'muted' => 'text-slate-500 hover:border-brand-primary/10 hover:bg-brand-mist hover:text-brand-ink',
        default => 'text-slate-600 hover:border-brand-primary/20 hover:bg-brand-mist hover:text-brand-ink',
    };
    $state = $active
        ? 'bg-brand-primary/20 font-semibold text-brand-dark border border-brand-primary/30 before:absolute before:left-0 before:top-1/2 before:h-5 before:w-1 before:-translate-y-1/2 before:rounded-r-full before:bg-brand-accent'
        : 'border border-transparent '.$toneClass;
@endphp

<a {{ $attributes->merge(['class' => $base.' '.$state]) }}>
    <span class="min-w-0">
        <span class="inline-flex min-w-0 items-center gap-2">{{ $slot }}</span>
        @if($hint)
            <span class="mt-0.5 block truncate pl-6 text-[0.68rem] font-medium text-slate-500">{{ $hint }}</span>
        @endif
    </span>
</a>
