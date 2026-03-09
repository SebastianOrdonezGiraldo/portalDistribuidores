@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        'danger' => 'btn-danger',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-sm',
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => trim(($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']))]) }}>
    {{ $slot }}
</button>
