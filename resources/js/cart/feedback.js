import { prefersReducedMotion } from '../lib/motion';

/**
 * Add-to-cart feedback: badge animations, flying particles, tooltips and inline toasts.
 */
export function initCartFeedback() {
    const cartSuccessFlash = document.querySelector('[data-cart-flash]');
    const animateCartBadges = (withCountFlip = false) => {
        document.querySelectorAll('[data-cart-badge]').forEach((badge) => {
            badge.classList.remove('animate-cart-bump', 'animate-cart-glow', 'animate-cart-count-flip');
            void badge.offsetWidth;
            badge.classList.add('animate-cart-bump', 'animate-cart-glow');
            if (withCountFlip) {
                badge.classList.add('animate-cart-count-flip');
            }

            window.setTimeout(() => {
                badge.classList.remove('animate-cart-bump', 'animate-cart-glow', 'animate-cart-count-flip');
            }, 750);
        });
    };

    const showInlineToast = (message, variant = 'success') => {
        let container = document.querySelector('[data-inline-toast-stack]');

        if (!container) {
            container = document.createElement('div');
            container.dataset.inlineToastStack = 'true';
            container.className = 'pointer-events-none fixed inset-x-3 bottom-3 z-[100] space-y-2 sm:inset-x-auto sm:right-4 sm:bottom-4 sm:w-[22rem]';
            document.body.append(container);
        }

        const palette = {
            success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
            error: 'border-red-200 bg-red-50 text-red-900',
            info: 'border-sky-200 bg-sky-50 text-sky-900',
        };

        const icons = {
            success: `<svg class="h-4 w-4 mt-0.5 shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>`,
            error: `<svg class="h-4 w-4 mt-0.5 shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
            info: `<svg class="h-4 w-4 mt-0.5 shrink-0 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
        };

        const toast = document.createElement('div');
        toast.className = `pointer-events-auto toast ${palette[variant] || palette.info} animate-toast-in`;
        toast.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-2">
                    ${icons[variant] || icons.info}
                    <p class="text-sm font-semibold">${message}</p>
                </div>
                <button type="button" class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded opacity-60 transition hover:opacity-100" data-inline-toast-close aria-label="Cerrar">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        `;

        container.append(toast);

        const close = () => {
            toast.classList.remove('animate-toast-in');
            toast.classList.add('inline-toast-leave');
            window.setTimeout(() => toast.remove(), 220);
        };

        toast.querySelector('[data-inline-toast-close]')?.addEventListener('click', close);
        window.setTimeout(close, 2800);
    };

    // Cart animation helpers
    const isVisibleCartTarget = (element) => {
        if (!(element instanceof HTMLElement)) {
            return false;
        }

        const rect = element.getBoundingClientRect();
        const styles = window.getComputedStyle(element);

        return styles.display !== 'none'
            && styles.visibility !== 'hidden'
            && rect.width > 0
            && rect.height > 0
            && rect.right > 0
            && rect.left < window.innerWidth
            && rect.bottom > 0
            && rect.top < window.innerHeight;
    };

    const getCartBadgeTarget = () => {
        const targets = Array.from(document.querySelectorAll('[data-cart-target]'));
        const visibleTarget = targets.find((target) => isVisibleCartTarget(target));

        if (visibleTarget) {
            return visibleTarget;
        }

        const badges = Array.from(document.querySelectorAll('[data-cart-badge]'));
        const visibleBadge = badges.find((badge) => isVisibleCartTarget(badge));

        return visibleBadge || targets[0] || badges[0] || null;
    };

    const getProductNameFromForm = (form) => {
        const explicit = form.dataset.productName?.trim();
        if (explicit) {
            return explicit;
        }

        const heading = form.closest('article')?.querySelector('h3 a, h3');
        if (heading?.textContent?.trim()) {
            return heading.textContent.trim();
        }

        return 'Producto';
    };

    const pulseCartButton = (button) => {
        if (!button || prefersReducedMotion) {
            return;
        }

        button.classList.remove('animate-cart-button-pop');
        void button.offsetWidth;
        button.classList.add('animate-cart-button-pop');
    };

    const shakeCartButton = (button) => {
        if (!button || prefersReducedMotion) {
            return;
        }

        button.classList.remove('animate-cart-button-shake');
        void button.offsetWidth;
        button.classList.add('animate-cart-button-shake');
    };

    const pulseProductCard = (fromEl) => {
        if (prefersReducedMotion) {
            return;
        }

        const card = fromEl?.closest('article');
        if (!card) {
            return;
        }

        card.classList.remove('cart-card-highlight');
        void card.offsetWidth;
        card.classList.add('cart-card-highlight');

        window.setTimeout(() => {
            card.classList.remove('cart-card-highlight');
        }, 560);
    };

    const spawnBadgeRing = (toEl) => {
        if (!toEl || prefersReducedMotion) {
            return;
        }

        const to = toEl.getBoundingClientRect();
        const ring = document.createElement('span');
        ring.className = 'cart-target-ring';
        ring.setAttribute('aria-hidden', 'true');
        ring.style.left = `${to.left + to.width / 2 - 16}px`;
        ring.style.top = `${to.top + to.height / 2 - 16}px`;
        document.body.append(ring);
        window.setTimeout(() => ring.remove(), 520);
    };

    const launchParticle = (fromX, fromY, toX, toY, options = {}) => {
        const size = options.size ?? 10;
        const duration = options.duration ?? 560;
        const delay = options.delay ?? 0;
        const arcHeight = options.arcHeight ?? 72;
        const startOpacity = options.opacity ?? 1;

        const particle = document.createElement('span');
        particle.className = 'cart-fly-dot';
        particle.setAttribute('aria-hidden', 'true');
        particle.style.left = `${fromX - size / 2}px`;
        particle.style.top = `${fromY - size / 2}px`;
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.opacity = String(startOpacity);
        document.body.append(particle);

        const deltaX = toX - fromX;
        const deltaY = toY - fromY;

        if (typeof particle.animate === 'function') {
            const animation = particle.animate(
                [
                    { transform: 'translate3d(0, 0, 0) scale(1)', opacity: startOpacity },
                    {
                        transform: `translate3d(${deltaX * 0.56}px, ${deltaY * 0.5 - arcHeight}px, 0) scale(0.86)`,
                        opacity: Math.max(0.65, startOpacity - 0.1),
                        offset: 0.58,
                    },
                    { transform: `translate3d(${deltaX}px, ${deltaY}px, 0) scale(0.24)`, opacity: 0 },
                ],
                {
                    duration,
                    delay,
                    easing: 'cubic-bezier(0.18, 0.88, 0.22, 1)',
                    fill: 'forwards',
                },
            );

            animation.onfinish = () => particle.remove();
            return;
        }

        Object.assign(particle.style, {
            transition: `transform ${duration}ms ease, opacity ${duration}ms ease`,
            transform: `translate3d(${deltaX}px, ${deltaY}px, 0) scale(0.24)`,
            opacity: '0',
        });
        window.setTimeout(() => particle.remove(), duration + delay + 80);
    };

    const flyParticle = (fromEl, toEl) => {
        if (!fromEl || !toEl) {
            return;
        }

        if (prefersReducedMotion) {
            animateCartBadges(true);
            return;
        }

        const from = fromEl.getBoundingClientRect();
        const to = toEl.getBoundingClientRect();

        const fromX = from.left + from.width / 2;
        const fromY = from.top + from.height / 2;
        const toX = to.left + to.width / 2;
        const toY = to.top + to.height / 2;

        launchParticle(fromX, fromY, toX, toY, {
            size: 10,
            duration: 610,
            delay: 0,
            arcHeight: 86,
            opacity: 1,
        });
        launchParticle(fromX, fromY, toX, toY, {
            size: 7,
            duration: 560,
            delay: 40,
            arcHeight: 62,
            opacity: 0.92,
        });
        launchParticle(fromX, fromY, toX, toY, {
            size: 6,
            duration: 520,
            delay: 90,
            arcHeight: 46,
            opacity: 0.78,
        });

        window.setTimeout(() => {
            spawnBadgeRing(toEl);
        }, 360);
    };

    const showCartTooltip = (message) => {
        const target = getCartBadgeTarget();
        const detailMessage = message.replace(/\s+agregado al carrito\.?$/i, '').trim();
        const displayMessage = detailMessage && detailMessage.toLowerCase() !== 'producto'
            ? detailMessage
            : 'Tu pedido se actualizo correctamente.';

        if (!target) {
            showInlineToast(message, 'success');
            return;
        }

        document.querySelectorAll('[data-cart-tooltip]').forEach((tooltip) => tooltip.remove());

        const tooltip = document.createElement('div');
        tooltip.dataset.cartTooltip = 'true';
        tooltip.className = 'cart-tooltip animate-cart-tooltip-in';
        tooltip.setAttribute('role', 'status');
        tooltip.setAttribute('aria-live', 'polite');
        Object.assign(tooltip.style, {
            position: 'fixed',
            zIndex: '120',
            boxSizing: 'border-box',
            display: 'flex',
            alignItems: 'flex-start',
            gap: '0.75rem',
            width: 'min(21rem, calc(100vw - 1.5rem))',
            maxWidth: '21rem',
            padding: '0.85rem 0.9rem',
            border: '1px solid rgba(20, 184, 166, 0.18)',
            borderRadius: '1rem',
            background: 'linear-gradient(135deg, rgba(255,255,255,0.98), rgba(236,253,253,0.96))',
            color: '#0f172a',
            boxShadow: '0 18px 46px rgba(15, 23, 42, 0.16), 0 8px 18px rgba(20, 184, 166, 0.10)',
            backdropFilter: 'blur(10px)',
        });

        const arrow = document.createElement('span');
        arrow.dataset.cartTooltipArrow = 'true';
        Object.assign(arrow.style, {
            position: 'absolute',
            top: '-0.42rem',
            width: '0.82rem',
            height: '0.82rem',
            borderTop: '1px solid rgba(20, 184, 166, 0.18)',
            borderLeft: '1px solid rgba(20, 184, 166, 0.18)',
            background: 'rgba(255, 255, 255, 0.98)',
            transform: 'translateX(-50%) rotate(45deg)',
            boxShadow: '-4px -4px 10px rgba(15, 23, 42, 0.035)',
        });

        const icon = document.createElement('span');
        icon.className = 'cart-tooltip-icon';
        icon.innerHTML = makeCheckSvg();
        Object.assign(icon.style, {
            position: 'relative',
            zIndex: '1',
            display: 'inline-flex',
            width: '2.35rem',
            height: '2.35rem',
            flexShrink: '0',
            alignItems: 'center',
            justifyContent: 'center',
            borderRadius: '0.8rem',
            background: '#0f8f95',
            color: '#ffffff',
            boxShadow: '0 10px 22px rgba(15, 143, 149, 0.26)',
        });

        const content = document.createElement('span');
        Object.assign(content.style, {
            position: 'relative',
            zIndex: '1',
            minWidth: '0',
            flex: '1',
            display: 'grid',
            gap: '0.15rem',
            paddingTop: '0.08rem',
        });

        const title = document.createElement('span');
        title.textContent = 'Agregado al carrito';
        Object.assign(title.style, {
            display: 'block',
            fontSize: '0.84rem',
            fontWeight: '800',
            lineHeight: '1.15',
            color: '#0f172a',
        });

        const text = document.createElement('span');
        text.className = 'cart-tooltip-text';
        text.textContent = displayMessage;
        Object.assign(text.style, {
            display: 'block',
            overflow: 'hidden',
            textOverflow: 'ellipsis',
            fontSize: '0.78rem',
            fontWeight: '600',
            lineHeight: '1.35',
            color: '#52637a',
            whiteSpace: 'nowrap',
        });

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'cart-tooltip-close';
        closeButton.setAttribute('aria-label', 'Cerrar');
        closeButton.innerHTML = '<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
        Object.assign(closeButton.style, {
            position: 'relative',
            zIndex: '1',
            display: 'inline-flex',
            width: '1.85rem',
            height: '1.85rem',
            flexShrink: '0',
            alignItems: 'center',
            justifyContent: 'center',
            border: '0',
            borderRadius: '0.65rem',
            background: 'rgba(15, 23, 42, 0.045)',
            color: '#64748b',
            cursor: 'pointer',
        });

        content.append(title, text);
        tooltip.append(arrow, icon, content, closeButton);
        document.body.append(tooltip);

        const position = () => {
            const rect = target.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            const viewportPadding = 12;
            const availableWidth = Math.max(0, window.innerWidth - tooltipRect.width - viewportPadding);
            const availableHeight = Math.max(0, window.innerHeight - tooltipRect.height - viewportPadding);
            const targetCenterX = rect.left + rect.width / 2;
            const targetBottom = Math.max(viewportPadding, Math.min(rect.bottom, window.innerHeight - viewportPadding));
            const left = Math.max(
                viewportPadding,
                Math.min(targetCenterX - tooltipRect.width / 2, availableWidth),
            );
            const top = Math.min(
                Math.max(viewportPadding, targetBottom + 12),
                availableHeight,
            );
            const arrowX = Math.max(22, Math.min(targetCenterX - left, tooltipRect.width - 22));

            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${top}px`;
            tooltip.style.setProperty('--cart-tooltip-arrow-x', `${arrowX}px`);
            arrow.style.left = `${arrowX}px`;
        };

        position();

        const close = () => {
            tooltip.classList.remove('animate-cart-tooltip-in');
            tooltip.classList.add('inline-toast-leave');
            window.removeEventListener('resize', position);
            window.removeEventListener('scroll', position);
            window.setTimeout(() => tooltip.remove(), 220);
        };

        closeButton.addEventListener('click', close);
        window.addEventListener('resize', position, { passive: true });
        window.addEventListener('scroll', position, { passive: true });
        window.setTimeout(close, 3200);
    };

    const makeSpinnerSvg = (extraClass = '') =>
        `<svg class="h-4 w-4 btn-spinning${extraClass ? ' ' + extraClass : ''}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.568 3 7.212l3-2.921z"></path></svg>`;

    const makeCheckSvg = () =>
        `<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

    const makeCrossSvg = () =>
        `<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;

    if (cartSuccessFlash) {
        animateCartBadges();
        showCartTooltip(cartSuccessFlash.dataset.cartMessage || 'Producto agregado al carrito.');
        cartSuccessFlash.remove();
    }

    return {
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
    };
}
