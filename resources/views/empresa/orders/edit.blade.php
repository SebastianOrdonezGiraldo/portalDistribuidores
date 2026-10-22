{{--
View contract:
- Source: App\Modules\Company\Http\Controllers\CompanyOrderController::edit.
- Expects: $order with items, $departments, and $catalogOptions.
- Owns: company page header; the form lives in orders/partials/edit-form.
- Notes: only states allowed by OrderStatus::canBeEditedByCompany reach this view; validation stays in UpdateOrderRequest.
--}}
<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Editar Cotización {{ $order->oc_number }}"
            subtitle="Ajusta datos comerciales, quita ítems y agrega productos nuevos."
        >
            <x-slot name="actions">
                <a href="{{ route('empresa.orders.show', $order) }}" class="btn btn-secondary w-full justify-center sm:w-auto">
                    Volver al detalle
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @include('orders.partials.edit-form', [
        'updateUrl' => route('empresa.orders.update', $order),
        'cancelUrl' => route('empresa.orders.show', $order),
        'showStatus' => false,
    ])
</x-app-layout>
