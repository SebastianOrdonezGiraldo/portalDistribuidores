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
- Notes: SVG must carry explicit width/height — unsized SVGs expand to 100% of wide parents.
--}}
@php
    /** @var \App\Modules\Shared\Enums\DistributorTier $tier */
    $accent = $tier->accent();
    $label = $size === 'sm' ? $tier->topbarLabel() : $tier->badgeLabel();
    $isClickable = $interactive && $tier->hasBenefitsModal();
    $iconPx = $size === 'sm' ? 14 : 16;
    $baseClasses = 'tier-badge tier-badge--'.$accent
        .' inline-flex max-w-full shrink-0 items-center gap-1.5 rounded-full font-semibold'
        .($size === 'sm' ? ' tier-badge--sm px-2.5 py-1 text-xs' : ' px-3 py-1.5 text-sm');
@endphp

@if($isClickable)
    <button
        type="button"
        {{ $attributes->merge(['class' => $baseClasses.' tier-badge--interactive cursor-pointer']) }}
        @click="$dispatch('open-modal', 'tier-upgrade')"
        title="{{ $tier->showUpgradeCta() ? 'Conoce cómo subir de nivel' : 'Ver beneficios de tu nivel' }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $iconPx }}" height="{{ $iconPx }}" class="tier-badge__icon shrink-0" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
            <path d="M12 2.5 14.6 8l6 .5-4.6 4 1.4 5.8L12 15.8 6.6 18.3 8 12.5 3.4 8.5l6-.5L12 2.5Z"/>
        </svg>
        <span class="truncate">{{ $label }}</span>
    </button>
@else
    <span {{ $attributes->merge(['class' => $baseClasses]) }}>
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $iconPx }}" height="{{ $iconPx }}" class="tier-badge__icon shrink-0" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
            <path d="M12 2.5 14.6 8l6 .5-4.6 4 1.4 5.8L12 15.8 6.6 18.3 8 12.5 3.4 8.5l6-.5L12 2.5Z"/>
        </svg>
        <span class="truncate">{{ $label }}</span>
    </span>
@endif
