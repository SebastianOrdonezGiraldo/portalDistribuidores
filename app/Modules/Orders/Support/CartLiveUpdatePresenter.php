<?php

namespace App\Modules\Orders\Support;

use App\Modules\Orders\Pricing\OrderPricingLine;
use App\Modules\Orders\Pricing\OrderPricingResult;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Support\Collection;

/**
 * Builds the JSON live-update contract from already-calculated cart pricing.
 *
 * Presentation only: does not query commerce rules or recalculate prices.
 */
final class CartLiveUpdatePresenter
{
    /**
     * @param  Collection<int, mixed>  $items
     * @return array{
     *     ok: true,
     *     empty: false,
     *     counts: array{products: int, units: int, cart: int},
     *     pricing: array{
     *         checkout_allowed: bool,
     *         gold_pricing_applied: bool,
     *         effective_total_cents: int,
     *         gold_savings_cents: int
     *     },
     *     lines: array<string, array{
     *         qty: int,
     *         unit_price_cents: int,
     *         silver_unit_price_cents: int,
     *         base_unit_price_cents: int,
     *         subtotal_cents: int,
     *         pricing_html: string,
     *         subtotal_html: string
     *     }>,
     *     html: array{
     *         commercial_status: string,
     *         order_summary: string,
     *         checkout_cta: string
     *     }
     * }
     */
    public function present(Collection $items, OrderPricingResult $pricing): array
    {
        /** @var array<string, mixed> $first */
        $first = $items->first() ?? [];
        /** @var DistributorTier $tier */
        $tier = $first['tier'] ?? DistributorTier::Silver;
        $isGold = $tier === DistributorTier::Gold;
        $goldPricingApplied = $pricing->goldPricingApplied;
        $goldSavings = (float) $pricing->goldSavingsDecimal();
        $hasGoldSavings = $goldPricingApplied && $goldSavings > 0.5;
        $grossTotal = (float) $pricing->effectiveTotalDecimal();
        $productsSubtotal = $hasGoldSavings
            ? (float) $pricing->silverCandidateTotalDecimal()
            : $grossTotal;

        $goldCandidatePesos = (float) $pricing->goldCandidateTotalDecimal();
        $potentialSavings = ! $isGold ? max(0, $grossTotal - $goldCandidatePesos) : 0.0;
        $potentialSavingsPct = $grossTotal > 0 ? (int) round(($potentialSavings / $grossTotal) * 100) : 0;
        $hasPotentialSavings = ! $isGold && $potentialSavings > 0.5;

        $unitsCount = (int) $items->sum(function (mixed $item): int {
            /** @var array<string, mixed> $item */
            return (int) ($item['qty'] ?? 0);
        });
        $productsCount = $items->count();

        $linesPayload = [];
        $pricingLines = $pricing->lines->values();

        foreach ($items->values() as $index => $item) {
            /** @var array<string, mixed> $item */
            /** @var OrderPricingLine|null $pricedLine */
            $pricedLine = $pricingLines->get($index);
            $lineKey = (string) $item['line_key'];

            if ($pricedLine === null) {
                continue;
            }

            $linesPayload[$lineKey] = [
                'qty' => $pricedLine->qty,
                'unit_price_cents' => $pricedLine->effectiveUnitPriceCents,
                'silver_unit_price_cents' => $pricedLine->silverUnitPriceCents,
                'base_unit_price_cents' => $pricedLine->goldUnitPriceCents,
                'subtotal_cents' => $pricedLine->effectiveSubtotalCents,
                'pricing_html' => view('components.cart.line-pricing', [
                    'unitPrice' => (float) $pricedLine->effectiveUnitPriceDecimal(),
                    'silverUnitPrice' => (float) $pricedLine->silverUnitPriceDecimal(),
                    'vatLabel' => $item['vat_label'],
                    'goldPricingApplied' => $goldPricingApplied,
                    'lineSavings' => (float) $pricedLine->lineSavingsDecimal(),
                ])->render(),
                'subtotal_html' => view('components.cart.line-subtotal', [
                    'subtotal' => (float) $pricedLine->effectiveSubtotalDecimal(),
                    'vatLabel' => $item['vat_label'],
                ])->render(),
            ];
        }

        return [
            'ok' => true,
            'empty' => false,
            'counts' => [
                'products' => $productsCount,
                'units' => $unitsCount,
                'cart' => $unitsCount,
            ],
            'pricing' => [
                'checkout_allowed' => $pricing->checkoutAllowed(),
                'gold_pricing_applied' => $goldPricingApplied,
                'effective_total_cents' => $pricing->effectiveTotalCents,
                'gold_savings_cents' => $pricing->goldSavingsCents,
            ],
            'lines' => $linesPayload,
            'html' => [
                'commercial_status' => view('components.commerce.order-commercial-status', [
                    'pricing' => $pricing,
                    'tier' => $tier,
                    'context' => 'cart',
                ])->render(),
                'order_summary' => view('components.cart.order-summary', [
                    'productsSubtotal' => $productsSubtotal,
                    'grossTotal' => $grossTotal,
                    'goldPricingApplied' => $goldPricingApplied,
                    'goldSavings' => $goldSavings,
                    'isGold' => $isGold,
                    'hasPotentialSavings' => $hasPotentialSavings,
                    'potentialSavings' => $potentialSavings,
                    'potentialSavingsPct' => $potentialSavingsPct,
                ])->render(),
                'checkout_cta' => view('components.cart.checkout-cta', [
                    'checkoutAllowed' => $pricing->checkoutAllowed(),
                ])->render(),
            ],
        ];
    }
}
