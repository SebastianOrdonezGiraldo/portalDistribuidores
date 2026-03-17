<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardDataService
{
    private const CACHE_TTL_SECONDS = 300;

    public function getData(): array
    {
        return Cache::remember('admin.dashboard.data', self::CACHE_TTL_SECONDS, fn () => $this->build());
    }

    private function build(): array
    {
        $totals = $this->getTotals();
        $monthlyMetrics = $this->getMonthlyMetrics();
        $orderStatusTotals = $this->getOrderStatusTotals();

        return [
            'totals'            => $totals,
            'kpis'              => $this->buildKpis($totals, $monthlyMetrics, $orderStatusTotals),
            'operationalSummary' => $this->buildOperationalSummary($totals),
            'orderStatusTotals' => $orderStatusTotals,
            'statusDistribution' => $this->buildStatusDistribution($orderStatusTotals, $totals),
            'operationalAlerts' => $this->buildOperationalAlerts($orderStatusTotals),
            'recentOrders'      => $this->getRecentOrders(),
            'recentEvents'      => $this->getRecentEvents(),
            'monthRangeLabel'   => $monthlyMetrics['range_label'],
            'currentMonthOrders' => $monthlyMetrics['current_orders'],
            'latestOrderAt'     => $this->getLatestOrderAt(),
            'latestCatalogUpdateAt' => $this->getLatestCatalogUpdateAt(),
        ];
    }

    private function getTotals(): array
    {
        return [
            'products'     => Product::query()->count(),
            'categories'   => \App\Modules\Categories\Models\Category::query()->count(),
            'distributors' => Distributor::query()->count(),
            'users'        => User::query()->count(),
            'orders'       => Order::query()->count(),
        ];
    }

    private function getMonthlyMetrics(): array
    {
        $now = now();
        $currentStart  = $now->copy()->startOfMonth();
        $currentEnd    = $now->copy()->endOfMonth();
        $previousStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd   = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $current = Order::query()
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->selectRaw('SUM(total_amount) as revenue, COUNT(*) as orders')
            ->first();

        $previous = Order::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->selectRaw('SUM(total_amount) as revenue, COUNT(*) as orders')
            ->first();

        return [
            'current_revenue'  => (float) ($current?->revenue ?? 0),
            'previous_revenue' => (float) ($previous?->revenue ?? 0),
            'current_orders'   => (int) ($current?->orders ?? 0),
            'previous_orders'  => (int) ($previous?->orders ?? 0),
            'range_label'      => 'Desde '.$currentStart->format('d/m').' al '.$currentEnd->format('d/m'),
        ];
    }

    private function getOrderStatusTotals(): \Illuminate\Support\Collection
    {
        $statusCounts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => (int) ($statusCounts[$s->value] ?? 0)]);
    }

    private function buildKpis(array $totals, array $monthly, \Illuminate\Support\Collection $statusTotals): array
    {
        return [
            [
                'label' => 'Pedidos Totales',
                'value' => number_format($totals['orders']),
                'trend' => $this->formatTrend($monthly['current_orders'], $monthly['previous_orders'], 'vs mes anterior'),
                'hint'  => 'Histórico en plataforma',
                'href'  => route('admin.orders.index'),
            ],
            [
                'label' => 'Facturación Mes',
                'value' => '$'.number_format($monthly['current_revenue'], 0, ',', '.'),
                'trend' => $this->formatTrend($monthly['current_revenue'], $monthly['previous_revenue'], 'vs mes anterior'),
                'hint'  => $monthly['range_label'],
                'href'  => route('admin.orders.index'),
            ],
            [
                'label' => 'Distribuidores Activos',
                'value' => number_format((int) Distributor::query()->where('status', DistributorStatus::Active)->count()),
                'hint'  => 'Con acceso operativo vigente',
                'href'  => route('admin.distributors.index'),
            ],
            [
                'label' => 'Catálogo Activo',
                'value' => number_format((int) Product::active()->count()),
                'hint'  => 'Productos visibles al distribuidor',
                'href'  => route('admin.products.index'),
            ],
        ];
    }

    private function buildOperationalSummary(array $totals): array
    {
        return [
            ['label' => 'Categorías',    'value' => number_format($totals['categories']),   'hint' => 'Estructura del catálogo',  'href' => route('admin.categories.index')],
            ['label' => 'Productos',     'value' => number_format($totals['products']),      'hint' => 'Registros en catálogo',    'href' => route('admin.products.index')],
            ['label' => 'Distribuidores','value' => number_format($totals['distributors']),  'hint' => 'Empresas vinculadas',      'href' => route('admin.distributors.index')],
            ['label' => 'Usuarios',      'value' => number_format($totals['users']),         'hint' => 'Accesos registrados',      'href' => route('admin.users.index')],
        ];
    }

    private function buildStatusDistribution(\Illuminate\Support\Collection $orderStatusTotals, array $totals): \Illuminate\Support\Collection
    {
        $maxStatusCount = max(1, $orderStatusTotals->max() ?? 0);

        return collect(OrderStatus::cases())
            ->map(function (OrderStatus $status) use ($orderStatusTotals, $maxStatusCount, $totals) {
                $count      = (int) ($orderStatusTotals[$status->value] ?? 0);
                $percentage = $totals['orders'] > 0
                    ? (int) round(($count / $totals['orders']) * 100)
                    : 0;
                $fill = (int) round(($count / $maxStatusCount) * 100);

                return [
                    'status'     => $status->value,
                    'count'      => $count,
                    'percentage' => $percentage,
                    'fill'       => $fill,
                    'bar_class'  => $status->badgeClass(),
                ];
            })
            ->values();
    }

    private function buildOperationalAlerts(\Illuminate\Support\Collection $statusCounts): \Illuminate\Support\Collection
    {
        return collect([
            [
                'variant'     => 'warning',
                'title'       => 'Pedidos sin PDF',
                'description' => 'Revisar cola de generación documental para pedidos pendientes.',
                'count'       => Order::query()->whereNull('pdf_path')->count(),
            ],
            [
                'variant'     => 'info',
                'title'       => 'Pedidos en procesamiento',
                'description' => 'Pedidos en transición operativa pendientes de envío final.',
                'count'       => (int) ($statusCounts[OrderStatus::Sending->value] ?? 0),
            ],
        ])->filter(fn (array $alert) => $alert['count'] > 0)->values();
    }

    private function getRecentOrders(): \Illuminate\Database\Eloquent\Collection
    {
        return Order::query()
            ->with('distributor', 'user')
            ->latest()
            ->take(10)
            ->get();
    }

    private function getRecentEvents(): \Illuminate\Support\Collection
    {
        $recentOrders = Order::query()->with('distributor', 'user')->latest()->take(6)->get();

        return collect()
            ->merge(
                $recentOrders->map(fn (Order $order) => [
                    'title'       => 'Pedido '.$order->oc_number,
                    'description' => ($order->distributor?->name ?? 'Distribuidor').' registró una orden.',
                    'status'      => $order->status,
                    'created_at'  => $order->created_at,
                ])
            )
            ->merge(
                Product::query()->latest()->take(4)->get()->map(fn (Product $product) => [
                    'title'       => 'Actualización de producto '.$product->sku,
                    'description' => $product->name,
                    'status'      => $product->is_active ? 'active' : 'inactive',
                    'created_at'  => $product->updated_at,
                ])
            )
            ->sortByDesc('created_at')
            ->take(8)
            ->values();
    }

    private function getLatestOrderAt(): ?Carbon
    {
        $value = Order::query()->max('created_at');

        return $value ? Carbon::parse($value) : null;
    }

    private function getLatestCatalogUpdateAt(): ?Carbon
    {
        $value = Product::query()->max('updated_at');

        return $value ? Carbon::parse($value) : null;
    }

    private function formatTrend(float|int $current, float|int $previous, string $suffix): string
    {
        $current  = (float) $current;
        $previous = (float) $previous;

        if ($current == 0.0 && $previous == 0.0) {
            return 'Sin movimiento '.$suffix;
        }

        if ($previous == 0.0) {
            return '+100% '.$suffix;
        }

        $delta  = (($current - $previous) / $previous) * 100;
        $prefix = $delta >= 0 ? '+' : '';

        return $prefix.number_format($delta, 1, ',', '.').'% '.$suffix;
    }
}
