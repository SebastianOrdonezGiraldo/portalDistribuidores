@props([
    'distributor',
    'recentOrders',
    'statusValue',
    'formatMoney',
])

@php
    $account = $distributor->user;
    $latestOrderAt = $distributor->latest_order_at
        ? \Illuminate\Support\Carbon::parse($distributor->latest_order_at)
        : null;

    $activities = collect();

    foreach ($recentOrders->take(3) as $order) {
        $activities->push([
            'title' => 'Nuevo pedido '.$order->oc_number,
            'meta' => $formatMoney($order->total_amount),
            'at' => $order->created_at,
        ]);
    }

    if ($account) {
        $activities->push([
            'title' => 'Cuenta de acceso vinculada',
            'meta' => $account->name,
            'at' => $account->created_at,
        ]);
    }

    if ($distributor->updated_at && $distributor->created_at?->ne($distributor->updated_at)) {
        $activities->push([
            'title' => 'Actualización de empresa',
            'meta' => 'Perfil comercial actualizado',
            'at' => $distributor->updated_at,
        ]);
    }

    $activities = $activities
        ->filter(fn (array $item) => filled($item['at']))
        ->sortByDesc('at')
        ->take(4)
        ->values();

    $hasContact = filled($distributor->contact_email) || filled($distributor->contact_name);
    $hasLocation = filled($distributor->city) && filled($distributor->address);
    $hasRecentActivity = $recentOrders->isNotEmpty();

    $relationLabel = match ($statusValue) {
        'active' => 'Cliente directo',
        'pending_review' => 'En revisión',
        'suspended' => 'Cuenta suspendida',
        'rejected' => 'Cuenta rechazada',
        default => 'Relación comercial',
    };
@endphp

<div class="distributor-expanded-panel">
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.65fr)_minmax(280px,1fr)]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Contexto comercial</h3>
                    <p class="mt-1 text-sm text-slate-500">Resumen operativo de la empresa y su actividad reciente.</p>
                </div>
                <x-ui.status-badge :status="$statusValue" />
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="distributor-context-cell">
                    <span class="distributor-context-icon distributor-context-icon--brand" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="distributor-context-label">Último pedido</p>
                        <p class="distributor-context-value">{{ $latestOrderAt?->format('d/m/Y') ?? 'Sin pedidos' }}</p>
                        <p class="distributor-context-meta">{{ $latestOrderAt?->diffForHumans() ?? 'Aún sin historial' }}</p>
                    </div>
                </div>

                <div class="distributor-context-cell">
                    <span class="distributor-context-icon distributor-context-icon--success" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="distributor-context-label">Monto acumulado</p>
                        <p class="distributor-context-value">{{ $formatMoney($distributor->orders_total_amount) }}</p>
                        <p class="distributor-context-meta">Historial total</p>
                    </div>
                </div>

                <div class="distributor-context-cell">
                    <span class="distributor-context-icon distributor-context-icon--info" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="distributor-context-label">Pedidos</p>
                        <p class="distributor-context-value">{{ number_format((int) $distributor->orders_count) }}</p>
                        <p class="distributor-context-meta">Total realizados</p>
                    </div>
                </div>

                <div class="distributor-context-cell">
                    <span class="distributor-context-icon distributor-context-icon--neutral" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="distributor-context-label">Usuario principal</p>
                        @if($account)
                            <p class="distributor-context-value truncate">{{ $account->name }}</p>
                            <a href="{{ route('admin.users.edit', $account) }}" class="distributor-context-link truncate">{{ $account->email }}</a>
                        @else
                            <p class="distributor-context-value">Sin cuenta</p>
                            <a href="{{ route('admin.users.create', ['role' => 'distributor', 'distributor_id' => $distributor->id]) }}" class="distributor-context-link">Crear cuenta de acceso</a>
                        @endif
                    </div>
                </div>

                <div class="distributor-context-cell">
                    <span class="distributor-context-icon distributor-context-icon--brand" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="distributor-context-label">Contacto</p>
                        <p class="distributor-context-value truncate">{{ $distributor->contact_name ?: 'Sin contacto principal' }}</p>
                        <p class="distributor-context-meta truncate">{{ $distributor->phone ?: 'Sin teléfono registrado' }}</p>
                    </div>
                </div>

                <div class="distributor-context-cell">
                    <span class="distributor-context-icon distributor-context-icon--info" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="distributor-context-label">Ubicación</p>
                        <p class="distributor-context-value truncate">{{ $distributor->city ?: 'Sin ciudad' }}</p>
                        <p class="distributor-context-meta truncate">{{ $distributor->address ?: 'Sin dirección registrada' }}</p>
                    </div>
                </div>

                <div class="distributor-context-cell">
                    <span class="distributor-context-icon distributor-context-icon--warning" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="distributor-context-label">Tipo de relación</p>
                        <p class="distributor-context-value">{{ $relationLabel }}</p>
                        <p class="distributor-context-meta">{{ $distributor->status->label() }}</p>
                    </div>
                </div>

                <div class="distributor-context-cell">
                    <span class="distributor-context-icon distributor-context-icon--neutral" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="distributor-context-label">Notas internas</p>
                        <p class="distributor-context-value">Sin notas registradas</p>
                        <p class="distributor-context-meta">NIT: {{ $distributor->nit ?: 'Sin NIT' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Estado de la cuenta</h3>
                <ul class="mt-4 space-y-2">
                    <li class="distributor-check-item">
                        <span class="distributor-check-item__label">Contacto comercial</span>
                        <span class="distributor-check-item__status {{ $hasContact ? 'is-complete' : 'is-pending' }}">
                            @if($hasContact)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Completo
                            @else
                                Pendiente
                            @endif
                        </span>
                    </li>
                    <li class="distributor-check-item">
                        <span class="distributor-check-item__label">Ubicación completa</span>
                        <span class="distributor-check-item__status {{ $hasLocation ? 'is-complete' : 'is-pending' }}">
                            @if($hasLocation)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Completo
                            @else
                                Incompleta
                            @endif
                        </span>
                    </li>
                    <li class="distributor-check-item">
                        <span class="distributor-check-item__label">Actividad reciente</span>
                        <span class="distributor-check-item__status {{ $hasRecentActivity ? 'is-complete' : 'is-pending' }}">
                            @if($hasRecentActivity)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Activa
                            @else
                                Sin pedidos
                            @endif
                        </span>
                    </li>
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-base font-semibold text-slate-900">Actividad reciente</h3>
                    <a href="{{ route('admin.orders.index', ['distributor_id' => $distributor->id]) }}" class="text-xs font-semibold text-brand-dark hover:underline">Ver historial completo</a>
                </div>

                @if($activities->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">Sin pedidos recientes. Esta empresa todavía no registra pedidos en el portal.</p>
                @else
                    <ol class="distributor-timeline mt-4">
                        @foreach($activities as $activity)
                            <li class="distributor-timeline__item">
                                <span class="distributor-timeline__dot" aria-hidden="true"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">{{ $activity['title'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $activity['meta'] }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $activity['at']?->format('d/m/Y H:i') }} · {{ $activity['at']?->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    </div>
</div>
