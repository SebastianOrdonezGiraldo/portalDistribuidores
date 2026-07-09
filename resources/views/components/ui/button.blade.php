@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

{{--
Component contract:
- Props: variant primary/secondary/ghost/danger, size sm/md/lg, and HTML button type.
- Slots: default button label/content.
- Use for: form buttons; use anchor classes directly when the element must be an <a>.
--}}
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
