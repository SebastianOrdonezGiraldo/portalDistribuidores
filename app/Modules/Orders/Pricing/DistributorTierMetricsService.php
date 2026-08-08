<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Aggregates monthly distributor metrics used by the tier catalog experience.
 *
 * Returns a single raw delta for savings: the gold-discount gap persisted on
 * order lines via snapshot columns. Presentation (real vs potential framing)
 * lives in config/commerce.php presentation — not here.
 *
 * Historical lines without base/silver snapshots contribute $0 (no live
 * recalculation with current discount rates).
 */
final class DistributorTierMetricsService
{
    public function __construct(
        private readonly DistributorPriceCalculator $priceCalculator,
    ) {}

    public function forDistributor(Distributor $distributor, ?Carbon $at = null): DistributorTierMetrics
    {
        $at ??= now();

        $currentStart = $at->copy()->startOfMonth();
        $currentEnd = $at->copy()->endOfMonth();
        $previousStart = $at->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $at->copy()->subMonthNoOverflow()->endOfMonth();

        $currentOrders = $this->countCompletedOrders($distributor->id, $currentStart, $currentEnd);
        $previousOrders = $this->countCompletedOrders($distributor->id, $previousStart, $previousEnd);

        $currentSavings = $this->sumSavingsCents($distributor->id, $currentStart, $currentEnd);
        $previousSavings = $this->sumSavingsCents($distributor->id, $previousStart, $previousEnd);

        return new DistributorTierMetrics(
            savingsCents: $currentSavings,
            previousSavingsCents: $previousSavings,
            ordersCount: $currentOrders,
            previousOrdersCount: $previousOrders,
            discountPercent: (int) config('commerce.tiers.gold_discount_percent', 5),
            benefitsCount: $distributor->tier->benefitsCount(),
        );
    }

    private function countCompletedOrders(int $distributorId, Carbon $from, Carbon $to): int
    {
        return $this->completedOrdersQuery($distributorId, $from, $to)->count();
    }

    /**
     * Gold-discount delta from persisted snapshots only.
     *
     * Prefers (silver_unit_price - base_unit_price) * qty when both snapshots
     * exist — this equals line_savings for Gold orders and is the potential
     * Gold savings for Silver orders. Lines without snapshots contribute 0.
     */
    private function sumSavingsCents(int $distributorId, Carbon $from, Carbon $to): int
    {
        $orderIds = $this->completedOrdersQuery($distributorId, $from, $to)->pluck('id');

        if ($orderIds->isEmpty()) {
            return 0;
        }

        $rows = OrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->whereNotNull('base_unit_price')
            ->whereNotNull('silver_unit_price')
            ->get(['qty', 'base_unit_price', 'silver_unit_price', 'line_savings']);

        $totalCents = 0;

        foreach ($rows as $row) {
            $qty = max(1, (int) $row->qty);
            $baseCents = $this->priceCalculator->decimalToCents((string) $row->base_unit_price);
            $silverCents = $this->priceCalculator->decimalToCents((string) $row->silver_unit_price);
            $unitDelta = max(0, $silverCents - $baseCents);

            // Fallback to persisted line_savings when delta is unexpectedly zero
            // but a positive line_savings was stored (defensive for edge data).
            if ($unitDelta === 0 && $row->line_savings !== null) {
                $totalCents += max(0, $this->priceCalculator->decimalToCents((string) $row->line_savings));

                continue;
            }

            $totalCents += $unitDelta * $qty;
        }

        return $totalCents;
    }

    /**
     * @return Builder<Order>
     */
    private function completedOrdersQuery(int $distributorId, Carbon $from, Carbon $to): Builder
    {
        return Order::query()
            ->where('distributor_id', $distributorId)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', array_map(
                static fn (OrderStatus $status) => $status->value,
                OrderStatus::commerciallyCounted(),
            ));
    }
}
