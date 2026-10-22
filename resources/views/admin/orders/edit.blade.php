{{--
View contract:
- Source: App\Modules\Admin\Http\Controllers\OrderAdminController::edit.
- Expects: $order with items, $departments, and $catalogOptions for adding products.
- Owns: admin page header; the form lives in orders/partials/edit-form.
- Notes: editable-state checks and update validation stay in OrderAdminController/UpdateOrderRequest/UpdateOrderAction.
--}}
<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Editar Pedido {{ $order->oc_number }}"
            subtitle="Ajusta datos comerciales e ítems para actualizar la cotización."
        >
            <x-slot name="actions">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary w-full justify-center sm:w-auto">
                    Volver al detalle
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @include('orders.partials.edit-form', [
        'updateUrl' => route('admin.orders.update', $order),
        'cancelUrl' => route('admin.orders.show', $order),
        'showStatus' => true,
    ])
</x-app-layout>
