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
    hidden
>
    <div class="payment-success-modal__backdrop" data-payment-success-dismiss></div>

    <div class="payment-success-modal__dialog" data-payment-success-dialog tabindex="-1">
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
    </div>
</div>
