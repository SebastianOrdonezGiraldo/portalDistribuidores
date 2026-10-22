/**
 * AJAX add-to-cart / update forms with optimistic feedback.
 *
 * @param {ReturnType<import('./feedback').initCartFeedback> & {showLoginRequiredModal: Function}} deps
 */
export function initCartForms({
    animateCartBadges,
    showInlineToast,
    getCartBadgeTarget,
    getProductNameFromForm,
    pulseCartButton,
    shakeCartButton,
    pulseProductCard,
    flyParticle,
    showCartTooltip,
    makeSpinnerSvg,
    makeCheckSvg,
    makeCrossSvg,
    showLoginRequiredModal,
}) {
    document.querySelectorAll('form[action*="/cart"]').forEach((form) => {
        const hasProductInput = !!form.querySelector('input[name="product_id"]');
        const submitButton = form.querySelector('button[type="submit"]');

        if (!hasProductInput || !submitButton) {
            return;
        }

        form.dataset.ajaxCart = 'true';

        // Compact card CTAs hide the label with sm:hidden; textContent still sees it.
        const isCompactButton = submitButton.dataset.cartCompact === 'true'
            || submitButton.classList.contains('sm:w-10');
        const isIconButton = !isCompactButton && submitButton.textContent.trim() === '';
        const originalHtml = submitButton.innerHTML;
        const loadingLabel = submitButton.dataset.loadingLabel || 'Agregando...';

        const setFeedbackLayout = (withLabel) => {
            form.classList.add('is-cart-feedback');
            if (withLabel) {
                submitButton.classList.add('cart-add-btn', 'cart-add-btn--feedback');
            } else {
                submitButton.classList.remove('cart-add-btn--feedback');
            }
        };

        const clearFeedbackLayout = () => {
            form.classList.remove('is-cart-feedback');
            submitButton.classList.remove('cart-add-btn--feedback');
        };

        const setLoading = () => {
            submitButton.disabled = true;
            submitButton.style.transition = 'background-color 150ms ease, border-color 150ms ease, width 150ms ease, padding 150ms ease';
            if (isIconButton) {
                setFeedbackLayout(false);
                submitButton.innerHTML = makeSpinnerSvg();
            } else {
                setFeedbackLayout(true);
                submitButton.innerHTML = `${makeSpinnerSvg('shrink-0')} <span>${loadingLabel}</span>`;
            }
        };

        const setSuccess = () => {
            if (isIconButton) {
                setFeedbackLayout(false);
                submitButton.innerHTML = makeCheckSvg();
            } else {
                setFeedbackLayout(true);
                submitButton.innerHTML = `${makeCheckSvg()} <span>Agregado</span>`;
            }
            submitButton.classList.add('!bg-emerald-500', '!border-emerald-500');
        };

        const setError = () => {
            if (isIconButton) {
                setFeedbackLayout(false);
                submitButton.innerHTML = makeCrossSvg();
            } else {
                setFeedbackLayout(true);
                submitButton.innerHTML = `${makeCrossSvg()} <span>Ups</span>`;
            }
            submitButton.classList.add('!bg-red-500', '!border-red-500');
            shakeCartButton(submitButton);
        };

        const resetButton = () => {
            clearFeedbackLayout();
            submitButton.innerHTML = originalHtml;
            submitButton.classList.remove(
                '!bg-emerald-500', '!border-emerald-500',
                '!bg-red-500', '!border-red-500',
                'animate-cart-button-pop', 'animate-cart-button-shake',
            );
            submitButton.disabled = false;
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (submitButton.disabled) {
                return;
            }

            setLoading();

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });

                const payload = await response.json().catch(() => ({}));

                if (response.status === 401 && payload.requires_login) {
                    resetButton();
                    showLoginRequiredModal(payload);
                    return;
                }

                if (!response.ok) {
                    throw new Error(payload.message || 'No se pudo agregar al carrito.');
                }

                // Update badge count and color
                const count = Number(payload.cart_count || 0);
                document.querySelectorAll('[data-cart-badge]').forEach((badge) => {
                    badge.textContent = String(count);
                    badge.classList.remove('border-slate-200', 'bg-slate-100', 'text-slate-700');
                    badge.classList.add('bg-brand-primary/10', 'text-brand-dark');
                });

                // Show success state on button
                setSuccess();
                pulseCartButton(submitButton);
                pulseProductCard(submitButton);
                form.dispatchEvent(new CustomEvent('cart:added'));

                // Launch particle from button toward cart badge
                const badgeTarget = getCartBadgeTarget();
                window.setTimeout(() => flyParticle(submitButton, badgeTarget), 70);

                // Animate badge when particle lands
                window.setTimeout(() => animateCartBadges(true), 430);

                const productName = getProductNameFromForm(form);
                const shortName = productName.length > 52 ? `${productName.slice(0, 49)}...` : productName;
                const fallbackMessage = `${shortName} agregado al carrito.`;
                const successMessage = payload.message && payload.message !== 'Producto agregado al carrito.'
                    ? payload.message
                    : fallbackMessage;

                showCartTooltip(successMessage);

                // Reset button after success display
                window.setTimeout(resetButton, 1300);

            } catch (error) {
                setError();
                window.setTimeout(resetButton, 1200);
                showInlineToast(error.message || 'No se pudo agregar al carrito.', 'error');
            }
        });
    });
}
