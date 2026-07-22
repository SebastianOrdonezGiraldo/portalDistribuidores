<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CompanyDashboardController extends Controller
{
    /**
     * Ver dashboard de empresa.
     *
     * @group Empresa
     *
     * @authenticated
     *
     * @response 200 {"content":"Vista HTML con KPIs y pedidos recientes"}
     * @response 403 {"message":"Rol no autorizado"}
     */
    public function __invoke(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $distributor = $user->distributor;

        $baseQuery = Order::query()->where('distributor_id', $user->distributor_id);

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $orderStatusTotals = collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => (int) ($statusCounts[$s->value] ?? 0)]);

        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $currentMonthOrders = (int) (clone $baseQuery)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();

        $previousMonthOrders = (int) (clone $baseQuery)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $currentMonthAmount = (float) (clone $baseQuery)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('total_amount');

        $previousMonthAmount = (float) (clone $baseQuery)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('total_amount');

        $totalOrders = (int) (clone $baseQuery)->count();
        $totalAmount = (float) (clone $baseQuery)->sum('total_amount');

        $kpis = [
            [
                'label' => 'Pedidos del mes',
                'value' => number_format($currentMonthOrders),
                'trend' => $this->formatTrend($currentMonthOrders, $previousMonthOrders, 'vs mes anterior'),
                'hint' => 'Cotizaciones generadas',
                'href' => route('empresa.orders.index'),
            ],
            [
                'label' => 'Monto del mes',
                'value' => '$'.number_format($currentMonthAmount, 0, ',', '.'),
                'trend' => $this->formatTrend($currentMonthAmount, $previousMonthAmount, 'vs mes anterior'),
                'hint' => $currentMonthStart->format('d/m').' – '.$currentMonthEnd->format('d/m'),
                'href' => route('empresa.orders.index'),
            ],
            [
                'label' => 'Total histórico',
                'value' => number_format($totalOrders),
                'hint' => 'Desde el inicio',
                'href' => route('empresa.orders.index'),
            ],
            [
                'label' => 'Monto histórico',
                'value' => '$'.number_format($totalAmount, 0, ',', '.'),
                'hint' => 'Valor acumulado',
                'href' => route('empresa.orders.index'),
            ],
        ];

        $maxCount = max(1, $orderStatusTotals->max() ?? 0);

        $statusColors = [
            'draft' => 'bg-amber-400',
            'pending_approval' => 'bg-violet-400',
            'submitted' => 'bg-emerald-400',
            'sold' => 'bg-cyan-500',
            'dispatched' => 'bg-sky-500',
            'delivered' => 'bg-blue-500',
            'rejected' => 'bg-red-500',
            'cancelled' => 'bg-slate-500',
            'sending' => 'bg-sky-400',
            'sent' => 'bg-blue-400',
            'failed' => 'bg-rose-400',
        ];

        $statusDistribution = collect(OrderStatus::cases())->map(function (OrderStatus $s) use ($orderStatusTotals, $statusColors, $maxCount, $totalOrders) {
            $count = (int) ($orderStatusTotals[$s->value] ?? 0);

            return [
                'status' => $s->value,
                'count' => $count,
                'percentage' => $totalOrders > 0 ? (int) round(($count / $totalOrders) * 100) : 0,
                'fill' => (int) round(($count / $maxCount) * 100),
                'bar_class' => $statusColors[$s->value] ?? 'bg-slate-400',
            ];
        })->values();

        $recentOrders = (clone $baseQuery)
            ->latest()
            ->take(5)
            ->get();

        $latestOrderWithPdf = (clone $baseQuery)
            ->whereNotNull('pdf_path')
            ->latest()
            ->first();

        $monthRangeLabel = $currentMonthStart->format('d/m').' al '.$currentMonthEnd->format('d/m');
        $latestOrderAt = (clone $baseQuery)->max('created_at');

        return view('empresa.dashboard', [
            'distributor' => $distributor,
            'kpis' => $kpis,
            'statusDistribution' => $statusDistribution,
            'recentOrders' => $recentOrders,
            'latestOrderWithPdf' => $latestOrderWithPdf,
            'monthRangeLabel' => $monthRangeLabel,
            'currentMonthOrders' => $currentMonthOrders,
            'latestOrderAt' => $latestOrderAt ? Carbon::parse($latestOrderAt) : null,
            'totalOrders' => $totalOrders,
        ]);
    }

    private function formatTrend(float|int $current, float|int $previous, string $suffix): string
    {
        $cur = (float) $current;
        $prev = (float) $previous;

        if ($cur === 0.0 && $prev === 0.0) {
            return 'Sin movimiento '.$suffix;
        }

        if ($prev === 0.0) {
            return '+100% '.$suffix;
        }

        $delta = (($cur - $prev) / $prev) * 100;
        $prefix = $delta >= 0 ? '+' : '';

        return $prefix.number_format($delta, 1, ',', '.').'% '.$suffix;
    }
}
