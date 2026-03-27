@props([
    'product',
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => ['class' => 'h-10 w-10 rounded-lg', 'fallback' => 40],
        'md' => ['class' => 'h-16 w-16 rounded-xl', 'fallback' => 64],
        'lg' => ['class' => 'h-24 w-24 rounded-2xl', 'fallback' => 96],
    ];
    $sizeConfig = $sizes[$size] ?? $sizes['md'];
    $sizeClass = $sizeConfig['class'];
    $fallbackSize = $sizeConfig['fallback'];
    $coverPhoto = $product->primaryPhoto
        ?? ($product->relationLoaded('photos') ? $product->photos->first() : null);
    $srcSet = $coverPhoto?->webpSrcSet() ?? '';
    $width = $coverPhoto?->intrinsicWidth($fallbackSize) ?? $fallbackSize;
    $height = $coverPhoto?->intrinsicHeight($fallbackSize) ?? $fallbackSize;
@endphp

<div {{ $attributes->merge(['class' => "shrink-0 overflow-hidden border border-slate-200 bg-slate-100 p-1.5 {$sizeClass}"]) }}>
    @if($coverPhoto)
        <picture>
            @if($srcSet !== '')
                <source type="image/webp" srcset="{{ $srcSet }}" sizes="{{ $fallbackSize }}px">
            @endif
            <img
                src="{{ $coverPhoto->publicUrl() }}"
                alt="{{ $product->name }}"
                width="{{ $width }}"
                height="{{ $height }}"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-contain"
            >
        </picture>
    @else
        <div class="flex h-full items-center justify-center text-xs text-slate-400">Sin foto</div>
    @endif
</div>
