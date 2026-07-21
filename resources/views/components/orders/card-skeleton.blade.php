<div {{ $attributes->merge(['class' => 'card p-4 sm:p-5']) }} aria-hidden="true">
    <div class="grid gap-4 lg:grid-cols-[minmax(11rem,.75fr)_minmax(14rem,1.15fr)_8rem_9rem_minmax(18rem,1.35fr)] lg:items-center">
        <div class="space-y-2"><x-ui.skeleton class="h-3 w-16" /><x-ui.skeleton class="h-5 w-32" /></div>
        <div class="space-y-2"><x-ui.skeleton class="h-4 w-44" /><x-ui.skeleton class="h-3 w-28" /></div>
        <x-ui.skeleton class="h-8 w-20" />
        <x-ui.skeleton class="h-8 w-24" />
        <div class="flex items-center gap-2"><x-ui.skeleton class="h-4 w-4 rounded-full" /><x-ui.skeleton class="h-1 flex-1" /><x-ui.skeleton class="h-4 w-4 rounded-full" /><x-ui.skeleton class="h-1 flex-1" /><x-ui.skeleton class="h-4 w-4 rounded-full" /></div>
    </div>
</div>
