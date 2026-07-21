@props(['order'])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-4']) }}>
    <div class="flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h11v10H3zM14 9h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
        </span>

        <div class="min-w-0 flex-1">
            <h3 class="text-sm font-semibold text-slate-950">Información de envío</h3>

            @if($order->tracking_number)
                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-500">Número de guía</dt>
                        <dd class="mt-1 break-all text-sm font-semibold tabular-nums text-slate-900">{{ $order->tracking_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-500">Transportadora</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $order->shipping_carrier?->label() ?? 'Por identificar' }}</dd>
                    </div>
                </dl>
            @else
                <p class="mt-2 text-sm leading-6 text-slate-600">Todavía no se ha asignado una guía ni una transportadora a este pedido.</p>
            @endif
        </div>
    </div>
</section>
