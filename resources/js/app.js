import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const openModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

const closeModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
    const sidebarToggles = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
    const sidebarCollapseButton = document.querySelector('[data-sidebar-collapse]');
    const sidebarCollapseIcon = sidebarCollapseButton?.querySelector('[data-sidebar-collapse-icon]');
    const sidebarExpandIcon = sidebarCollapseButton?.querySelector('[data-sidebar-expand-icon]');
    const sidebarNavLinks = Array.from(document.querySelectorAll('[data-sidebar-nav-link]'));
    const cookieBanner = document.querySelector('[data-cookie-banner]');
    const cookieAcceptButton = cookieBanner?.querySelector('[data-cookie-accept]');
    const cookieConsentKey = 'portal_cookie_consent_v1';
    const isDesktopViewport = () => typeof window.matchMedia === 'function'
        && window.matchMedia('(min-width: 1024px)').matches;
    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(', ');
    let lastSidebarTrigger = null;
    const distributorSidebarStorageKey = 'distributor_sidebar_collapsed_v1';

    sidebarNavLinks.forEach((link) => {
        const labelSource = link.cloneNode(true);
        labelSource.querySelectorAll('.badge').forEach((badge) => badge.remove());
        link.dataset.sidebarLabel = (labelSource.textContent || '').replace(/\s+/g, ' ').trim();
    });

    const readDesktopSidebarPreference = () => {
        if (!sidebarCollapseButton || !sidebar) {
            return false;
        }

        try {
            const storedState = window.localStorage.getItem(distributorSidebarStorageKey);

            if (storedState !== null) {
                return storedState === '1';
            }
        } catch {
            // Fall back to the route-specific initial state below.
        }

        return sidebar.dataset.sidebarDefaultCollapsed === 'true';
    };

    let desktopSidebarCollapsed = readDesktopSidebarPreference();

    const renderDesktopSidebarState = ({ persist = false } = {}) => {
        const collapsed = Boolean(sidebarCollapseButton && isDesktopViewport() && desktopSidebarCollapsed);

        document.documentElement.classList.toggle('distributor-sidebar-collapsed', collapsed);

        if (sidebarCollapseButton) {
            sidebarCollapseButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarCollapseButton.setAttribute('aria-label', collapsed ? 'Expandir menú lateral' : 'Contraer menú lateral');
            sidebarCollapseButton.setAttribute('title', collapsed ? 'Expandir menú' : 'Contraer menú');
        }

        sidebarCollapseIcon?.classList.toggle('hidden', collapsed);
        sidebarExpandIcon?.classList.toggle('hidden', !collapsed);

        sidebarNavLinks.forEach((link) => {
            if (collapsed && link.dataset.sidebarLabel) {
                link.setAttribute('title', link.dataset.sidebarLabel);
            } else {
                link.removeAttribute('title');
            }
        });

        if (persist) {
            try {
                window.localStorage.setItem(distributorSidebarStorageKey, desktopSidebarCollapsed ? '1' : '0');
            } catch {
                // The control remains functional for the current page without persistence.
            }
        }
    };

    const hasCookieConsent = () => {
        try {
            if (window.localStorage.getItem(cookieConsentKey) === 'accepted') {
                return true;
            }
        } catch {
            // localStorage may be blocked in some browsers or private modes.
        }

        return document.cookie.split('; ').some((entry) => entry.startsWith(`${cookieConsentKey}=accepted`));
    };

    const persistCookieConsent = () => {
        try {
            window.localStorage.setItem(cookieConsentKey, 'accepted');
        } catch {
            // Keep working with cookie fallback if localStorage is not available.
        }

        const maxAge = 60 * 60 * 24 * 365;
        document.cookie = `${cookieConsentKey}=accepted; Path=/; Max-Age=${maxAge}; SameSite=Lax`;
    };

    if (cookieBanner) {
        if (!hasCookieConsent()) {
            cookieBanner.classList.remove('hidden');
        }

        cookieAcceptButton?.addEventListener('click', () => {
            persistCookieConsent();
            cookieBanner.classList.add('hidden');
        });
    }

    const setSidebarExpanded = (expanded) => {
        sidebarToggles.forEach((button) => {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    };

    const setSidebarInteractivity = (enabled) => {
        if (!sidebar) {
            return;
        }

        if (enabled) {
            sidebar.classList.remove('pointer-events-none');
            sidebar.removeAttribute('inert');
            return;
        }

        sidebar.classList.add('pointer-events-none');
        sidebar.setAttribute('inert', '');
    };

    const showSidebar = (triggerButton = null) => {
        if (!sidebar || isDesktopViewport()) {
            return;
        }

        lastSidebarTrigger = triggerButton instanceof HTMLElement
            ? triggerButton
            : (document.activeElement instanceof HTMLElement ? document.activeElement : null);

        sidebar?.classList.remove('-translate-x-full');
        sidebarOverlay?.classList.remove('hidden');
        document.body.classList.add('sidebar-open');
        setSidebarInteractivity(true);
        setSidebarExpanded(true);

        const firstFocusable = sidebar.querySelector('[data-sidebar-close]') || sidebar.querySelector(focusableSelector);
        firstFocusable?.focus();
    };

    const hideSidebar = () => {
        if (!sidebar) {
            return;
        }

        const mobileViewport = !isDesktopViewport();
        const shouldRestoreFocus = mobileViewport && sidebar.contains(document.activeElement);

        if (mobileViewport) {
            sidebar.classList.add('-translate-x-full');
            setSidebarInteractivity(false);
        } else {
            setSidebarInteractivity(true);
        }

        sidebarOverlay?.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
        setSidebarExpanded(false);

        if (shouldRestoreFocus) {
            const fallbackToggle = sidebarToggles[0];
            const focusTarget = lastSidebarTrigger && document.contains(lastSidebarTrigger)
                ? lastSidebarTrigger
                : fallbackToggle;

            focusTarget?.focus();
        }
    };

    const syncSidebarLayout = () => {
        if (!sidebar) {
            return;
        }

        if (isDesktopViewport()) {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay?.classList.add('hidden');
            document.body.classList.remove('sidebar-open');
            setSidebarInteractivity(true);
            setSidebarExpanded(false);
            renderDesktopSidebarState();
            return;
        }

        document.documentElement.classList.remove('distributor-sidebar-collapsed');
        sidebar.classList.add('-translate-x-full');
        sidebarOverlay?.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
        setSidebarInteractivity(false);
        setSidebarExpanded(false);
    };

    sidebarToggles.forEach((button) => {
        button.addEventListener('click', () => {
            showSidebar(button);
        });
    });

    sidebarCollapseButton?.addEventListener('click', () => {
        desktopSidebarCollapsed = !desktopSidebarCollapsed;
        renderDesktopSidebarState({ persist: true });
    });

    document.querySelectorAll('[data-sidebar-close], [data-sidebar-overlay]').forEach((button) => {
        button.addEventListener('click', hideSidebar);
    });

    sidebar?.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => {
            if (!isDesktopViewport()) {
                hideSidebar();
            }
        });
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideSidebar();
        }
    });

    window.addEventListener('resize', syncSidebarLayout);
    syncSidebarLayout();

    const portalTourRoot = document.querySelector('[data-portal-tour-root]');

    if (portalTourRoot) {
        const tourHighlight = portalTourRoot.querySelector('[data-portal-tour-highlight]');
        const tourDialog = portalTourRoot.querySelector('[data-portal-tour-dialog]');
        const tourTitle = portalTourRoot.querySelector('[data-portal-tour-title]');
        const tourDescription = portalTourRoot.querySelector('[data-portal-tour-description]');
        const tourProgress = portalTourRoot.querySelector('[data-portal-tour-progress]');
        const tourIcon = portalTourRoot.querySelector('[data-portal-tour-icon]');
        const tourDots = portalTourRoot.querySelector('[data-portal-tour-dots]');
        const tourPrevious = portalTourRoot.querySelector('[data-portal-tour-previous]');
        const tourNext = portalTourRoot.querySelector('[data-portal-tour-next]');
        const tourSkipButtons = portalTourRoot.querySelectorAll('[data-portal-tour-skip]');
        const tourRestartButtons = document.querySelectorAll('[data-portal-tour-restart]');
        const catalogMenuToggle = document.querySelector('[data-portal-tour-catalog-menu-toggle]');
        const originalDesktopSidebarState = desktopSidebarCollapsed;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        let currentTourStep = 0;
        let tourRenderSequence = 0;
        let tourIsOpen = false;

        const tourSteps = [
            {
                key: 'company',
                title: 'Tu empresa, en un solo lugar',
                description: 'Desde este menú consultas pedidos, administras tus sedes y actualizas los datos de la empresa.',
                selector: '[data-portal-tour-target="company"]',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/><path d="M9 10h.01M15 10h.01"/></svg>',
            },
            {
                key: 'search',
                title: 'Encuentra productos rápido',
                description: 'Busca por nombre, SKU o marca y limita los resultados a una categoría.',
                selector: '[data-portal-tour-target="search-desktop"], [data-portal-tour-target="search-mobile"]',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>',
            },
            {
                key: 'filters',
                title: 'Afina tu búsqueda',
                description: 'Usa los filtros para ordenar el catálogo y mostrar justo los productos que necesitas.',
                selector: '[data-portal-tour-target="filters-desktop"], [data-portal-tour-target="filters-mobile"]',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M4 6h16M7 12h10M10 18h4"/></svg>',
            },
            {
                key: 'cart',
                title: 'Tu pedido siempre a la mano',
                description: 'Aquí ves cuántos productos llevas y continúas con la preparación de tu pedido.',
                selector: '[data-cart-target]',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.2 10h10.6L20 7H6"/></svg>',
            },
        ];

        const waitForTourLayout = (milliseconds = 180) => new Promise((resolve) => {
            window.setTimeout(resolve, milliseconds);
        });

        const isElementVisible = (element) => {
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

        const isElementRendered = (element) => {
            if (!(element instanceof HTMLElement)) {
                return false;
            }

            const rect = element.getBoundingClientRect();
            const styles = window.getComputedStyle(element);

            return styles.display !== 'none'
                && styles.visibility !== 'hidden'
                && rect.width > 0
                && rect.height > 0;
        };

        const findTourTarget = (selector) => {
            const candidates = Array.from(document.querySelectorAll(selector));

            return candidates.find((element) => isElementVisible(element))
                || candidates.find((element) => isElementRendered(element));
        };

        const closeCatalogMenu = async () => {
            if (catalogMenuToggle?.getAttribute('aria-expanded') === 'true') {
                catalogMenuToggle.click();
                await waitForTourLayout(170);
            }
        };

        const prepareTourStep = async (step) => {
            if (!isDesktopViewport()) {
                if (step.key === 'company') {
                    await closeCatalogMenu();

                    if (!document.body.classList.contains('sidebar-open')) {
                        showSidebar();
                        await waitForTourLayout(220);
                    }

                    return;
                }

                if (document.body.classList.contains('sidebar-open')) {
                    hideSidebar();
                    await waitForTourLayout(190);
                }

                if (step.key === 'search') {
                    if (catalogMenuToggle?.getAttribute('aria-expanded') !== 'true') {
                        catalogMenuToggle?.click();
                        await waitForTourLayout(190);
                    }

                    return;
                }

                await closeCatalogMenu();
                return;
            }

            if (step.key === 'company' && desktopSidebarCollapsed) {
                desktopSidebarCollapsed = false;
                renderDesktopSidebarState();
                await waitForTourLayout(220);
            }
        };

        const placeTourDialog = (targetRect) => {
            if (!tourDialog || !targetRect) {
                return;
            }

            const viewportPadding = 12;
            const targetGap = 16;
            const dialogRect = tourDialog.getBoundingClientRect();
            const availableBelow = window.innerHeight - targetRect.bottom;
            const availableAbove = targetRect.top;
            const availableRight = window.innerWidth - targetRect.right;
            const availableLeft = targetRect.left;
            let placement = 'below';
            let left = targetRect.left + ((targetRect.width - dialogRect.width) / 2);
            let top = targetRect.bottom + targetGap;

            if (availableBelow < dialogRect.height + targetGap && availableAbove >= dialogRect.height + targetGap) {
                placement = 'above';
                top = targetRect.top - dialogRect.height - targetGap;
            } else if (availableBelow < dialogRect.height + targetGap && availableRight >= dialogRect.width + targetGap) {
                placement = 'right';
                left = targetRect.right + targetGap;
                top = targetRect.top + ((targetRect.height - dialogRect.height) / 2);
            } else if (availableBelow < dialogRect.height + targetGap && availableLeft >= dialogRect.width + targetGap) {
                placement = 'left';
                left = targetRect.left - dialogRect.width - targetGap;
                top = targetRect.top + ((targetRect.height - dialogRect.height) / 2);
            } else if (window.innerWidth < 640 && availableBelow < dialogRect.height + targetGap) {
                placement = 'docked';
                left = viewportPadding;
                top = window.innerHeight - dialogRect.height - viewportPadding;
            }

            left = Math.max(viewportPadding, Math.min(left, window.innerWidth - dialogRect.width - viewportPadding));
            top = Math.max(viewportPadding, Math.min(top, window.innerHeight - dialogRect.height - viewportPadding));

            tourDialog.dataset.placement = placement;
            tourDialog.style.left = `${Math.round(left)}px`;
            tourDialog.style.top = `${Math.round(top)}px`;

            const arrowOffset = ['above', 'below', 'docked'].includes(placement)
                ? Math.max(24, Math.min(targetRect.left + (targetRect.width / 2) - left, dialogRect.width - 24))
                : Math.max(24, Math.min(targetRect.top + (targetRect.height / 2) - top, dialogRect.height - 24));

            tourDialog.style.setProperty('--portal-tour-arrow-offset', `${Math.round(arrowOffset)}px`);
        };

        const renderTourStep = async () => {
            const renderSequence = ++tourRenderSequence;
            const step = tourSteps[currentTourStep];

            await prepareTourStep(step);

            if (renderSequence !== tourRenderSequence || !tourIsOpen) {
                return;
            }

            let target = findTourTarget(step.selector);

            if (!target) {
                target = findTourTarget('[data-portal-tour-restart]') || document.querySelector('header');
            }

            const initialRect = target?.getBoundingClientRect();

            if (initialRect && (initialRect.top < 72 || initialRect.bottom > window.innerHeight - 24)) {
                target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'center' });
                await waitForTourLayout(prefersReducedMotion ? 20 : 420);
            }

            if (renderSequence !== tourRenderSequence || !tourIsOpen) {
                return;
            }

            const targetRect = target?.getBoundingClientRect();

            if (!targetRect || !tourHighlight || !tourDialog) {
                return;
            }

            const highlightPadding = window.innerWidth < 640 ? 5 : 7;
            tourHighlight.style.left = `${Math.max(4, targetRect.left - highlightPadding)}px`;
            tourHighlight.style.top = `${Math.max(4, targetRect.top - highlightPadding)}px`;
            tourHighlight.style.width = `${Math.min(window.innerWidth - 8, targetRect.width + (highlightPadding * 2))}px`;
            tourHighlight.style.height = `${Math.min(window.innerHeight - 8, targetRect.height + (highlightPadding * 2))}px`;

            tourProgress.textContent = `${currentTourStep + 1} de ${tourSteps.length}`;
            tourTitle.textContent = step.title;
            tourDescription.textContent = step.description;
            tourIcon.innerHTML = step.icon;
            tourPrevious.classList.toggle('hidden', currentTourStep === 0);
            tourNext.textContent = currentTourStep === tourSteps.length - 1 ? 'Entendido' : 'Siguiente';
            tourDots.innerHTML = tourSteps.map((_, index) => `<span class="${index === currentTourStep ? 'is-active' : ''}"></span>`).join('');

            tourDialog.style.visibility = 'hidden';
            tourDialog.classList.remove('portal-tour-dialog-enter');
            placeTourDialog(targetRect);
            tourDialog.style.visibility = 'visible';
            void tourDialog.offsetWidth;
            tourDialog.classList.add('portal-tour-dialog-enter');
            tourNext.focus({ preventScroll: true });
        };

        const restorePageAfterTour = async () => {
            await closeCatalogMenu();

            if (!isDesktopViewport() && document.body.classList.contains('sidebar-open')) {
                hideSidebar();
            }

            if (isDesktopViewport() && desktopSidebarCollapsed !== originalDesktopSidebarState) {
                desktopSidebarCollapsed = originalDesktopSidebarState;
                renderDesktopSidebarState();
            }
        };

        const closePortalTour = async ({ persist = false } = {}) => {
            if (!tourIsOpen) {
                return;
            }

            tourIsOpen = false;
            tourRenderSequence += 1;
            portalTourRoot.classList.add('hidden');
            portalTourRoot.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('portal-tour-open');
            await restorePageAfterTour();

            if (!persist) {
                return;
            }

            try {
                const response = await fetch(portalTourRoot.dataset.completeUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ completed: true }),
                });

                if (!response.ok) {
                    throw new Error(`El servidor respondió con estado ${response.status}.`);
                }
            } catch (error) {
                console.warn('No se pudo guardar el estado del tutorial.', error);
            }
        };

        const openPortalTour = () => {
            currentTourStep = 0;
            tourIsOpen = true;
            portalTourRoot.classList.remove('hidden');
            portalTourRoot.setAttribute('aria-hidden', 'false');
            document.body.classList.add('portal-tour-open');
            renderTourStep();
        };

        tourPrevious?.addEventListener('click', () => {
            if (currentTourStep === 0) {
                return;
            }

            currentTourStep -= 1;
            renderTourStep();
        });

        tourNext?.addEventListener('click', () => {
            if (currentTourStep === tourSteps.length - 1) {
                closePortalTour({ persist: true });
                return;
            }

            currentTourStep += 1;
            renderTourStep();
        });

        tourSkipButtons.forEach((button) => {
            button.addEventListener('click', () => closePortalTour({ persist: true }));
        });

        tourRestartButtons.forEach((button) => {
            button.addEventListener('click', openPortalTour);
        });

        window.addEventListener('resize', () => {
            if (tourIsOpen) {
                renderTourStep();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (!tourIsOpen) {
                return;
            }

            if (event.key === 'ArrowRight') {
                tourNext?.click();
            } else if (event.key === 'ArrowLeft') {
                tourPrevious?.click();
            }
        });

        if (portalTourRoot.dataset.replayRequested === 'true') {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('tutorial');
            window.history.replaceState({}, '', `${cleanUrl.pathname}${cleanUrl.search}${cleanUrl.hash}`);
        }

        if (portalTourRoot.dataset.autoStart === 'true') {
            window.setTimeout(openPortalTour, 320);
        }
    }

    document.querySelectorAll('dialog.modal-dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            const rect = dialog.getBoundingClientRect();
            const clickedInsideDialog = event.clientX >= rect.left
                && event.clientX <= rect.right
                && event.clientY >= rect.top
                && event.clientY <= rect.bottom;

            if (!clickedInsideDialog) {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialogId = button.getAttribute('data-dialog-close');
            const dialog = dialogId
                ? document.getElementById(dialogId)
                : button.closest('dialog');

            if (typeof HTMLDialogElement !== 'undefined' && dialog instanceof HTMLDialogElement) {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('[data-toast]').forEach((toast) => {
        const timeout = Number(toast.dataset.toastTimeout || 4200);

        if (timeout > 0) {
            window.setTimeout(() => {
                toast.remove();
            }, timeout);
        }
    });

    document.querySelectorAll('[data-toast-dismiss]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('[data-toast]')?.remove();
        });
    });

    const loginRequiredModal = document.querySelector('[data-login-required-modal]');
    const loginRequiredLink = loginRequiredModal?.querySelector('[data-login-required-link]');
    const showLoginRequiredModal = (payload = {}) => {
        if (!loginRequiredModal) {
            window.location.href = payload.login_url || '/login';
            return;
        }

        if (loginRequiredLink && payload.login_url) {
            loginRequiredLink.setAttribute('href', payload.login_url);
        }

        openModal(loginRequiredModal);
    };

    loginRequiredModal?.querySelectorAll('[data-login-required-close]').forEach((button) => {
        button.addEventListener('click', () => closeModal(loginRequiredModal));
    });

    loginRequiredModal?.addEventListener('click', (event) => {
        if (event.target === loginRequiredModal) {
            closeModal(loginRequiredModal);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && loginRequiredModal && !loginRequiredModal.classList.contains('hidden')) {
            closeModal(loginRequiredModal);
        }
    });

    const cartSuccessFlash = document.querySelector('[data-cart-flash]');
    const prefersReducedMotion = typeof window.matchMedia === 'function'
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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

    document.addEventListener('click', (event) => {
        document.querySelectorAll('details[data-action-menu][open]').forEach((details) => {
            if (!details.contains(event.target)) {
                details.open = false;
            }
        });
    });

    const confirmModal = document.querySelector('[data-confirm-modal]');
    const confirmText = confirmModal?.querySelector('[data-confirm-text]');
    const confirmApprove = confirmModal?.querySelector('[data-confirm-approve]');
    const confirmCancel = confirmModal?.querySelector('[data-confirm-cancel]');
    let pendingForm = null;

    const resetLoadingFormState = (form) => {
        delete form.dataset.formSubmitting;

        form.querySelectorAll('[data-loading-label]').forEach((button) => {
            if (button.dataset.originalLabel) {
                button.textContent = button.dataset.originalLabel;
                delete button.dataset.originalLabel;
            }

            button.disabled = false;
        });
    };

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                form.dataset.confirmed = 'false';
                return;
            }

            event.preventDefault();
            pendingForm = form;

            if (confirmText) {
                confirmText.textContent = form.dataset.confirm || 'Confirmar acci\u00f3n';
            }

            openModal(confirmModal);
        });
    });

    confirmApprove?.addEventListener('click', () => {
        if (!pendingForm) {
            closeModal(confirmModal);
            return;
        }

        const form = pendingForm;
        pendingForm = null;
        closeModal(confirmModal);
        resetLoadingFormState(form);
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });

    confirmCancel?.addEventListener('click', () => {
        if (pendingForm) {
            resetLoadingFormState(pendingForm);
        }

        pendingForm = null;
        closeModal(confirmModal);
    });

    confirmModal?.addEventListener('click', (event) => {
        if (event.target === confirmModal) {
            if (pendingForm) {
                resetLoadingFormState(pendingForm);
            }

            pendingForm = null;
            closeModal(confirmModal);
        }
    });

    document.querySelectorAll('form[data-loading-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.ajaxCart === 'true') {
                return;
            }

            // Another submit handler (e.g. data-confirm) may have cancelled this attempt.
            if (event.defaultPrevented) {
                return;
            }

            if (typeof form.codexBeforeSubmit === 'function' && form.codexBeforeSubmit(event) === false) {
                event.preventDefault();
                return;
            }

            if (form.dataset.formSubmitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.formSubmitting = 'true';

            form.querySelectorAll('[data-loading-label]').forEach((button) => {
                if (button.disabled) {
                    return;
                }

                button.dataset.originalLabel = button.textContent;
                button.textContent = button.dataset.loadingLabel || 'Procesando...';
                button.disabled = true;
            });
        });
    });

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

    const bulkTable = document.querySelector('[data-bulk-table]');

    if (bulkTable) {
        const master = bulkTable.querySelector('[data-bulk-master]');
        const rows = () => Array.from(bulkTable.querySelectorAll('[data-bulk-row]'));
        const countEl = document.querySelector('[data-bulk-count]');
        const copyButton = document.querySelector('[data-bulk-copy]');
        const bulkForm = document.querySelector('form[data-bulk-form]');
        const selectedInputsContainer = bulkForm?.querySelector('[data-bulk-selected-inputs]');
        const bulkActionInput = bulkForm?.querySelector('[data-bulk-action-input]');
        const bulkButtons = bulkForm ? Array.from(bulkForm.querySelectorAll('[data-bulk-submit]')) : [];
        const bulkActionButtons = bulkForm ? Array.from(bulkForm.querySelectorAll('[data-bulk-action-trigger]')) : [];
        const defaultConfirmText = bulkForm?.dataset.confirm || 'Confirmar acción';

        const refreshBulk = () => {
            const selected = rows().filter((row) => row.checked).map((row) => row.value);

            if (countEl) {
                countEl.textContent = String(selected.length);
            }

            if (copyButton) {
                copyButton.disabled = selected.length === 0;
                copyButton.dataset.codes = selected.join(', ');
            }

            if (selectedInputsContainer) {
                selectedInputsContainer.innerHTML = selected
                    .map((id) => `<input type="hidden" name="product_ids[]" value="${id}">`)
                    .join('');
            }

            bulkButtons.forEach((button) => {
                button.disabled = selected.length === 0;
            });

            if (master) {
                const allSelected = selected.length > 0 && selected.length === rows().length;
                master.checked = allSelected;
                master.indeterminate = selected.length > 0 && !allSelected;
            }
        };

        master?.addEventListener('change', () => {
            rows().forEach((row) => {
                row.checked = master.checked;
            });
            refreshBulk();
        });

        rows().forEach((row) => {
            row.addEventListener('change', refreshBulk);
        });

        copyButton?.addEventListener('click', async () => {
            const codes = copyButton.dataset.codes;

            if (!codes) {
                return;
            }

            try {
                await navigator.clipboard.writeText(codes);
                copyButton.textContent = 'Copiado';
                window.setTimeout(() => {
                    copyButton.textContent = 'Copiar CTC';
                }, 1400);
            } catch (error) {
                copyButton.textContent = 'No disponible';
            }
        });

        bulkActionButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (!(button instanceof HTMLElement)) {
                    return;
                }

                if (bulkActionInput) {
                    bulkActionInput.value = button.dataset.bulkActionValue || '';
                }

                if (bulkForm) {
                    bulkForm.dataset.confirm = button.dataset.bulkConfirm || defaultConfirmText;
                }
            });
        });

        bulkForm?.addEventListener('submit', (event) => {
            if (rows().every((row) => !row.checked)) {
                event.preventDefault();
            }
        });

        refreshBulk();
    }

    document.querySelectorAll('[data-product-list]').forEach((productList) => {
        let loading = false;

        const setButtonLoading = (isLoading) => {
            const button = productList.querySelector('[data-product-load-more]');
            if (!button) {
                return;
            }

            if (isLoading) {
                button.disabled = true;
                button.dataset.originalLabel = button.textContent.trim();
                button.textContent = button.dataset.loadingLabel || 'Cargando...';
                return;
            }

            button.disabled = false;
            if (button.dataset.originalLabel) {
                button.textContent = button.dataset.originalLabel;
            }
        };

        const loadMore = async () => {
            if (loading || productList.dataset.hasMore !== 'true') {
                return;
            }

            loading = true;
            setButtonLoading(true);

            const params = new URLSearchParams(productList.dataset.baseQuery || '');
            const pageParam = productList.dataset.pageParam || 'page';
            params.set(pageParam, productList.dataset.nextPage || '1');

            const listName = productList.dataset.list || '';
            if (listName) {
                params.set('list', listName);
            }

            try {
                const response = await fetch(`${productList.dataset.loadUrl}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('product-list-load-failed');
                }

                const payload = await response.json();
                const grid = productList.querySelector('[data-product-grid]');
                const controls = productList.querySelector('[data-product-list-controls]');

                grid?.insertAdjacentHTML('beforeend', payload.html || '');

                if (controls) {
                    controls.innerHTML = payload.controlsHtml || '';
                }

                productList.dataset.currentPage = String(payload.currentPage || productList.dataset.currentPage || '1');
                productList.dataset.nextPage = String(payload.nextPage || productList.dataset.nextPage || '1');
                productList.dataset.hasMore = payload.hasMore ? 'true' : 'false';

                if (payload.pushUrl && typeof window.history?.pushState === 'function') {
                    window.history.pushState({}, '', payload.pushUrl);
                }
            } catch {
                // Keep the current controls so the user can retry manually.
            } finally {
                loading = false;
                setButtonLoading(false);
            }
        };

        productList.addEventListener('click', (event) => {
            const button = event.target.closest('[data-product-load-more]');
            if (!button || !productList.contains(button)) {
                return;
            }

            event.preventDefault();
            loadMore();
        });
    });

    document.querySelectorAll('form[data-status-form], form[data-shipping-form]').forEach((shippingForm) => {
        const statusSelect = shippingForm.querySelector('[data-status-select]');
        const shippingFields = shippingForm.querySelector('[data-shipping-fields]');
        const trackingInput = shippingForm.querySelector('[data-tracking-number]');
        const carrierInput = shippingForm.querySelector('[data-carrier-input]');
        const carrierEditButton = shippingForm.querySelector('[data-carrier-edit]');
        const carrierData = shippingForm.querySelector('[data-carrier-prefixes]');

        if (!shippingFields || !trackingInput || !carrierInput) {
            return;
        }

        let carrierWasEdited = carrierInput.dataset.carrierManual === 'true';

        let carrierPrefixes = {};

        try {
            carrierPrefixes = JSON.parse(carrierData?.textContent || '{}');
        } catch {
            carrierPrefixes = {};
        }

        const renderCarrier = () => {
            if (carrierWasEdited) {
                return;
            }

            const prefix = trackingInput.value.trim().charAt(0);
            const suggestedCarrier = carrierPrefixes[prefix] || '';

            carrierInput.value = suggestedCarrier;
            carrierInput.readOnly = suggestedCarrier !== '';
        };

        const syncShippingFields = () => {
            const isDispatched = statusSelect ? statusSelect.value === 'dispatched' : true;

            shippingFields.classList.toggle('hidden', !isDispatched);
            trackingInput.required = isDispatched;
            trackingInput.disabled = !isDispatched;
            carrierInput.required = isDispatched;
            carrierInput.disabled = !isDispatched;

            if (isDispatched) {
                renderCarrier();
            }
        };

        statusSelect?.addEventListener('change', syncShippingFields);
        trackingInput.addEventListener('input', () => {
            if (trackingInput.value.trim() === '') {
                carrierWasEdited = false;
                carrierInput.value = '';
                carrierInput.readOnly = false;

                return;
            }

            renderCarrier();
        });
        carrierInput.addEventListener('input', () => {
            carrierWasEdited = true;
        });
        carrierEditButton?.addEventListener('click', () => {
            carrierWasEdited = true;
            carrierInput.readOnly = false;
            carrierInput.focus();
            carrierInput.select();
        });
        syncShippingFields();
    });

    const productForm = document.querySelector('form[data-product-form]');

    if (productForm) {
        const formatMoney = (value) => {
            const parsed = Number(value);
            if (Number.isNaN(parsed)) {
                return '$0';
            }

            return `$${parsed.toLocaleString('es-CO', { maximumFractionDigits: 0 })}`;
        };

        const formatStock = (value) => {
            if (value === '' || value === null || value === undefined) {
                return 'Sin definir';
            }

            const parsed = Number(value);
            if (Number.isNaN(parsed)) {
                return 'Sin definir';
            }

            if (Math.abs(parsed - Math.round(parsed)) < 0.00001) {
                return `${Math.round(parsed).toLocaleString('es-CO')}`;
            }

            return parsed.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        const formatFileSize = (bytes) => {
            if (!Number.isFinite(bytes) || bytes <= 0) {
                return '0 MB';
            }

            const megabytes = bytes / (1024 * 1024);
            const decimals = Math.abs(megabytes - Math.round(megabytes)) < 0.05 ? 0 : 1;

            return `${megabytes.toLocaleString('es-CO', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            })} MB`;
        };

        const priceInput = productForm.querySelector('[data-live-price]');
        const stockInput = productForm.querySelector('[data-live-stock]');
        const priceOutputs = productForm.querySelectorAll('[data-live-price-output], [data-live-price-output-sidebar]');
        const stockOutputs = productForm.querySelectorAll('[data-live-stock-output], [data-live-stock-output-sidebar]');
        const previewNameInput = productForm.querySelector('[data-preview-name]');
        const previewBrandInput = productForm.querySelector('[data-preview-brand]');
        const previewCategoryInput = productForm.querySelector('[data-preview-category]');
        const previewDescriptionInput = productForm.querySelector('[data-preview-description]');
        const previewNameOutput = productForm.querySelector('[data-preview-name-output]');
        const previewBrandOutput = productForm.querySelector('[data-preview-brand-output]');
        const previewCategoryOutput = productForm.querySelector('[data-preview-category-output]');
        const previewDescriptionOutput = productForm.querySelector('[data-preview-description-output]');
        const mediaSection = document.getElementById('media-documentos');
        const uploadFeedback = productForm.querySelector('[data-upload-feedback]');
        const uploadFeedbackMessage = productForm.querySelector('[data-upload-feedback-message]');
        const photosInput = productForm.querySelector('#photos');
        const techSheetInput = productForm.querySelector('#tech_sheet');
        const manualInput = productForm.querySelector('#manual');
        const invimaInput = productForm.querySelector('#invima');
        const quickGuideInput = productForm.querySelector('#quick_guide');
        const calibrationDocumentInput = productForm.querySelector('#calibration_document');
        const documentInputs = [techSheetInput, manualInput, invimaInput, quickGuideInput, calibrationDocumentInput].filter(Boolean);
        const totalMaxKb = Number(productForm.dataset.totalMaxKb || 0);
        const totalMaxText = productForm.dataset.totalMaxText || `${totalMaxKb / 1024} MB`;

        const hideUploadFeedback = () => {
            if (!uploadFeedback) {
                return;
            }

            uploadFeedback.classList.add('hidden');

            if (uploadFeedbackMessage) {
                uploadFeedbackMessage.textContent = '';
            }
        };

        const showUploadFeedback = (message, input, { notify = false } = {}) => {
            if (uploadFeedback) {
                uploadFeedback.classList.remove('hidden');
            }

            if (uploadFeedbackMessage) {
                uploadFeedbackMessage.textContent = message;
            }

            if (notify) {
                showInlineToast(message, 'error');
            }

            if (mediaSection && notify) {
                mediaSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            if (uploadFeedback && notify) {
                window.setTimeout(() => uploadFeedback.focus(), 50);
            } else if (input && notify && typeof input.focus === 'function') {
                window.setTimeout(() => input.focus(), 50);
            }
        };

        const validateProductUploads = ({ notify = false } = {}) => {
            hideUploadFeedback();

            const photoFiles = photosInput?.files ? Array.from(photosInput.files) : [];
            const documentFileGroups = documentInputs.map((input) => ({
                input,
                files: input.files ? Array.from(input.files) : [],
                maxSizeKb: Number(input.dataset.maxSizeKb || 0),
                maxSizeText: input.dataset.maxSizeText || `${Number(input.dataset.maxSizeKb || 0) / 1024} MB`,
                label: input.dataset.uploadLabel || 'documento PDF',
            }));
            const photoMaxFiles = Number(photosInput?.dataset.maxFiles || 0);
            const photoMaxSizeKb = Number(photosInput?.dataset.maxSizeKb || 0);
            const photoMaxSizeText = photosInput?.dataset.maxSizeText || `${photoMaxSizeKb / 1024} MB`;
            const photoLabel = photosInput?.dataset.uploadLabel || 'fotos del producto';

            if (photoMaxFiles > 0 && photoFiles.length > photoMaxFiles) {
                showUploadFeedback(
                    `Solo puedes seleccionar hasta ${photoMaxFiles} ${photoLabel}. Reduce la cantidad de archivos e intentalo nuevamente.`,
                    photosInput,
                    { notify },
                );
                return false;
            }

            const oversizedPhoto = photoFiles.find((file) => file.size > photoMaxSizeKb * 1024);
            if (oversizedPhoto) {
                showUploadFeedback(
                    `La foto "${oversizedPhoto.name}" supera el maximo permitido de ${photoMaxSizeText}. Reduce su peso antes de guardarla.`,
                    photosInput,
                    { notify },
                );
                return false;
            }

            for (const group of documentFileGroups) {
                const oversizedDocument = group.maxSizeKb > 0
                    ? group.files.find((file) => file.size > group.maxSizeKb * 1024)
                    : null;

                if (oversizedDocument) {
                    showUploadFeedback(
                        `El archivo "${oversizedDocument.name}" en ${group.label} supera el maximo permitido de ${group.maxSizeText}. Reduce el PDF antes de guardarlo.`,
                        group.input,
                        { notify },
                    );
                    return false;
                }
            }

            const documentFiles = documentFileGroups.flatMap((group) => group.files);
            const totalSelectedBytes = [...photoFiles, ...documentFiles]
                .reduce((sum, file) => sum + (Number.isFinite(file.size) ? file.size : 0), 0);

            if (totalMaxKb > 0 && totalSelectedBytes > totalMaxKb * 1024) {
                showUploadFeedback(
                    `La carga actual pesa ${formatFileSize(totalSelectedBytes)} y el formulario permite hasta ${totalMaxText} en total. Reduce la cantidad o el peso de fotos y documentos.`,
                    photosInput || documentInputs[0],
                    { notify },
                );
                return false;
            }

            return true;
        };

        const refreshProductPreview = () => {
            const currentPrice = priceInput?.value ?? '';
            const currentStock = stockInput?.value ?? '';
            const currentName = previewNameInput?.value?.trim() || 'Nombre del producto';
            const currentBrand = previewBrandInput?.value?.trim() || 'Sin marca';
            const categoryOption = previewCategoryInput?.selectedOptions?.[0];
            const currentCategory = categoryOption?.textContent?.trim() || 'Sin categoría seleccionada';
            const currentDescription = previewDescriptionInput?.value?.trim() || 'Sin descripción comercial';

            priceOutputs.forEach((output) => {
                output.textContent = formatMoney(currentPrice);
            });

            stockOutputs.forEach((output) => {
                output.textContent = formatStock(currentStock);
            });

            if (previewNameOutput) {
                previewNameOutput.textContent = currentName;
            }

            if (previewBrandOutput) {
                previewBrandOutput.textContent = currentBrand;
            }

            if (previewCategoryOutput) {
                previewCategoryOutput.textContent = currentCategory;
            }

            if (previewDescriptionOutput) {
                previewDescriptionOutput.textContent = currentDescription.slice(0, 140);
            }
        };

        [priceInput, stockInput, previewNameInput, previewBrandInput, previewCategoryInput, previewDescriptionInput]
            .filter(Boolean)
            .forEach((field) => {
                field.addEventListener('input', refreshProductPreview);
                field.addEventListener('change', refreshProductPreview);
            });

        refreshProductPreview();
        productForm.codexBeforeSubmit = () => validateProductUploads({ notify: true });

        photosInput?.addEventListener('change', () => {
            validateProductUploads();
        });

        documentInputs.forEach((input) => {
            input.addEventListener('change', () => {
                validateProductUploads();
            });
        });

        const variantToggle = productForm.querySelector('input[name="has_variants"][value="1"]');
        const variantSection = productForm.querySelector('[data-variant-section]');
        const variantRowsContainer = productForm.querySelector('[data-variant-rows]');
        const variantTemplate = productForm.querySelector('[data-variant-template]');
        const addVariantRowButton = productForm.querySelector('[data-variant-add-row]');
        const setVariantRequiredRules = (enabled) => {
            if (priceInput instanceof HTMLInputElement) {
                priceInput.required = !enabled;
                priceInput.readOnly = enabled;
                priceInput.classList.toggle('bg-slate-100', enabled);
                priceInput.classList.toggle('cursor-not-allowed', enabled);
            }

            const basePriceHelp = productForm.querySelector('[data-product-base-price-help]');
            const variantsPriceHelp = productForm.querySelector('[data-product-base-price-variants-help]');

            if (basePriceHelp) {
                basePriceHelp.classList.toggle('hidden', enabled);
            }

            if (variantsPriceHelp) {
                variantsPriceHelp.classList.toggle('hidden', !enabled);
            }

            if (!variantRowsContainer) {
                return;
            }

            variantRowsContainer
                .querySelectorAll('input[name$="[value]"], input[name$="[price]"]')
                .forEach((input) => {
                    if (input instanceof HTMLInputElement) {
                        input.required = enabled;
                    }
                });
        };

        const contapymeStockManaged = productForm.dataset.contapymeStockManaged === '1';

        const applyContaPymeVariantStockLocks = () => {
            if (!contapymeStockManaged || !variantRowsContainer) {
                return;
            }

            variantRowsContainer
                .querySelectorAll('[data-variant-stock-input], input[name$="[stock]"]')
                .forEach((input) => {
                    if (input instanceof HTMLInputElement) {
                        input.disabled = true;
                    }
                });
        };

        let variantIndex = 0;
        if (variantRowsContainer) {
            variantRowsContainer.querySelectorAll('input[name^="variants["]').forEach((input) => {
                const match = input.name.match(/^variants\[(\d+)\]/);
                if (!match) {
                    return;
                }

                const current = Number(match[1]);
                if (Number.isFinite(current)) {
                    variantIndex = Math.max(variantIndex, current + 1);
                }
            });
        }

        const appendVariantRow = () => {
            if (!variantRowsContainer || !variantTemplate) {
                return;
            }

            const rawHtml = (variantTemplate.innerHTML || '').trim();
            if (!rawHtml) {
                return;
            }

            const rowHtml = rawHtml.replaceAll('__INDEX__', String(variantIndex));
            variantIndex += 1;
            variantRowsContainer.insertAdjacentHTML('beforeend', rowHtml);

            const variantsEnabled = variantToggle instanceof HTMLInputElement ? variantToggle.checked : false;
            setVariantRequiredRules(variantsEnabled);
            applyContaPymeVariantStockLocks();
        };

        const refreshVariantSection = () => {
            const enabled = variantToggle instanceof HTMLInputElement ? variantToggle.checked : false;

            if (variantSection) {
                variantSection.classList.toggle('hidden', !enabled);
            }

            if (enabled && variantRowsContainer && variantRowsContainer.querySelectorAll('[data-variant-row]').length === 0) {
                appendVariantRow();
            }

            setVariantRequiredRules(enabled);
            applyContaPymeVariantStockLocks();
        };

        addVariantRowButton?.addEventListener('click', () => {
            appendVariantRow();
        });

        variantRowsContainer?.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-variant-remove-row]');

            if (!removeButton) {
                return;
            }

            const row = removeButton.closest('[data-variant-row]');
            if (!row) {
                return;
            }

            const rows = variantRowsContainer.querySelectorAll('[data-variant-row]');
            if (rows.length <= 1) {
                row.remove();

                if (variantToggle instanceof HTMLInputElement) {
                    variantToggle.checked = false;
                }

                refreshVariantSection();
                return;
            }

            row.remove();
        });

        variantToggle?.addEventListener('change', refreshVariantSection);
        refreshVariantSection();

        const skuInput = productForm.querySelector('[data-sku-input]');
        const skuFeedback = productForm.querySelector('[data-sku-feedback]');
        const skuCheckUrl = productForm.dataset.skuCheckUrl;
        const skuIgnore = productForm.dataset.skuIgnore;
        let skuTimeout;
        let skuAbortController;

        const setSkuFeedback = (message, type = 'neutral') => {
            if (!skuFeedback) {
                return;
            }

            skuFeedback.textContent = message;
            skuFeedback.classList.remove('text-emerald-700', 'text-red-700', 'text-slate-500');

            if (type === 'ok') {
                skuFeedback.classList.add('text-emerald-700');
            } else if (type === 'error') {
                skuFeedback.classList.add('text-red-700');
            } else {
                skuFeedback.classList.add('text-slate-500');
            }
        };

        const runSkuCheck = async () => {
            if (!skuInput || !skuCheckUrl) {
                return;
            }

            const value = skuInput.value.trim();

            if (!value) {
                skuInput.setCustomValidity('');
                setSkuFeedback('Escribe un SKU único para evitar conflictos en catálogo y pedidos.', 'neutral');
                return;
            }

            if (skuAbortController) {
                skuAbortController.abort();
            }

            skuAbortController = new AbortController();

            const query = new URLSearchParams({ sku: value });
            if (skuIgnore) {
                query.set('ignore', skuIgnore);
            }

            try {
                const response = await fetch(`${skuCheckUrl}?${query.toString()}`, {
                    headers: { Accept: 'application/json' },
                    signal: skuAbortController.signal,
                });

                if (!response.ok) {
                    throw new Error('sku-check-failed');
                }

                const payload = await response.json();

                if (payload.available) {
                    skuInput.setCustomValidity('');
                    setSkuFeedback(payload.message || 'SKU disponible.', 'ok');
                } else {
                    skuInput.setCustomValidity('Este SKU ya está en uso.');
                    setSkuFeedback(payload.message || 'Este SKU ya está en uso.', 'error');
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    skuInput.setCustomValidity('');
                    setSkuFeedback('No se pudo validar el SKU en este momento.', 'neutral');
                }
            }
        };

        if (skuInput) {
            skuInput.addEventListener('input', () => {
                skuInput.setCustomValidity('');
                window.clearTimeout(skuTimeout);
                skuTimeout = window.setTimeout(runSkuCheck, 450);
            });

            skuInput.addEventListener('blur', runSkuCheck);
        }

        const formsWithUnsavedGuard = document.querySelectorAll('form[data-unsaved-guard]');

        formsWithUnsavedGuard.forEach((form) => {
            let hasChanges = false;

            const markDirty = () => {
                hasChanges = true;
            };

            form.addEventListener('input', markDirty);
            form.addEventListener('change', markDirty);
            form.addEventListener('submit', () => {
                hasChanges = false;
            });

            window.addEventListener('beforeunload', (event) => {
                if (!hasChanges) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            });
        });
    }

    // Magic-link QR canvases for payment receipt upload.
    document.querySelectorAll('canvas[data-qr-url]').forEach(async (canvas) => {
        const url = canvas.getAttribute('data-qr-url');
        if (!url) {
            return;
        }

        try {
            const QRCode = (await import('qrcode')).default;
            await QRCode.toCanvas(canvas, url, {
                width: 160,
                margin: 1,
                color: { dark: '#0f172a', light: '#ffffff' },
            });
        } catch (error) {
            canvas.replaceWith(Object.assign(document.createElement('p'), {
                className: 'text-xs text-slate-500',
                textContent: 'No se pudo generar el QR. Usa el enlace de texto.',
            }));
        }
    });

    // Poll payment status while waiting for cross-device receipt upload.
    document.querySelectorAll('[data-payment-panel]').forEach((panel) => {
        const statusUrl = panel.getAttribute('data-payment-status-url');
        let currentStatus = panel.getAttribute('data-payment-status') || '';
        const expiresAtRaw = panel.getAttribute('data-payment-expires-at');
        const expiresAt = expiresAtRaw ? Date.parse(expiresAtRaw) : null;
        const badge = panel.querySelector('[data-payment-status-badge]');
        const message = panel.querySelector('[data-payment-status-message]');
        const uploadZone = panel.querySelector('[data-payment-upload-zone]');
        const pollable = ['pending_upload', 'rejected'];
        const badgeClassByStatus = {
            not_applicable: 'payment-status-na',
            pending_upload: 'payment-status-pending',
            confirming: 'payment-status-confirming',
            validated: 'payment-status-validated',
            rejected: 'payment-status-rejected',
            expired: 'payment-status-expired',
        };
        const statusMessages = {
            confirming: 'Comprobante recibido. Estamos confirmando tu pago.',
            validated: 'Pago validado. Continuamos con la gestión de tu pedido.',
            expired: 'El plazo de pago venció y la reserva se liberó. Puedes armar un nuevo pedido desde el catálogo.',
            rejected: 'Tu comprobante fue rechazado. Sube uno nuevo antes de que venza la reserva.',
        };

        if (!statusUrl || !pollable.includes(currentStatus)) {
            return;
        }

        let failures = 0;
        let timer = null;
        let celebrating = false;
        let stopped = false;
        let inFlight = false;
        const baseIntervalMs = 8000;
        const maxIntervalMs = 60000;
        const maxFailures = 8;

        const clearTimer = () => {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
        };

        const stop = () => {
            stopped = true;
            clearTimer();
        };

        const nextDelayMs = () => {
            if (failures <= 0) {
                return baseIntervalMs;
            }

            // Exponential backoff on consecutive network/5xx failures: 8s → 16s → 32s → 60s.
            return Math.min(maxIntervalMs, baseIntervalMs * (2 ** Math.min(failures, 3)));
        };

        const scheduleNext = () => {
            if (stopped || celebrating) {
                return;
            }

            if (document.visibilityState === 'hidden') {
                clearTimer();
                return;
            }

            clearTimer();
            timer = window.setTimeout(() => {
                tick();
            }, nextDelayMs());
        };

        const shouldStopForTtl = () => {
            if (!expiresAt || Number.isNaN(expiresAt)) {
                return false;
            }

            // Stop a bit after reservation TTL (+2 min margin).
            return Date.now() > (expiresAt + 120000);
        };

        const syncBadgeClass = (status) => {
            if (!badge) {
                return;
            }

            Object.values(badgeClassByStatus).forEach((cls) => badge.classList.remove(cls));
            const next = badgeClassByStatus[status];
            if (next) {
                badge.classList.add(next);
            }
        };

        const renderConfirmingUi = (payload) => {
            currentStatus = payload.payment_status || currentStatus;
            panel.setAttribute('data-payment-status', currentStatus);

            if (badge) {
                badge.textContent = payload.payment_status_label || 'Confirmando pago';
            }

            syncBadgeClass(currentStatus);

            if (message) {
                message.textContent = statusMessages.confirming;
            }

            if (uploadZone) {
                uploadZone.hidden = true;
            }
        };

        const celebrateReceiptReceived = () => {
            const modal = document.querySelector('[data-payment-success-modal]');

            if (celebrating || !modal || modal.classList.contains('is-active')) {
                return;
            }

            celebrating = true;
            clearTimer();

            const previousFocus = document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;
            const dialog = modal.querySelector('[data-payment-success-dialog]');
            const label = modal.querySelector('[data-payment-success-label]');
            const previousOverflow = document.body.style.overflow;

            modal.hidden = false;
            modal.classList.add('is-active');
            document.body.style.overflow = 'hidden';

            // Re-set label so aria-live announces once the dialog is shown.
            if (label) {
                label.textContent = '';
                label.textContent = 'Comprobante recibido';
            }

            dialog?.focus({ preventScroll: true });

            let closed = false;
            const close = () => {
                if (closed) {
                    return;
                }

                closed = true;
                modal.classList.remove('is-active');
                modal.hidden = true;
                document.body.style.overflow = previousOverflow;
                celebrating = false;
                document.removeEventListener('keydown', onKeydown);

                if (previousFocus && document.contains(previousFocus)) {
                    previousFocus.focus({ preventScroll: true });
                }
            };

            const onKeydown = (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    close();
                }
            };

            modal.querySelector('[data-payment-success-dismiss]')?.addEventListener('click', close, { once: true });
            document.addEventListener('keydown', onKeydown);
        };

        const applyStatus = (payload) => {
            const previousStatus = currentStatus;
            const nextStatus = payload.payment_status || currentStatus;

            if (nextStatus === currentStatus) {
                return;
            }

            // Terminal / non-upload states: update from payload, stop polling. No full reload.
            if (!pollable.includes(nextStatus)) {
                stop();

                // Positive poll response: receipt landed in BD → confirming.
                if (nextStatus === 'confirming' && pollable.includes(previousStatus)) {
                    renderConfirmingUi(payload);
                    celebrateReceiptReceived();
                    return;
                }

                currentStatus = nextStatus;
                panel.setAttribute('data-payment-status', currentStatus);

                if (badge && payload.payment_status_label) {
                    badge.textContent = payload.payment_status_label;
                }

                syncBadgeClass(currentStatus);

                if (message && statusMessages[currentStatus]) {
                    message.textContent = statusMessages[currentStatus];
                }

                if (uploadZone && !payload.allows_upload) {
                    uploadZone.hidden = true;
                }

                return;
            }

            currentStatus = nextStatus;
            panel.setAttribute('data-payment-status', currentStatus);

            if (badge && payload.payment_status_label) {
                badge.textContent = payload.payment_status_label;
            }

            syncBadgeClass(currentStatus);

            if (message && statusMessages[currentStatus]) {
                message.textContent = statusMessages[currentStatus];
            }

            if (uploadZone) {
                uploadZone.hidden = !payload.allows_upload;
            }
        };

        const tick = async () => {
            if (stopped || celebrating || inFlight) {
                return;
            }

            if (document.visibilityState === 'hidden') {
                clearTimer();
                return;
            }

            if (shouldStopForTtl()) {
                stop();
                if (message) {
                    message.textContent = `${message.textContent} (El plazo de reserva venció; recarga si necesitás el estado final.)`;
                }
                return;
            }

            inFlight = true;

            try {
                const response = await fetch(statusUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`poll failed: ${response.status}`);
                }

                const payload = await response.json();
                failures = 0;
                await applyStatus(payload);
            } catch (error) {
                failures += 1;
                if (failures >= maxFailures) {
                    stop();
                    if (message) {
                        message.textContent = `${message.textContent} (No pudimos actualizar el estado automáticamente; recarga la página.)`;
                    }
                    return;
                }
            } finally {
                inFlight = false;
            }

            if (!stopped && pollable.includes(currentStatus)) {
                scheduleNext();
            }
        };

        const onVisibilityChange = () => {
            if (stopped) {
                return;
            }

            if (document.visibilityState === 'hidden') {
                // Pause for real: clear the timer so we don't hit the API in background.
                clearTimer();
                return;
            }

            // Resume with an immediate poll when the tab becomes visible again.
            tick();
        };

        document.addEventListener('visibilitychange', onVisibilityChange);

        if (document.visibilityState === 'visible') {
            scheduleNext();
        }
    });
});
