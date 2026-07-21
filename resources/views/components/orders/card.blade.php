@props(['order'])

<article {{ $attributes->merge(['class' => 'card overflow-hidden transition hover:border-slate-300']) }}>
    <details class="group">
        <summary class="focus-ring cursor-pointer list-none rounded-2xl p-4 sm:p-5 [&::-webkit-details-marker]:hidden">
            <div class="grid items-start gap-4 lg:grid-cols-[minmax(11rem,.75fr)_minmax(14rem,1.15fr)_8rem_9rem_minmax(18rem,1.35fr)_2rem] lg:items-center">
                <div class="flex items-start justify-between gap-3 lg:block">
                    <div>
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Pedido</p>
                        <p class="mt-1 font-display text-base font-semibold text-slate-950">{{ $order->oc_number }}</p>
                    </div>
                    <div class="lg:hidden"><x-ui.status-badge :status="$order->status" /></div>
                </div>

                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $order->company_name }}</p>
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $order->contact_name }}@if($order->city) · {{ $order->city }}@endif</p>
                </div>

                <div class="hidden lg:block">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-slate-500">Fecha</p>
                    <p class="mt-1 text-sm font-medium tabular-nums text-slate-800">{{ $order->created_at?->format('d/m/Y') }}</p>
                    <p class="text-xs tabular-nums text-slate-400">{{ $order->created_at?->format('H:i') }}</p>
                </div>

                <div class="flex items-end justify-between gap-3 lg:block">
                    <div>
                        <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-slate-500">Monto</p>
                        <p class="mt-1 text-base font-bold tabular-nums text-slate-950">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</p>
                    </div>
                    <div class="hidden lg:block"><x-ui.status-badge :status="$order->status" /></div>
                    <p class="text-xs tabular-nums text-slate-500 lg:hidden">{{ $order->created_at?->format('d/m/Y') }}</p>
                </div>

                <x-orders.stepper :status="$order->status" />

                <svg aria-hidden="true" class="hidden h-5 w-5 text-slate-400 transition group-open:rotate-180 lg:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </div>

            <div class="mt-4 flex items-center justify-center gap-2 border-t border-slate-100 pt-3 text-xs font-semibold text-brand-dark lg:hidden">
                <span>Ver trazabilidad y acciones</span>
                <svg aria-hidden="true" class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </div>
        </summary>

        <div class="grid gap-6 border-t border-slate-200 bg-slate-50/70 px-4 py-5 sm:px-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(15rem,.65fr)]">
            <x-orders.timeline :order="$order" />

            <div class="lg:border-l lg:border-slate-200 lg:pl-6">
                <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Resumen y acciones</h4>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Generado por</dt><dd class="text-right font-medium text-slate-800">{{ $order->user?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Última actualización</dt><dd class="text-right font-medium text-slate-800">{{ $order->updated_at?->diffForHumans() }}</dd></div>
                    @if($order->status->isRejected() && $order->approval_note)
                        <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-red-800">
                            <dt class="text-xs font-semibold uppercase tracking-wide">Motivo</dt>
                            <dd class="mt-1 text-sm">{{ $order->approval_note }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('empresa.orders.show', $order) }}" class="btn btn-primary flex-1">Ver pedido</a>
                    @if($order->pdf_path)
                        <a href="{{ route('empresa.orders.pdf', $order) }}" class="btn btn-secondary" aria-label="Descargar PDF de {{ $order->oc_number }}">PDF</a>
                    @endif
                    @if(auth()->user()?->canReorder() && $order->status->value !== 'pending_approval')
                        <form method="POST" action="{{ route('empresa.orders.reorder', $order) }}" class="flex-1">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full">Volver a cotizar</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </details>
</article>
