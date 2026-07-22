@props(['distributor', 'variant' => 'menu', 'selectable' => false])

{{--
Component contract:
- Props: distributor model, variant menu|card, selectable (card edit form with tier-select).
- Slots: none.
- Use for: PATCH tier changes via admin.distributors.tier.update with confirmation.
--}}
@php
    use App\Modules\Shared\Enums\DistributorTier;

    $currentTier = $distributor->tier ?? DistributorTier::Silver;
    $oppositeTier = $currentTier === DistributorTier::Gold ? DistributorTier::Silver : DistributorTier::Gold;

    $confirmToGold = '¿Cambiar este distribuidor a ICM Oro? Sus carritos abiertos y pedidos nuevos utilizarán precios Oro.';
    $confirmToSilver = '¿Cambiar este distribuidor a ICM Plata? Sus carritos abiertos se recalcularán. Los pedidos confirmados conservarán sus precios históricos.';

    $flipConfirm = $oppositeTier === DistributorTier::Gold ? $confirmToGold : $confirmToSilver;
    $selectedTier = old('tier', $currentTier->value);
@endphp

@if($selectable)
    <form
        method="POST"
        action="{{ route('admin.distributors.tier.update', $distributor) }}"
        x-data="{ tier: @js((string) $selectedTier) }"
        x-bind:data-confirm="tier === @js(DistributorTier::Gold->value) ? @js($confirmToGold) : @js($confirmToSilver)"
        class="space-y-4"
    >
        @csrf
        @method('PATCH')

        <div>
            <label class="form-label" for="distributor-tier-select">Nivel comercial</label>
            <x-ui.tier-select
                id="distributor-tier-select"
                name="tier"
                :value="$selectedTier"
                x-model="tier"
            />
            <p class="form-help">Selecciona el nivel comercial que aplicará a carritos abiertos y pedidos nuevos.</p>
        </div>

        <button type="submit" class="btn btn-primary w-full justify-center">
            Actualizar nivel
        </button>
    </form>
@else
    <form
        method="POST"
        action="{{ route('admin.distributors.tier.update', $distributor) }}"
        data-confirm="{{ $flipConfirm }}"
        @class(['block w-full' => $variant === 'menu'])
    >
        @csrf
        @method('PATCH')
        <input type="hidden" name="tier" value="{{ $oppositeTier->value }}">

        @if($variant === 'menu')
            <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-slate-50">
                Cambiar a {{ $oppositeTier->label() }}
            </button>
        @else
            <button type="submit" class="btn btn-primary w-full justify-center">
                Cambiar a {{ $oppositeTier->label() }}
            </button>
        @endif
    </form>
@endif
