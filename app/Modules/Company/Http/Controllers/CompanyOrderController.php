<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $statusOptions = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::cases());

        $filters = $request->validate([
            'q'      => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
        ]);

        $filters = array_merge(['q' => null, 'status' => null], $filters);

        $baseQuery = Order::query()
            ->where('distributor_id', $user->distributor_id)
            ->with('user')
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);
                $query->where(function ($sub) use ($term) {
                    $sub->where('oc_number', 'like', "%{$term}%")
                        ->orWhere('company_name', 'like', "%{$term}%")
                        ->orWhere('contact_name', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']));

        $orders = (clone $baseQuery)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusSummary = collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => (int) ($statusCounts[$s->value] ?? 0)]);

        $metrics = [
            'total'   => (clone $baseQuery)->count(),
            'amount'  => (float) (clone $baseQuery)->sum('total_amount'),
        ];

        return view('empresa.orders.index', [
            'orders'        => $orders,
            'filters'       => $filters,
            'statusOptions' => $statusOptions,
            'statusSummary' => $statusSummary,
            'metrics'       => $metrics,
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items', 'distributor', 'user');

        $items  = $order->items;
        $totals = [
            'items_count'        => $items->count(),
            'units_total'        => (float) $items->sum('qty'),
            'subtotals_total'    => (float) $items->sum('subtotal'),
            'average_unit_price' => $items->isNotEmpty() ? (float) $items->avg('price_each') : 0.0,
        ];

        $hasFinancialGap = abs($totals['subtotals_total'] - (float) $order->total_amount) > 0.01;

        return view('empresa.orders.show', [
            'order'           => $order,
            'totals'          => $totals,
            'hasFinancialGap' => $hasFinancialGap,
        ]);
    }

    public function downloadPdf(Order $order, OrderPdfGenerator $pdfGenerator): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $order);

        $path = $pdfGenerator->generate($order);

        if ($order->pdf_path !== $path) {
            $order->update(['pdf_path' => $path]);
        }

        if (! Storage::disk('public')->exists($path)) {
            GenerateOrderPdfJob::dispatch($order->id);

            return back()->withErrors('PDF en generación. Intenta nuevamente en unos segundos.');
        }

        return Storage::disk('public')->download($path, $order->oc_number.'.pdf');
    }
}
