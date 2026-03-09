<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-extrabold text-slate-900">Tu carrito</h1>
            <a href="{{ route('catalog.index') }}" class="btn-secondary">Seguir comprando</a>
        </div>
    </x-slot>

    @if($items->isEmpty())
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
            <p class="text-slate-600">Aún no agregas productos.</p>
            <a href="{{ route('catalog.index') }}" class="btn-primary mt-3 inline-flex">Ir al catálogo</a>
        </section>
    @else
        <form action="{{ route('cart.update') }}" method="POST" class="space-y-3">
            @csrf
            @method('PUT')

            @foreach($items as $item)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $item['product']->name }}</p>
                            <p class="text-xs text-slate-500">{{ $item['product']->sku }}</p>
                            <p class="mt-1 text-sm font-semibold text-[#BC2983]">${{ number_format((float)$item['product']->price, 2, ',', '.') }}</p>
                        </div>
                        <p class="text-xs text-slate-500">Pon cantidad 0 para quitar</p>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <label class="text-xs font-semibold text-slate-600">Cantidad</label>
                        <input
                            type="number"
                            name="quantities[{{ $item['product']->id }}]"
                            min="0"
                            step="1"
                            value="{{ (int) $item['qty'] }}"
                            class="w-20 rounded-lg border-slate-300 px-2 py-1 text-sm focus:border-[#8DC543] focus:ring-[#8DC543]"
                        >
                        <span class="text-xs text-slate-500">Subtotal: ${{ number_format((float)$item['subtotal'], 2, ',', '.') }}</span>
                    </div>
                </article>
            @endforeach

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-700">Total</p>
                    <p class="text-2xl font-black text-[#BC2983]">${{ number_format((float)$total, 2, ',', '.') }}</p>
                </div>
                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="btn-secondary flex-1">Actualizar cantidades</button>
                    <a href="{{ route('checkout.show') }}" class="btn-primary flex-1 text-center">Continuar</a>
                </div>
            </section>
        </form>
    @endif
</x-app-layout>
