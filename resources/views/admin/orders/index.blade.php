<x-app-layout>
    @php
        $filters = array_merge([
            'q' => null,
            'status' => null,
            'distributor_id' => null,
            'date_from' => null,
            'date_to' => null,
        ], $filters ?? []);
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="Listado de Pedidos" subtitle="Búsqueda operativa por cliente, estado y rango de fechas para seguimiento comercial.">
            <x-slot name="actions">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Limpiar filtros</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.filter-bar method="GET" action="{{ route('admin.orders.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <div class="xl:col-span-2">
            <label class="form-label" for="orders-q">Buscar</label>
            <x-ui.input id="orders-q" name="q" :value="$filters['q']" placeholder="CTC, cliente, contacto o email" />
        </div>

        <div>
            <label class="form-label" for="orders-status">Estado</label>
            <x-ui.select id="orders-status" name="status">
                <option value="">Todos</option>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="orders-distributor">Cliente</label>
            <x-ui.select id="orders-distributor" name="distributor_id">
                <option value="">Todos</option>
                @foreach($distributors as $distributor)
                    <option value="{{ $distributor->id }}" @selected((string) $filters['distributor_id'] === (string) $distributor->id)>{{ $distributor->name }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="orders-date-from">Fecha desde</label>
            <x-ui.input id="orders-date-from" name="date_from" type="date" :value="$filters['date_from']" />
        </div>

        <div>
            <label class="form-label" for="orders-date-to">Fecha hasta</label>
            <x-ui.input id="orders-date-to" name="date_to" type="date" :value="$filters['date_to']" />
        </div>

        <div class="xl:col-span-6 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span>Pedidos listados: <strong class="text-slate-700">{{ $orders->total() }}</strong></span>
                <span>Seleccionados: <strong data-bulk-count>0</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" data-bulk-copy>Copiar CTC</x-ui.button>
                <x-ui.button type="submit" variant="primary">Aplicar filtros</x-ui.button>
            </div>
        </div>
    </x-ui.filter-bar>

    <section data-bulk-table class="mt-4">
        @if($orders->isEmpty())
            <x-ui.empty-state title="No se encontraron pedidos" description="Ajusta el filtro de estado, fechas o cliente para encontrar resultados.">
                <x-slot name="action">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-primary">Restablecer búsqueda</a>
                </x-slot>
            </x-ui.empty-state>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="w-10"><input type="checkbox" class="form-checkbox" data-bulk-master></th>
                        <th>CTC</th>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Documento</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td><input type="checkbox" class="form-checkbox" data-bulk-row value="{{ $order->oc_number }}"></td>
                            <td>
                                <p class="font-semibold text-slate-900">{{ $order->oc_number }}</p>
                                <p class="text-xs text-slate-500">{{ $order->user?->email }}</p>
                            </td>
                            <td>
                                <p class="font-medium text-slate-900">{{ $order->company_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->distributor?->name ?? 'Sin distribuidor' }}</p>
                            </td>
                            <td>
                                <p class="text-sm text-slate-900">{{ $order->contact_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->contact_email }}</p>
                            </td>
                            <td><x-ui.status-badge :status="$order->status" /></td>
                            <td class="font-medium text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                            <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($order->pdf_path)
                                    <x-ui.badge variant="success">Disponible</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning">Pendiente</x-ui.badge>
                                @endif
                            </td>
                            <td class="text-right">
                                <x-ui.action-menu>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Ver detalle</a>
                                    <a href="{{ route('admin.orders.pdf', $order) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Descargar PDF</a>
                                    <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" data-confirm="¿Eliminar {{ $order->oc_number }}? Esta acción no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-red-700 hover:bg-red-50">Eliminar</button>
                                    </form>
                                </x-ui.action-menu>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="mt-4">
                <x-ui.pagination :paginator="$orders" />
            </div>
        @endif
    </section>
</x-app-layout>
