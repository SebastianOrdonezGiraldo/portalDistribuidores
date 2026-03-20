<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Listado de Pedidos" subtitle="Búsqueda operativa por cliente, estado y rango de fechas para seguimiento comercial.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Pedidos filtrados: {{ number_format($metrics['total_orders']) }}</span>
                    <span class="stat-pill">Monto filtrado: ${{ number_format($metrics['total_amount'], 0, ',', '.') }}</span>
                    <span class="stat-pill">Pendientes PDF: {{ number_format($metrics['pending_pdf']) }}</span>
                    <span class="stat-pill">Filtros activos: {{ $activeFiltersCount }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary w-full justify-center sm:w-auto">Limpiar filtros</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card
            label="Pedidos Filtrados"
            :value="number_format($metrics['total_orders'])"
            hint="Resultado actual del listado"
        />
        <x-ui.kpi-card
            label="Valor Comercial"
            :value="'$'.number_format($metrics['total_amount'], 0, ',', '.')"
            hint="Suma del conjunto filtrado"
        />
        <x-ui.kpi-card
            label="PDF Pendiente"
            :value="number_format($metrics['pending_pdf'])"
            hint="Órdenes sin documento generado"
        />
        <x-ui.kpi-card
            label="En Proceso"
            :value="number_format($metrics['sending'])"
            hint="Estado operativo: sending"
        />
    </section>

    @php
        $baseStatusQuery = request()->except(['page', 'status']);
        $statusLabels = [
            'draft' => 'Pendiente',
            'submitted' => 'Aprobado',
            'sending' => 'Procesando',
            'sent' => 'Enviado',
            'failed' => 'Error',
        ];
    @endphp

    <x-ui.card class="mt-4 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.orders.index', $baseStatusQuery) }}"
               class="btn {{ empty($filters['status']) ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                Todos <span class="ml-1 text-xs opacity-80">{{ number_format($metrics['total_orders']) }}</span>
            </a>
            @foreach($statusOptions as $status)
                <a href="{{ route('admin.orders.index', array_merge($baseStatusQuery, ['status' => $status])) }}"
                   class="btn {{ $filters['status'] === $status ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 text-xs">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                    <span class="ml-1 text-xs opacity-80">{{ number_format($statusSummary[$status] ?? 0) }}</span>
                </a>
            @endforeach
        </div>
    </x-ui.card>

    <x-ui.filter-bar method="GET" action="{{ route('admin.orders.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-8">
        <div class="xl:col-span-2">
            <label class="form-label" for="orders-q">Buscar</label>
            <x-ui.input id="orders-q" name="q" :value="$filters['q']" placeholder="CTC, cliente, contacto o email" />
        </div>

        <div>
            <label class="form-label" for="orders-status">Estado</label>
            <x-ui.select id="orders-status" name="status">
                <option value="">Todos</option>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
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

        <div>
            <label class="form-label" for="orders-sort">Orden</label>
            <x-ui.select id="orders-sort" name="sort">
                <option value="newest" @selected($filters['sort'] === 'newest')>Más recientes</option>
                <option value="oldest" @selected($filters['sort'] === 'oldest')>Más antiguos</option>
                <option value="amount_desc" @selected($filters['sort'] === 'amount_desc')>Mayor valor</option>
                <option value="amount_asc" @selected($filters['sort'] === 'amount_asc')>Menor valor</option>
            </x-ui.select>
        </div>

        <div>
            <label class="form-label" for="orders-per-page">Por página</label>
            <x-ui.select id="orders-per-page" name="per_page">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected((int) $filters['per_page'] === $option)>{{ $option }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="xl:col-span-8 flex flex-col gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex w-full flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                <span>Resultados: <strong class="text-slate-700">{{ number_format($orders->total()) }}</strong></span>
                <span>Mostrando: <strong class="text-slate-700">{{ $orders->firstItem() ?? 0 }}-{{ $orders->lastItem() ?? 0 }}</strong></span>
                <span>Seleccionados: <strong data-bulk-count>0</strong></span>
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-ui.button type="button" variant="secondary" size="sm" class="w-full justify-center sm:w-auto" data-bulk-copy disabled>Copiar CTC</x-ui.button>
                <x-ui.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">Aplicar filtros</x-ui.button>
            </div>
        </div>
    </x-ui.filter-bar>

    <section data-bulk-table class="mt-4">
        @if($orders->isEmpty())
            <x-ui.empty-state title="No se encontraron pedidos" description="Ajusta filtros o limpia la búsqueda para recuperar resultados.">
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
                        <th>Creado</th>
                        <th>Actualizado</th>
                        <th>Documento</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td data-label="Seleccionar"><input type="checkbox" class="form-checkbox" data-bulk-row value="{{ $order->oc_number }}"></td>
                            <td data-label="CTC" data-full="true">
                                <p class="font-semibold text-slate-900">{{ $order->oc_number }}</p>
                                <p class="text-xs text-slate-500">{{ $order->user?->email }}</p>
                            </td>
                            <td data-label="Cliente" data-full="true">
                                <p class="font-medium text-slate-900">{{ $order->company_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->distributor?->name ?? 'Sin distribuidor' }}</p>
                            </td>
                            <td data-label="Contacto" data-full="true">
                                <p class="text-sm text-slate-900">{{ $order->contact_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->contact_email ?? 'Sin email' }}</p>
                            </td>
                            <td data-label="Estado"><x-ui.status-badge :status="$order->status" /></td>
                            <td data-label="Total" class="font-medium text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                            <td data-label="Creado">
                                <p>{{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-slate-500">{{ $order->created_at?->diffForHumans() }}</p>
                            </td>
                            <td data-label="Actualizado">{{ $order->updated_at?->format('d/m/Y H:i') }}</td>
                            <td data-label="Documento">
                                @if($order->pdf_path)
                                    <x-ui.badge variant="success">Disponible</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning">Pendiente</x-ui.badge>
                                @endif
                            </td>
                            <td data-label="Acciones" class="text-right">
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
