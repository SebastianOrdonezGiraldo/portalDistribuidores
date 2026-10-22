/**
 * Mobile drawer and collapsible desktop sidebar of the distributor/admin layout.
 *
 * Returns the controls the portal tour needs to open, close and expand it.
 */
export function initSidebar() {
    const sidebar = document.querySelector('[data-sidebar]');
    const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
    const sidebarToggles = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
    const sidebarCollapseButton = document.querySelector('[data-sidebar-collapse]');
    const sidebarCollapseIcon = sidebarCollapseButton?.querySelector('[data-sidebar-collapse-icon]');
    const sidebarExpandIcon = sidebarCollapseButton?.querySelector('[data-sidebar-expand-icon]');
    const sidebarNavLinks = Array.from(document.querySelectorAll('[data-sidebar-nav-link]'));
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

    return {
        isDesktopViewport,
        showSidebar,
        hideSidebar,
        isDesktopCollapsed: () => desktopSidebarCollapsed,
        setDesktopCollapsed: (collapsed) => {
            desktopSidebarCollapsed = collapsed;
            renderDesktopSidebarState();
        },
    };
}
