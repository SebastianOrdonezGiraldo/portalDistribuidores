<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:40'],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $orders = Order::query()
            ->with('distributor', 'user')
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
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statusOptions' => array_map(fn (OrderStatus $status) => $status->value, OrderStatus::cases()),
            'distributors' => Distributor::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.show', [
            'order' => $order->load('items', 'distributor', 'user'),
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
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->route('admin.orders.index')->with('status', "Pedido {$orderNumber} eliminado.");
    }
}
