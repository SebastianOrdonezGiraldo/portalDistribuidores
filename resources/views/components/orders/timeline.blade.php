@props(['order'])

@php
    $events = $order->statusHistory
        ->sortBy('created_at')
        ->values();
@endphp

<div {{ $attributes }}>
    <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Trazabilidad</h4>
    <ol class="relative mt-4 border-l border-slate-200 pl-5">
        @forelse($events as $event)
            @php
                $eventStatus = \App\Modules\Shared\Enums\OrderStatus::tryFrom((string) $event->to_status);
            @endphp
            <li class="relative pb-5 last:pb-0">
                <span class="absolute -left-[1.48rem] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-brand-primary ring-1 ring-cyan-200" aria-hidden="true"></span>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <x-ui.status-badge :status="$event->to_status" />
                    <time class="text-xs tabular-nums text-slate-500" datetime="{{ $event->created_at?->toIso8601String() }}">{{ $event->created_at?->format('d/m/Y · H:i') }}</time>
                </div>
                <p class="mt-1.5 text-sm text-slate-600">{{ $event->note ?: 'Cambio a '.strtolower($eventStatus?->label() ?? (string) $event->to_status).'.' }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $event->actor?->name ? 'Actualizado por '.$event->actor->name : 'Actualizado por el sistema' }}</p>
            </li>
        @empty
            <li class="relative">
                <span class="absolute -left-[1.48rem] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-brand-primary ring-1 ring-cyan-200" aria-hidden="true"></span>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <x-ui.status-badge :status="$order->status" />
                    <time class="text-xs tabular-nums text-slate-500" datetime="{{ $order->created_at?->toIso8601String() }}">{{ $order->created_at?->format('d/m/Y · H:i') }}</time>
                </div>
                <p class="mt-1.5 text-sm text-slate-600">Registro inicial del pedido.</p>
            </li>
        @endforelse
    </ol>
</div>
