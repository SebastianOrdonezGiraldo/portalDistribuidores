import { prefersReducedMotion } from '../lib/motion';

/**
 * Guided tour of the portal for distributors.
 *
 * @param {ReturnType<import('../layout/sidebar').initSidebar>} sidebar
 */
export function initPortalTour(sidebar) {
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
        const originalDesktopSidebarState = sidebar.isDesktopCollapsed();
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
            if (!sidebar.isDesktopViewport()) {
                if (step.key === 'company') {
                    await closeCatalogMenu();

                    if (!document.body.classList.contains('sidebar-open')) {
                        sidebar.showSidebar();
                        await waitForTourLayout(220);
                    }

                    return;
                }

                if (document.body.classList.contains('sidebar-open')) {
                    sidebar.hideSidebar();
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

            if (step.key === 'company' && sidebar.isDesktopCollapsed()) {
                sidebar.setDesktopCollapsed(false);
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

            if (!sidebar.isDesktopViewport() && document.body.classList.contains('sidebar-open')) {
                sidebar.hideSidebar();
            }

            if (sidebar.isDesktopViewport() && sidebar.isDesktopCollapsed() !== originalDesktopSidebarState) {
                sidebar.setDesktopCollapsed(originalDesktopSidebarState);
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
}
