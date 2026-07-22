@props([
    'idPrefix' => 'order',
    'trackingNumber' => '',
    'shippingCarrier' => '',
    'carrierPrefixes' => [],
    'visible' => true,
    'manuallyEdited' => false,
])

<div class="{{ $visible ? '' : 'hidden' }} grid gap-3 rounded-xl border border-sky-200 bg-sky-50/70 p-3 sm:grid-cols-2" data-shipping-fields>
    <div>
        <label class="form-label" for="{{ $idPrefix }}-tracking-number">Número de guía</label>
        <x-ui.input
            id="{{ $idPrefix }}-tracking-number"
            name="tracking_number"
            type="text"
            inputmode="numeric"
            autocomplete="off"
            maxlength="80"
            pattern="[0-9]+"
            placeholder="Ejemplo: 2258298191"
            value="{{ $trackingNumber }}"
            data-tracking-number
            aria-invalid="{{ $errors->has('tracking_number') ? 'true' : 'false' }}"
            aria-describedby="{{ $idPrefix }}-tracking-number-help{{ $errors->has('tracking_number') ? ' '.$idPrefix.'-tracking-number-error' : '' }}"
        />
        <p id="{{ $idPrefix }}-tracking-number-help" class="mt-1 text-xs text-slate-500">Si lo borras por completo, la detección automática se reinicia.</p>
        <x-input-error :id="$idPrefix.'-tracking-number-error'" :messages="$errors->get('tracking_number')" />
    </div>

    <div>
        <label class="form-label" for="{{ $idPrefix }}-shipping-carrier">Transportadora</label>
        <div class="relative">
            <x-ui.input
                id="{{ $idPrefix }}-shipping-carrier"
                name="shipping_carrier"
                type="text"
                maxlength="40"
                placeholder="Transportadora o método de envío"
                value="{{ $shippingCarrier }}"
                class="pr-11 font-semibold"
                data-carrier-input
                data-carrier-manual="{{ $manuallyEdited ? 'true' : 'false' }}"
                :readonly="filled($shippingCarrier)"
                aria-invalid="{{ $errors->has('shipping_carrier') ? 'true' : 'false' }}"
                aria-describedby="{{ $idPrefix }}-shipping-carrier-help{{ $errors->has('shipping_carrier') ? ' '.$idPrefix.'-shipping-carrier-error' : '' }}"
            />
            <button
                type="button"
                class="focus-ring absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-sky-700 transition hover:bg-sky-100"
                data-carrier-edit
                aria-label="Editar transportadora"
                title="Editar transportadora"
            >
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
            </button>
        </div>
        <p id="{{ $idPrefix }}-shipping-carrier-help" class="mt-1 text-xs text-slate-500">Se sugiere automáticamente. Usa el lápiz para indicar una opción especial.</p>
        <x-input-error :id="$idPrefix.'-shipping-carrier-error'" :messages="$errors->get('shipping_carrier')" />
    </div>

    <script type="application/json" data-carrier-prefixes>@json($carrierPrefixes)</script>
</div>
