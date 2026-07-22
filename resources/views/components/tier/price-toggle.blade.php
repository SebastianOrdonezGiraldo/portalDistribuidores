@props([
    'tier',
])

{{--
Component contract:
- Props: DistributorTier $tier.
- Slots: none.
- Use for: catalog header price mode control (Alpine parent must expose showGoldPrices).
--}}
@php
    /** @var \App\Modules\Shared\Enums\DistributorTier $tier */
    $toggle = $tier->priceToggle();
    $interactive = (bool) ($toggle['interactive'] ?? false);
    $labelOn = (string) ($toggle['label_on'] ?? 'Mostrando precios Oro');
    $labelOff = (string) ($toggle['label_off'] ?? 'Mostrar precios Oro');
    $lockedLabel = (string) ($toggle['locked_label'] ?? 'Ver precios Oro');
@endphp

@if($interactive)
    <label class="tier-price-toggle" title="Alternar vista de precios Oro">
        <span class="tier-price-toggle__track" :class="showGoldPrices ? 'is-on' : ''">
            <input
                type="checkbox"
                class="sr-only"
                x-model="showGoldPrices"
                :aria-checked="showGoldPrices.toString()"
            >
            <span class="tier-price-toggle__thumb" aria-hidden="true"></span>
        </span>
        <span class="tier-price-toggle__label" x-text="showGoldPrices ? @js($labelOn) : @js($labelOff)"></span>
        <span class="tier-price-toggle__info" title="El precio Oro es tu precio efectivo de nivel. El precio estándar es la referencia Plata.">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        </span>
    </label>
@else
    <button
        type="button"
        class="tier-price-toggle tier-price-toggle--locked"
        @click="$dispatch('open-modal', 'tier-upgrade')"
        title="Disponible al subir a Nivel Oro"
    >
        <span class="tier-price-toggle__track">
            <span class="tier-price-toggle__thumb" aria-hidden="true"></span>
        </span>
        <span class="tier-price-toggle__label">{{ $lockedLabel }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
    </button>
@endif
