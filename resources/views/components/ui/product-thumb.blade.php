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
    $coverPhoto = $product->primaryPhoto
        ?? ($product->relationLoaded('photos') ? $product->photos->first() : null);
@endphp

<div {{ $attributes->merge(['class' => "shrink-0 overflow-hidden border border-slate-200 bg-slate-100 p-1.5 {$sizeClass}"]) }}>
    @if($coverPhoto)
        <img
            src="{{ \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($coverPhoto->path) }}"
            alt="{{ $product->name }}"
            class="h-full w-full object-contain"
        >
    @else
        <div class="flex h-full items-center justify-center text-xs text-slate-400">Sin foto</div>
    @endif
</div>
