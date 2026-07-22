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
    $greeting = filled($userName)
        ? str_replace(':name', $userName, $greetingTemplate)
        : trim((string) preg_replace('/,?\s*:name/', '', $greetingTemplate), " \t,");
    if ($greeting === '') {
        $greeting = $tier->badgeLabel();
    }
    $headline = (string) ($banner['headline'] ?? '');
    $subtext = (string) ($banner['subtext'] ?? '');
    $missedTemplate = $banner['missed_savings_template'] ?? null;
    $missedLine = ($missedTemplate && filled($missedSavingsAmount))
        ? str_replace(':amount', $missedSavingsAmount, (string) $missedTemplate)
        : null;
    $showCta = (bool) ($banner['show_upgrade_cta'] ?? false);
    $ctaLabel = (string) ($banner['cta_label'] ?? 'Sube de nivel');
    $accent = $tier->accent();
    $bgPath = (string) ($banner['background_image'] ?? '');
    $bgUrl = filled($bgPath) ? asset($bgPath) : null;
@endphp

<section
    class="tier-welcome-banner tier-welcome-banner--{{ $accent }}"
    aria-label="Bienvenida de nivel"
    @if($bgUrl) style="--tier-banner-image: url('{{ $bgUrl }}')" @endif
>
    <div class="tier-welcome-banner__media" aria-hidden="true"></div>
    <div class="tier-welcome-banner__veil" aria-hidden="true"></div>

    <div class="tier-welcome-banner__inner">
        <div class="tier-welcome-banner__copy min-w-0">
            <div class="tier-welcome-banner__meta">
                <p class="tier-welcome-banner__greeting">{{ $greeting }}</p>
                <x-tier.badge :tier="$tier" size="sm" />
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
                    class="tier-welcome-banner__cta"
                    @click="$dispatch('open-modal', 'tier-upgrade')"
                >
                    {{ $ctaLabel }}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                    </svg>
                </button>
            @endif
        </div>

        <div class="tier-welcome-banner__emblem" aria-hidden="true">
            <div class="tier-welcome-banner__emblem-ring">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" class="tier-welcome-banner__emblem-icon" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.15">
                    <path d="M12 2.5 14.6 8l6 .5-4.6 4 1.4 5.8L12 15.8 6.6 18.3 8 12.5 3.4 8.5l6-.5L12 2.5Z"/>
                </svg>
                <span class="tier-welcome-banner__emblem-label">{{ $tier->badgeLabel() }}</span>
            </div>
        </div>
    </div>
</section>
