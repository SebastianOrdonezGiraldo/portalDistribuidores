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
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $kpis = [
            [
                'label' => 'Pedidos Totales',
                'value' => number_format($totals['orders']),
                'hint' => 'Histórico en plataforma',
                'href' => route('admin.orders.index'),
            ],
            [
                'label' => 'Facturación Mes',
                'value' => '$'.number_format((float) Order::query()->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total_amount'), 0, ',', '.'),
                'hint' => 'Desde '.Carbon::parse($startOfMonth)->format('d/m').' al '.Carbon::parse($endOfMonth)->format('d/m'),
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
                'value' => number_format((int) Product::query()->where('is_active', true)->count()),
                'hint' => 'Productos visibles al distribuidor',
                'href' => route('admin.products.index'),
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
            'orderStatusTotals' => $orderStatusTotals,
            'operationalAlerts' => $operationalAlerts,
            'recentOrders' => $recentOrders,
            'recentEvents' => $recentEvents,
        ]);
    }
}
