<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totals = [
            'products' => Product::query()->count(),
            'categories' => \App\Modules\Categories\Models\Category::query()->count(),
            'distributors' => Distributor::query()->count(),
            'users' => User::query()->count(),
            'orders' => Order::query()->count(),
        ];

        $statusCounts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $orderStatusTotals = collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status) => [$status->value => (int) ($statusCounts[$status->value] ?? 0)]);

        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $currentMonthRevenue = (float) Order::query()
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('total_amount');

        $previousMonthRevenue = (float) Order::query()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('total_amount');

        $currentMonthOrders = (int) Order::query()
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();

        $previousMonthOrders = (int) Order::query()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $monthRangeLabel = 'Desde '.$currentMonthStart->format('d/m').' al '.$currentMonthEnd->format('d/m');

        $kpis = [
            [
                'label' => 'Pedidos Totales',
                'value' => number_format($totals['orders']),
                'trend' => $this->formatTrend($currentMonthOrders, $previousMonthOrders, 'vs mes anterior'),
                'hint' => 'Histórico en plataforma',
                'href' => route('admin.orders.index'),
            ],
            [
                'label' => 'Facturación Mes',
                'value' => '$'.number_format($currentMonthRevenue, 0, ',', '.'),
                'trend' => $this->formatTrend($currentMonthRevenue, $previousMonthRevenue, 'vs mes anterior'),
                'hint' => $monthRangeLabel,
                'href' => route('admin.orders.index'),
            ],
            [
                'label' => 'Distribuidores Activos',
                'value' => number_format((int) Distributor::query()->where('status', 'active')->count()),
                'hint' => 'Con acceso operativo vigente',
                'href' => route('admin.distributors.index'),
            ],
            [
                'label' => 'Catálogo Activo',
                'value' => number_format((int) Product::active()->count()),
                'hint' => 'Productos visibles al distribuidor',
                'href' => route('admin.products.index'),
            ],
        ];

        $operationalSummary = [
            [
                'label' => 'Categorías',
                'value' => number_format($totals['categories']),
                'hint' => 'Estructura del catálogo',
                'href' => route('admin.categories.index'),
            ],
            [
                'label' => 'Productos',
                'value' => number_format($totals['products']),
                'hint' => 'Registros en catálogo',
                'href' => route('admin.products.index'),
            ],
            [
                'label' => 'Distribuidores',
                'value' => number_format($totals['distributors']),
                'hint' => 'Empresas vinculadas',
                'href' => route('admin.distributors.index'),
            ],
            [
                'label' => 'Usuarios',
                'value' => number_format($totals['users']),
                'hint' => 'Accesos registrados',
                'href' => route('admin.users.index'),
            ],
        ];

        $operationalAlerts = collect([
            [
                'variant' => 'warning',
                'title' => 'Pedidos sin PDF',
                'description' => 'Revisar cola de generación documental para pedidos pendientes.',
                'count' => Order::query()->whereNull('pdf_path')->count(),
            ],
            [
                'variant' => 'info',
                'title' => 'Pedidos en procesamiento',
                'description' => 'Pedidos en transición operativa pendientes de envío final.',
                'count' => (int) ($statusCounts['sending'] ?? 0),
            ],
        ])->filter(fn (array $alert) => $alert['count'] > 0)->values();

        $statusColors = [
            'draft' => 'bg-amber-400',
            'submitted' => 'bg-emerald-400',
            'sending' => 'bg-sky-400',
            'sent' => 'bg-blue-400',
            'failed' => 'bg-rose-400',
        ];

        $maxStatusCount = max(1, $orderStatusTotals->max() ?? 0);

        $statusDistribution = collect(OrderStatus::cases())
            ->map(function (OrderStatus $status) use ($orderStatusTotals, $statusColors, $maxStatusCount, $totals) {
                $count = (int) ($orderStatusTotals[$status->value] ?? 0);
                $percentage = $totals['orders'] > 0
                    ? (int) round(($count / $totals['orders']) * 100)
                    : 0;
                $fill = (int) round(($count / $maxStatusCount) * 100);

                return [
                    'status' => $status->value,
                    'count' => $count,
                    'percentage' => $percentage,
                    'fill' => $fill,
                    'bar_class' => $statusColors[$status->value] ?? 'bg-slate-400',
                ];
            })
            ->values();

        $latestOrderAt = Order::query()->max('created_at');
        $latestCatalogUpdateAt = Product::query()->max('updated_at');

        $recentOrders = Order::query()
            ->with('distributor', 'user')
            ->latest()
            ->take(10)
            ->get();

        $recentEvents = collect()
            ->merge(
                $recentOrders->take(6)->map(fn (Order $order) => [
                    'title' => 'Pedido '.$order->oc_number,
                    'description' => ($order->distributor?->name ?? 'Distribuidor').' registró una orden.',
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ])
            )
            ->merge(
                Product::query()->latest()->take(4)->get()->map(fn (Product $product) => [
                    'title' => 'Actualización de producto '.$product->sku,
                    'description' => $product->name,
                    'status' => $product->is_active ? 'active' : 'inactive',
                    'created_at' => $product->updated_at,
                ])
            )
            ->sortByDesc('created_at')
            ->take(8)
            ->values();

        return view('admin.dashboard', [
            'totals' => $totals,
            'kpis' => $kpis,
            'operationalSummary' => $operationalSummary,
            'orderStatusTotals' => $orderStatusTotals,
            'statusDistribution' => $statusDistribution,
            'operationalAlerts' => $operationalAlerts,
            'recentOrders' => $recentOrders,
            'recentEvents' => $recentEvents,
            'monthRangeLabel' => $monthRangeLabel,
            'currentMonthOrders' => $currentMonthOrders,
            'latestOrderAt' => $latestOrderAt ? Carbon::parse($latestOrderAt) : null,
            'latestCatalogUpdateAt' => $latestCatalogUpdateAt ? Carbon::parse($latestCatalogUpdateAt) : null,
        ]);
    }

    private function formatTrend(float|int $current, float|int $previous, string $suffix): string
    {
        $currentValue = (float) $current;
        $previousValue = (float) $previous;

        if ($currentValue == 0.0 && $previousValue == 0.0) {
            return 'Sin movimiento '.$suffix;
        }

        if ($previousValue == 0.0) {
            return '+100% '.$suffix;
        }

        $delta = (($currentValue - $previousValue) / $previousValue) * 100;
        $prefix = $delta >= 0 ? '+' : '';

        return $prefix.number_format($delta, 1, ',', '.').'% '.$suffix;
    }
}
