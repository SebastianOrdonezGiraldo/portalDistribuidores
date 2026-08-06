{{--
Component contract:
- Props: checkoutAllowed (bool from backend).
- Confirmed CTA only (allowed / blocked by minimum). No "Actualizando carrito…" state.
--}}
@props([
    'checkoutAllowed' => true,
])

@if($checkoutAllowed)
    <x-ui.button type="submit" variant="primary" class="w-full justify-center" data-loading-label="Procesando..." data-checkout-submit>
        Continuar al checkout
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </x-ui.button>
@else
    <x-ui.button type="button" variant="primary" class="w-full justify-center opacity-60" disabled data-checkout-blocked>
        Completa el pedido mínimo
    </x-ui.button>
@endif
