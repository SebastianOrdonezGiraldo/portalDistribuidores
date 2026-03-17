@props([
    'product',
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-10 w-10 rounded-lg',
        'md' => 'h-16 w-16 rounded-xl',
        'lg' => 'h-24 w-24 rounded-2xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->merge(['class' => "shrink-0 overflow-hidden border border-slate-200 bg-slate-100 {$sizeClass}"]) }}>
    @if($product->primaryPhoto)
        <img
            src="{{ \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($product->primaryPhoto->path) }}"
            alt="{{ $product->name }}"
            class="h-full w-full object-cover"
        >
    @else
        <div class="flex h-full items-center justify-center text-xs text-slate-400">Sin foto</div>
    @endif
</div>
