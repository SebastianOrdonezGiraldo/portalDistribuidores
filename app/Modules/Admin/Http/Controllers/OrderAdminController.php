<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\UpdateAdminOrderRequest;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Actions\UpdateOrderAction;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $statusOptions = array_map(fn (OrderStatus $status) => $status->value, OrderStatus::cases());
        $sortOptions = ['newest', 'oldest', 'amount_desc', 'amount_asc'];
        $perPageOptions = [20, 50, 100];

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', Rule::in($sortOptions)],
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
        ]);

        $filters = array_merge([
            'q' => null,
            'status' => null,
            'distributor_id' => null,
            'date_from' => null,
            'date_to' => null,
            'sort' => 'newest',
            'per_page' => 20,
        ], $filters);

        $filteredQuery = Order::query()
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('oc_number', 'like', "%{$term}%")
                        ->orWhere('company_name', 'like', "%{$term}%")
                        ->orWhere('contact_name', 'like', "%{$term}%")
                        ->orWhere('contact_email', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['distributor_id']), fn ($query) => $query->where('distributor_id', $filters['distributor_id']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']));

        $orders = (clone $filteredQuery)
            ->with('distributor', 'user')
            ->when($filters['sort'] === 'newest', fn ($query) => $query->latest())
            ->when($filters['sort'] === 'oldest', fn ($query) => $query->oldest())
            ->when($filters['sort'] === 'amount_desc', fn ($query) => $query->orderByDesc('total_amount')->orderByDesc('created_at'))
            ->when($filters['sort'] === 'amount_asc', fn ($query) => $query->orderBy('total_amount')->orderByDesc('created_at'))
            ->paginate((int) $filters['per_page'])
            ->withQueryString();

        $statusCounts = (clone $filteredQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusSummary = collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status) => [$status->value => (int) ($statusCounts[$status->value] ?? 0)]);

        $metrics = [
            'total_orders' => (clone $filteredQuery)->count(),
            'total_amount' => (float) (clone $filteredQuery)->sum('total_amount'),
            'pending_pdf' => (clone $filteredQuery)->whereNull('pdf_path')->count(),
            'in_progress' => (int) (($statusSummary[OrderStatus::Sold->value] ?? 0) + ($statusSummary[OrderStatus::Dispatched->value] ?? 0)),
        ];

        $activeFiltersCount = collect([
            $filters['q'],
            $filters['status'],
            $filters['distributor_id'],
            $filters['date_from'],
            $filters['date_to'],
        ])->filter(fn ($value) => filled($value))->count();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            'distributors' => Distributor::query()->orderBy('name')->get(['id', 'name']),
            'statusSummary' => $statusSummary,
            'metrics' => $metrics,
            'activeFiltersCount' => $activeFiltersCount,
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items', 'distributor', 'user', 'statusHistory.actor');

        $items = $order->items;

        $totals = [
            'items_count' => $items->count(),
            'units_total' => (float) $items->sum('qty'),
            'subtotals_total' => (float) $items->sum('subtotal'),
            'average_unit_price' => $items->isNotEmpty()
                ? (float) $items->avg('price_each')
                : 0.0,
        ];

        $hasFinancialGap = abs($totals['subtotals_total'] - (float) $order->total_amount) > 0.01;
        $nextStatuses = collect($order->status->nextAllowedStatuses())
            ->map(fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'requires_note' => $status->requiresTransitionNote(),
            ])
            ->values();

        return view('admin.orders.show', [
            'order' => $order,
            'totals' => $totals,
            'hasFinancialGap' => $hasFinancialGap,
            'nextStatuses' => $nextStatuses,
        ]);
    }

    public function edit(Order $order): View|RedirectResponse
    {
        $this->authorize('update', $order);

        if (! $order->status->canBeEditedByAdmin()) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->withErrors('Este pedido no puede editarse en su estado actual.');
        }

        $order->load('items');

        return view('admin.orders.edit', [
            'order' => $order,
            'departments' => config('locations.colombia_departments', []),
        ]);
    }

    public function update(
        UpdateAdminOrderRequest $request,
        Order $order,
        UpdateOrderAction $updateOrderAction,
    ): RedirectResponse {
        $this->authorize('update', $order);

        if (! $order->status->canBeEditedByAdmin()) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->withErrors('Este pedido no puede editarse en su estado actual.');
        }

        try {
            $order = $updateOrderAction->execute($order, $request->validated(), $request->user(), true);
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->getMessage());
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', "Pedido {$order->oc_number} actualizado correctamente.");
    }

    public function updateStatus(
        Request $request,
        Order $order,
        OrderStatusTransitionService $transitionService,
    ): RedirectResponse {
        $this->authorize('update', $order);

        $payload = $request->validate([
            'status' => ['required', 'string', Rule::in(array_map(fn (OrderStatus $status) => $status->value, OrderStatus::cases()))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $targetStatus = OrderStatus::from($payload['status']);

        /** @var \App\Models\User $actor */
        $actor = $request->user();

        try {
            $transitionService->transition(
                $order,
                $targetStatus,
                $actor,
                $payload['note'] ?? null
            );
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->getMessage());
        }

        return back()->with('status', "Pedido {$order->oc_number} actualizado a {$targetStatus->label()}.");
    }

    public function downloadPdf(Order $order, OrderPdfGenerator $pdfGenerator): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $order);

        $path = $pdfGenerator->generate($order);

        if ($order->pdf_path !== $path) {
            $order->update(['pdf_path' => $path]);
        }

        $disk = Storage::disk(OrderPdfGenerator::diskName());

        if (! $disk->exists($path)) {
            GenerateOrderPdfJob::dispatch($order->id);

            return back()->withErrors('PDF en generación. Intenta nuevamente en unos segundos.');
        }

        return $disk->download($path, $order->oc_number.'.pdf');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $pathsToDelete = collect([
            $order->pdf_path,
            'orders/'.$order->oc_number.'.pdf',
        ])->filter()->unique()->values();

        $orderNumber = $order->oc_number;

        try {
            $order->delete();
        } catch (QueryException $exception) {
            $sqlState = (string) ($exception->errorInfo[0] ?? '');

            if (in_array($sqlState, ['23000', '23001', '23503'], true)) {
                return back()->with('error', 'No se pudo eliminar el pedido porque está relacionado con otros registros.');
            }

            throw $exception;
        }

        foreach ($pathsToDelete as $path) {
            foreach (collect([OrderPdfGenerator::diskName(), 'public'])->unique() as $diskName) {
                if (Storage::disk($diskName)->exists($path)) {
                    Storage::disk($diskName)->delete($path);
                }
            }
        }

        return redirect()->route('admin.orders.index')->with('status', "Pedido {$orderNumber} eliminado.");
    }
}
