/**
 * Quantity steppers on product cards (only visible on desktop via CSS).
 */
export function initCartQtyControlsOnPage() {
    // ── Control de cantidad en tarjetas de producto (solo activo en PC vía CSS) ──
    const initCartQtyControls = (root = document) => {
        root.querySelectorAll('.cart-qty-form:not([data-qty-init])').forEach((form) => {
            form.dataset.qtyInit = 'true';

            const valueInput = form.querySelector('.cart-qty-value');
            const display = form.querySelector('.cart-qty-display');
            const minusBtn = form.querySelector('.cart-qty-minus');
            const plusBtn = form.querySelector('.cart-qty-plus');

            if (!valueInput || !display || !minusBtn || !plusBtn) return;

            const getQty = () => parseInt(valueInput.value, 10) || 1;

            const setQty = (n) => {
                const qty = Math.max(1, n);
                valueInput.value = qty;
                display.textContent = qty;
            };

            minusBtn.addEventListener('click', (e) => {
                e.preventDefault();
                setQty(getQty() - 1);
            });

            plusBtn.addEventListener('click', (e) => {
                e.preventDefault();
                setQty(getQty() + 1);
            });

            // Resetear la cantidad a 1 tras un submit exitoso del mismo form
            form.addEventListener('cart:added', () => setQty(1));
        });
    };

    initCartQtyControls();
}
