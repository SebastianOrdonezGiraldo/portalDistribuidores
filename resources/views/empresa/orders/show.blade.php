<x-app-layout>
    @php
        $formatQty = function (float|int|string|null $value): string {
            $n = (float) ($value ?? 0);
            $isInt = abs($n - round($n)) < 0.00001;
            return number_format($n, $isInt ? 0 : 2, ',', '.');
        };

        $timeline = collect([
            [
                'title'       => 'Cotización creada',
                'description' => 'Registro inicial de la CTC en el portal.',
                'status'      => 'submitted',
                'at'          => $order->created_at,
            ],
            [
                'title'       => 'Estado actual: '.strtoupper($order->status->value),
                'description' => 'Seguimiento del proceso de tu cotización.',
                'status'      => $order->status,
                'at'          => $order->updated_at,
            ],
            $order->pdf_path ? [
                'title'       => 'PDF disponible',
                'description' => 'Documento listo para descarga.',
                'status'      => 'sent',
                'at'          => $order->updated_at,
            ] : [
                'title'       => 'PDF pendiente',
                'description' => 'El documento aún no fue generado.',
                'status'      => 'pending',
                'at'          => $order->updated_at,
            ],
        ])->filter()->sortByDesc('at')->values();
    @endphp

    <x-slot name="header">
        <x-ui.page-header
            title="Cotización {{ $order->oc_number }}"
            subtitle="Detalle completo de tu cotización."
        >
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Creado: {{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
                    <span class="stat-pill">Ítems: {{ number_format($totals['items_count']) }}</span>
                    <span class="stat-pill">Total: ${{ number_format((float) $order->total_amount, 0, ',', '.') }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('empresa.orders.index') }}" class="btn btn-secondary">Volver al historial</a>
                @if(auth()->user()?->canReorder() && !$order->status->isPendingReview())
                    <form method="POST" action="{{ route('empresa.orders.reorder', $order) }}" class="inline">
                        @csrf
                        <x-ui.button type="submit" variant="secondary">Volver a cotizar</x-ui.button>
                    </form>
                @endif
                @if(auth()->user()?->canManageLists())
                    <button type="button"
                        onclick="document.getElementById('save-as-list-modal').showModal()"
                        class="btn btn-secondary">
                        Guardar como lista
                    </button>
                @endif
                <a href="{{ route('empresa.orders.pdf', $order) }}" class="btn btn-primary">Descargar PDF</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <section class="grid gap-4 xl:grid-cols-[1.7fr_1fr]">
        <div class="space-y-4">
            {{-- Encabezado --}}
            <x-ui.card class="p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="card-title">Resumen de la Cotización</h2>
                        <p class="mt-1 text-sm text-slate-600">Información registrada al momento de la creación.</p>
                    </div>
                    <x-ui.status-badge :status="$order->status" class="shrink-0" />
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">CTC</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $order->oc_number }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Monto total</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Ítems</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($totals['items_count']) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Unidades</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $formatQty($totals['units_total']) }}</p>
                    </div>
                </div>

                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Empresa</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $order->company_name }}</dd>
                        @if($order->company_nit)
                            <p class="text-xs text-slate-500">NIT: {{ $order->company_nit }}</p>
                        @endif
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Contacto</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $order->contact_name }}</dd>
                        @if($order->contact_email)
                            <p class="text-xs text-slate-500">{{ $order->contact_email }}</p>
                        @endif
                        @if($order->phone)
                            <p class="text-xs text-slate-500">{{ $order->phone }}</p>
                        @endif
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Dirección y ciudad</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            {{ $order->company_address ?? '—' }}
                            @if($order->city)· {{ $order->city }}@endif
                        </dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Generado por</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $order->user?->name ?? '—' }}</dd>
                        <p class="text-xs text-slate-500">{{ $order->user?->email }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Fechas</dt>
                        <dd class="mt-1 text-xs text-slate-700">Creado: {{ $order->created_at?->format('d/m/Y H:i') }}</dd>
                        <p class="text-xs text-slate-500">Actualizado: {{ $order->updated_at?->diffForHumans() }}</p>
                    </div>
                </dl>

                @if($hasFinancialGap)
                    <div class="mt-4">
                        <x-ui.alert variant="warning" title="Diferencia detectada en totales">
                            La suma de subtotales (${{ number_format($totals['subtotals_total'], 0, ',', '.') }}) no coincide con el total de la cotización.
                        </x-ui.alert>
                    </div>
                @endif
            </x-ui.card>

            {{-- Ítems --}}
            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <h2 class="card-title">Ítems de la Cotización</h2>
                        <p class="mt-1 text-xs text-slate-500">Productos solicitados con cantidades y precios.</p>
                    </div>
                </x-slot>

                <div class="p-5 pt-0">
                    @if($order->items->isEmpty())
                        <x-ui.empty-state title="Sin ítems" description="No se encontraron líneas de producto." compact />
                    @else
                        <x-ui.table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>SKU</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $index => $item)
                                    <tr>
                                        <td class="text-xs text-slate-500">{{ $index + 1 }}</td>
                                        <td class="font-medium text-slate-900">{{ $item->sku_snapshot }}</td>
                                        <td>
                                            <p class="font-medium text-slate-900">{{ $item->product_name_snapshot }}</p>
                                            @if($item->variant_value_snapshot)
                                                <p class="text-xs text-slate-500">{{ $item->variant_attribute_snapshot ?? 'Variante' }}: {{ $item->variant_value_snapshot }}</p>
                                            @endif
                                        </td>
                                        <td>{{ $formatQty($item->qty) }} {{ $item->unit_label }}</td>
                                        <td>${{ number_format((float) $item->price_each, 0, ',', '.') }}</td>
                                        <td class="font-semibold text-slate-900">${{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>

                        <div class="mt-4 grid gap-2 border-t border-slate-200 pt-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Subtotal ítems</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">${{ number_format($totals['subtotals_total'], 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Promedio unitario</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">${{ number_format($totals['average_unit_price'], 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Total cotización</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            {{-- Notas --}}
            @if($order->notes)
                <x-ui.card class="p-5">
                    <h2 class="card-title">Observaciones</h2>
                    <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $order->notes }}</p>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-4">
            {{-- Trazabilidad --}}
            <x-ui.card class="p-5">
                <h2 class="card-title">Estado y Trazabilidad</h2>
                <ol class="mt-4 space-y-3">
                    @foreach($timeline as $event)
                        <li class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-slate-900">{{ $event['title'] }}</p>
                                <x-ui.status-badge :status="$event['status']" class="shrink-0" />
                            </div>
                            <p class="mt-1 text-xs text-slate-600">{{ $event['description'] }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $event['at']?->format('d/m/Y H:i') }}</p>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>

            {{-- Documentos y acciones --}}
            <x-ui.card class="p-5">
                <h2 class="card-title">Documentación</h2>
                <div class="mt-3">
                    @if($order->pdf_path)
                        <x-ui.alert variant="success" title="PDF listo para descarga">
                            Documento comercial generado y disponible.
                        </x-ui.alert>
                    @else
                        <x-ui.alert variant="warning" title="PDF pendiente">
                            El PDF se encuentra en cola o aún no fue generado.
                        </x-ui.alert>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('empresa.orders.pdf', $order) }}" class="btn btn-primary">Descargar PDF</a>
                    <a href="{{ route('empresa.orders.index') }}" class="btn btn-secondary">Volver</a>
                    @if($order->contact_email)
                        <a href="mailto:{{ $order->contact_email }}" class="btn btn-secondary">Enviar correo</a>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </section>

    {{-- Banner de aprobación pendiente --}}
    @if($order->status->isPendingReview())
        <div class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-4">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                    <p class="font-semibold text-violet-800">En revisión interna</p>
                    <p class="mt-0.5 text-sm text-violet-700">Esta solicitud está pendiente de aprobación por el administrador de tu empresa. Recibirás una notificación una vez sea revisada.</p>
                    @can('approveOrders')
                        <div class="mt-3 flex gap-2">
                            <form method="POST" action="{{ route('empresa.approvals.approve', $order) }}">
                                @csrf
                                <x-ui.button type="submit" variant="primary" class="text-sm">Aprobar ahora</x-ui.button>
                            </form>
                            <button type="button" onclick="document.getElementById('reject-modal-show').showModal()" class="btn btn-danger text-sm">Rechazar</button>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    {{-- Banner de rechazo --}}
    @if($order->status->isRejected())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <div>
                    <p class="font-semibold text-red-800">Solicitud rechazada</p>
                    @if($order->approval_note)
                        <p class="mt-0.5 text-sm text-red-700">Motivo: <em>{{ $order->approval_note }}</em></p>
                    @endif
                    @if(auth()->user()?->canReorder())
                        <p class="mt-2 text-sm text-red-600">Puedes usar "Volver a cotizar" para crear una nueva solicitud corregida.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Guardar como lista frecuente --}}
    @if(auth()->user()?->canManageLists())
        <dialog id="save-as-list-modal" class="modal-dialog">
            <div class="modal-dialog-panel w-full max-w-md">
                <h2 class="card-title">Guardar como lista frecuente</h2>
                <p class="mt-1 text-sm text-slate-500">Guarda los {{ $order->items->count() }} producto(s) de esta cotización para reutilizarlos.</p>
                <form method="POST" action="{{ route('empresa.lists.store-from-order', $order) }}" class="mt-4">
                    @csrf
                    <div>
                        <label class="form-label" for="list-name-order">Nombre de la lista *</label>
                        <x-ui.input id="list-name-order" name="name" required
                            placeholder="Ej: Reposición mensual, Kit básico..." />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('save-as-list-modal').close()" class="btn btn-secondary">Cancelar</button>
                        <x-ui.button type="submit" variant="primary">Guardar lista</x-ui.button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif

    {{-- Modal: Rechazar desde detalle (solo admin_empresa) --}}
    @can('approveOrders')
        @if($order->status->isPendingReview())
            <dialog id="reject-modal-show" class="modal-dialog">
                <div class="modal-dialog-panel w-full max-w-md">
                    <h2 class="card-title text-red-700">Rechazar solicitud</h2>
                    <form method="POST" action="{{ route('empresa.approvals.reject', $order) }}" class="mt-4">
                        @csrf
                        <div>
                            <label class="form-label" for="reject-note-show">Motivo del rechazo *</label>
                            <x-ui.textarea id="reject-note-show" name="approval_note" rows="3" required
                                placeholder="Indica el motivo..."></x-ui.textarea>
                            <x-input-error :messages="$errors->get('approval_note')" />
                        </div>
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" onclick="document.getElementById('reject-modal-show').close()" class="btn btn-secondary">Cancelar</button>
                            <x-ui.button type="submit" variant="danger">Confirmar rechazo</x-ui.button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endif
    @endcan
</x-app-layout>
