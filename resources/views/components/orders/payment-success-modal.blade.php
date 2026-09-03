{{--
Component contract:
- Slots: none.
- Hidden until JS adds .is-active after payment polling detects confirming.
- Use for: one-shot success celebration when a payment receipt is confirmed in BD.
--}}
<div
    id="payment-success-modal"
    class="payment-success-modal"
    data-payment-success-modal
    role="dialog"
    aria-modal="true"
    aria-labelledby="payment-success-modal-label"
    aria-describedby="payment-success-modal-description"
    hidden
>
    <div class="payment-success-modal__backdrop" data-payment-success-dismiss></div>

    <div class="payment-success-modal__dialog" data-payment-success-dialog tabindex="-1">
        <button
            type="button"
            class="payment-success-close"
            data-payment-success-close
            data-payment-success-dismiss
            aria-label="Cerrar mensaje"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>

        <div class="payment-success-circle" aria-hidden="true">
            <svg
                class="payment-success-check"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M20 6 9 17l-5-5" />
            </svg>
        </div>

        <p
            id="payment-success-modal-label"
            class="payment-success-label"
            data-payment-success-label
            aria-live="polite"
        ></p>

        <p id="payment-success-modal-description" class="payment-success-description">
            Tu comprobante quedó en revisión. Puedes cerrar este mensaje y continuar.
        </p>

        <button type="button" class="payment-success-continue" data-payment-success-dismiss>
            Continuar
        </button>
    </div>
</div>
