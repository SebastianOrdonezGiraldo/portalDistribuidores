@props([
    'tier',
    'size' => 'md',
    'interactive' => false,
])

{{--
Component contract:
- Props: DistributorTier $tier, size sm|md, interactive (opens upgrade modal when tier shows CTA).
- Slots: none.
- Use for: topbar / banner / sidebar level indicators.
--}}
@php
    /** @var \App\Modules\Shared\Enums\DistributorTier $tier */
    $accent = $tier->accent();
    $label = $size === 'sm' ? $tier->topbarLabel() : $tier->badgeLabel();
    $isClickable = $interactive && $tier->showUpgradeCta();
    $classes = 'tier-badge tier-badge--'.$accent.($size === 'sm' ? ' tier-badge--sm' : '');
@endphp

@if($isClickable)
    <button
        type="button"
        {{ $attributes->merge(['class' => $classes.' tier-badge--interactive']) }}
        @click="$dispatch('open-modal', 'tier-upgrade')"
        title="Conoce cómo subir de nivel"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="tier-badge__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2.5 14.6 8l6 .5-4.6 4 1.4 5.8L12 15.8 6.6 18.3 8 12.5 3.4 8.5l6-.5L12 2.5Z"/>
        </svg>
        <span>{{ $label }}</span>
    </button>
@else
    <span {{ $attributes->merge(['class' => $classes]) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="tier-badge__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2.5 14.6 8l6 .5-4.6 4 1.4 5.8L12 15.8 6.6 18.3 8 12.5 3.4 8.5l6-.5L12 2.5Z"/>
        </svg>
        <span>{{ $label }}</span>
    </span>
@endif
