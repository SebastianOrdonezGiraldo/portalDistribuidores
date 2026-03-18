<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Aprobaciones Internas"
            subtitle="Revisa las solicitudes de pedido enviadas por tu equipo comercial."
        />
    </x-slot>

    {{-- Pendientes --}}
    <section>
        <div class="mb-3 flex items-center gap-2">
            <h2 class="text-base font-semibold text-slate-900">Pendientes de revisión</h2>
            @if($pending->isNotEmpty())
                <span class="badge badge-violet">{{ $pending->count() }}</span>
            @endif
        </div>

        @if($pending->isEmpty())
            <x-ui.card class="p-6 text-center">
                <p class="text-sm text-slate-500">No hay solicitudes pendientes de aprobación. ✓</p>
            </x-ui.card>
        @else
            <div class="space-y-3">
                @foreach($pending as $order)
                    <x-ui.card class="p-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-sm font-semibold text-slate-900">{{ $order->oc_number }}</span>
                                    <x-ui.status-badge :status="$order->status" />
                                </div>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $order->company_name }}
                                    @if($order->user)
                                        · enviado por <strong>{{ $order->user->name }}</strong>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500">
                                    Creado {{ $order->created_at->diffForHumans() }}
                                    · ${{ number_format((float) $order->total_amount, 0, ',', '.') }}
                                </p>
                                @if($order->notes)
                                    <p class="mt-2 text-xs text-slate-500 italic">"{{ Str::limit($order->notes, 120) }}"</p>
                                @endif
                            </div>

                            <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
                                <a href="{{ route('empresa.orders.show', $order) }}"
                                   class="btn btn-secondary text-sm"
                                   target="_blank">
                                    Ver detalle
                                </a>
                                <form method="POST" action="{{ route('empresa.approvals.approve', $order) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="primary" class="text-sm">Aprobar</x-ui.button>
                                </form>
                                <button type="button"
                                    onclick="openRejectModal({{ $order->id }})"
                                    class="btn btn-danger text-sm">
                                    Rechazar
                                </button>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Rechazados recientes --}}
    @if($rejected->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-3 text-base font-semibold text-slate-900">Rechazados recientemente</h2>
            <x-ui.table>
                <x-slot name="head">
                    <tr>
                        <th>CTC</th>
                        <th>Creado por</th>
                        <th>Total</th>
                        <th>Motivo</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </x-slot>
                <x-slot name="body">
                    @foreach($rejected as $order)
                        <tr>
                            <td class="font-mono text-sm">{{ $order->oc_number }}</td>
                            <td>{{ $order->user?->name ?? '—' }}</td>
                            <td>${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                            <td class="max-w-xs">
                                @if($order->approval_note)
                                    <span class="text-sm text-slate-600 italic">{{ Str::limit($order->approval_note, 80) }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-sm text-slate-500">{{ $order->updated_at->format('d/m/Y') }}</td>
                            <td>
                                <a href="{{ route('empresa.orders.show', $order) }}" class="text-sm text-brand-primary hover:underline">Ver</a>
                            </td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-ui.table>
        </section>
    @endif

    {{-- Modal rechazo --}}
    <dialog id="reject-modal" class="modal-dialog">
        <div class="modal-dialog-panel w-full max-w-md">
            <h2 class="card-title text-red-700">Rechazar solicitud</h2>
            <p class="mt-1 text-sm text-slate-500">Indica el motivo del rechazo. Será visible para el usuario que creó la solicitud.</p>
            <form id="reject-form" method="POST" class="mt-4">
                @csrf
                <div>
                    <label class="form-label" for="reject-note">Motivo del rechazo *</label>
                    <x-ui.textarea id="reject-note" name="approval_note" rows="3" required
                        placeholder="Ej: Presupuesto excedido, producto fuera de catálogo..."></x-ui.textarea>
                    <x-input-error :messages="$errors->get('approval_note')" />
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('reject-modal').close()" class="btn btn-secondary">Cancelar</button>
                    <x-ui.button type="submit" variant="danger">Confirmar rechazo</x-ui.button>
                </div>
            </form>
        </div>
    </dialog>

    @push('scripts')
        <script>
            function openRejectModal(orderId) {
                const modal = document.getElementById('reject-modal');
                const form  = document.getElementById('reject-form');
                form.action = `/empresa/aprobaciones/${orderId}/rechazar`;
                modal.showModal();
            }
        </script>
    @endpush
</x-app-layout>
