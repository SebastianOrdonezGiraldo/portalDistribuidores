@props([
    'tier',
    'metrics',
])

{{--
Component contract:
- Props: DistributorTier $tier, DistributorTierMetrics $metrics.
- Slots: none.
- Use for: catalog (or other) KPI strip; labels/hints come from presentation config.
- Notes: benefits count is PLACEHOLDER from config until a promotions module exists.
--}}
@php
    /** @var \App\Modules\Shared\Enums\DistributorTier $tier */
    /** @var \App\Modules\Orders\Pricing\DistributorTierMetrics $metrics */
    $kpis = $tier->kpis();
    $money = static fn (string $decimal): string => '$'.number_format((float) $decimal, 0, ',', '.');
    $formatTrend = static function (?float $percent): ?string {
        if ($percent === null) {
            return null;
        }

        $prefix = $percent >= 0 ? '+' : '';

        return $prefix.rtrim(rtrim(number_format($percent, 1, '.', ''), '0'), '.').'% vs. mes anterior';
    };

    $savingsCfg = $kpis['savings'] ?? [];
    $discountCfg = $kpis['discount'] ?? [];
    $ordersCfg = $kpis['orders'] ?? [];
    $benefitsCfg = $kpis['benefits'] ?? [];

    $savingsValue = $metrics->savingsCents > 0
        ? $money($metrics->savingsDecimal())
            .(isset($savingsCfg['value_suffix']) ? ' '.$savingsCfg['value_suffix'] : '')
        : '—';
    $savingsHint = $metrics->savingsCents > 0
        ? null
        : ($savingsCfg['empty_hint'] ?? null);

    $discountValue = str_replace(
        ':percent',
        (string) $metrics->discountPercent,
        (string) ($discountCfg['value_template'] ?? ':percent%')
    );

    $ordersSuffix = $metrics->ordersCount === 1
        ? ($ordersCfg['value_suffix_one'] ?? 'pedido este mes')
        : ($ordersCfg['value_suffix'] ?? 'pedidos este mes');
    $ordersValue = $metrics->ordersCount > 0
        ? number_format($metrics->ordersCount).' '.$ordersSuffix
        : ($ordersCfg['empty_hint'] ?? 'Aún no tienes pedidos este mes');

    $benefitsValue = number_format($metrics->benefitsCount).' '.($benefitsCfg['value_suffix'] ?? 'beneficios');
    $benefitsCta = $benefitsCfg['cta_label'] ?? null;
    $discountLocked = (bool) ($discountCfg['locked'] ?? false);
    $openBenefitsModal = $tier->hasBenefitsModal();
@endphp

<div class="tier-metrics-grid" aria-label="Indicadores de tu nivel">
    <x-ui.kpi-card
        :label="$savingsCfg['label'] ?? 'Ahorro'"
        :value="$savingsValue"
        :trend="$formatTrend($metrics->savingsTrendPercent())"
        :hint="$savingsHint"
        accent="success"
        :compact="true"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </x-slot:icon>
    </x-ui.kpi-card>

    <x-ui.kpi-card
        :label="$discountCfg['label'] ?? 'Descuento'"
        :value="$discountValue"
        :hint="$discountCfg['hint'] ?? null"
        :accent="$discountLocked ? 'warning' : 'brand'"
        :compact="true"
    >
        <x-slot:icon>
            @if($discountLocked)
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 5 5 19"/><circle cx="7.5" cy="7.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>
            @endif
        </x-slot:icon>
    </x-ui.kpi-card>

    <x-ui.kpi-card
        :label="$ordersCfg['label'] ?? 'Pedidos'"
        :value="$ordersValue"
        :trend="$formatTrend($metrics->ordersTrendPercent())"
        accent="info"
        :compact="true"
        :href="route('empresa.orders.index')"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
        </x-slot:icon>
    </x-ui.kpi-card>

    @if($openBenefitsModal)
        <button
            type="button"
            class="card overflow-hidden p-4 text-left transition hover:-translate-y-0.5 hover:shadow-soft"
            @click="$dispatch('open-modal', 'tier-upgrade')"
        >
            <div class="flex items-start justify-between gap-2">
                <div class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 12v8H4v-8"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H8.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h3.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7z"/></svg>
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-amber-800/80">{{ $benefitsCfg['label'] ?? 'Beneficios de tu nivel' }}</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ $benefitsValue }}</p>
            <p class="mt-1 text-xs font-semibold text-brand-dark">{{ $benefitsCta ?? 'Ver beneficios' }} →</p>
        </button>
    @else
        <x-ui.kpi-card
            :label="$benefitsCfg['label'] ?? 'Beneficios de tu nivel'"
            :value="$benefitsValue"
            :hint="$benefitsCfg['hint'] ?? null"
            accent="warning"
            :compact="true"
        >
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 12v8H4v-8"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H8.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h3.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7z"/></svg>
            </x-slot:icon>
        </x-ui.kpi-card>
    @endif
</div>
