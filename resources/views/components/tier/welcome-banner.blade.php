@props([
    'tier',
    'userName' => null,
    'missedSavingsAmount' => null,
])

{{--
Component contract:
- Props: DistributorTier $tier, optional userName, optional missedSavingsAmount (formatted money string).
- Slots: none.
- Use for: welcome / opportunity banner driven by commerce.presentation config.
- Notes: no Blade branches on tier value — all copy comes from $tier->* methods.
--}}
@php
    /** @var \App\Modules\Shared\Enums\DistributorTier $tier */
    $banner = $tier->banner();
    $greetingTemplate = (string) ($banner['greeting'] ?? '');
    $greeting = $userName
        ? str_replace(':name', $userName, $greetingTemplate)
        : str_replace(', :name', '', str_replace(':name', '', $greetingTemplate));
    $headline = (string) ($banner['headline'] ?? '');
    $subtext = (string) ($banner['subtext'] ?? '');
    $missedTemplate = $banner['missed_savings_template'] ?? null;
    $missedLine = ($missedTemplate && filled($missedSavingsAmount))
        ? str_replace(':amount', $missedSavingsAmount, (string) $missedTemplate)
        : null;
    $showCta = (bool) ($banner['show_upgrade_cta'] ?? false);
    $ctaLabel = (string) ($banner['cta_label'] ?? 'Sube de nivel');
    $accent = $tier->accent();
@endphp

<section class="tier-welcome-banner tier-welcome-banner--{{ $accent }}" aria-label="Bienvenida de nivel">
    <div class="tier-welcome-banner__copy">
        <div class="tier-welcome-banner__meta">
            <p class="tier-welcome-banner__greeting">{{ $greeting }}</p>
            <x-tier.badge :tier="$tier" />
        </div>

        <h2 class="tier-welcome-banner__headline">{{ $headline }}</h2>

        @if($missedLine)
            <p class="tier-welcome-banner__missed">{{ $missedLine }}</p>
        @endif

        @if(filled($subtext))
            <p class="tier-welcome-banner__subtext">{{ $subtext }}</p>
        @endif

        @if($showCta)
            <button
                type="button"
                class="btn btn-primary mt-4"
                @click="$dispatch('open-modal', 'tier-upgrade')"
            >
                {{ $ctaLabel }}
            </button>
        @endif
    </div>

    <div class="tier-welcome-banner__emblem" aria-hidden="true">
        <span class="tier-welcome-banner__emblem-label">{{ $tier->badgeLabel() }}</span>
    </div>
</section>
