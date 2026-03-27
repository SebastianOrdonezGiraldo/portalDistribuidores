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

const showInlineToast = (message, variant = 'success') => {
    let container = document.querySelector('[data-inline-toast-stack]');

    if (!container) {
        container = document.createElement('div');
        container.dataset.inlineToastStack = 'true';
        container.className = 'pointer-events-none fixed inset-x-3 top-3 z-[85] space-y-2 sm:inset-x-auto sm:right-4 sm:top-4 sm:w-[22rem]';
        document.body.append(container);
    }

    const palette = {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
        error: 'border-red-200 bg-red-50 text-red-900',
        info: 'border-sky-200 bg-sky-50 text-sky-900',
    };

    const icons = {
        success: '<svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>',
        error: '<svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        info: '<svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
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

window.PortalUI = {
    closeModal,
    openModal,
    showInlineToast,
};

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
    const sidebarToggles = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
    const isDesktopViewport = () => typeof window.matchMedia === 'function'
        && window.matchMedia('(min-width: 1024px)').matches;

    const syncSidebarA11y = (expanded) => {
        sidebarToggles.forEach((button) => {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });

        if (!sidebar) {
            return;
        }

        const isVisible = expanded || isDesktopViewport();
        sidebar.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
    };

    const showSidebar = () => {
        if (!sidebar || isDesktopViewport()) {
            return;
        }

        sidebar.classList.remove('-translate-x-full');
        sidebarOverlay?.classList.remove('hidden');
        document.body.classList.add('sidebar-open');
        syncSidebarA11y(true);
    };

    const hideSidebar = () => {
        if (!sidebar) {
            return;
        }

        if (!isDesktopViewport()) {
            sidebar.classList.add('-translate-x-full');
        }

        sidebarOverlay?.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
        syncSidebarA11y(false);
    };

    const syncSidebarLayout = () => {
        if (!sidebar) {
            return;
        }

        if (isDesktopViewport()) {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay?.classList.add('hidden');
            document.body.classList.remove('sidebar-open');
            syncSidebarA11y(false);
            return;
        }

        sidebar.classList.add('-translate-x-full');
        sidebarOverlay?.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
        syncSidebarA11y(false);
    };

    sidebarToggles.forEach((button) => {
        button.addEventListener('click', showSidebar);
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
            } else {
                closeModal(dialog);
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

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                form.dataset.confirmed = 'false';
                return;
            }

            event.preventDefault();
            pendingForm = form;

            if (confirmText) {
                confirmText.textContent = form.dataset.confirm || 'Confirmar acción';
            }

            if (typeof HTMLDialogElement !== 'undefined' && confirmModal instanceof HTMLDialogElement) {
                confirmModal.showModal();
            } else {
                openModal(confirmModal);
            }
        });
    });

    confirmApprove?.addEventListener('click', () => {
        if (!pendingForm) {
            closeModal(confirmModal);
            return;
        }

        pendingForm.dataset.confirmed = 'true';
        pendingForm.requestSubmit();
        pendingForm = null;

        if (typeof HTMLDialogElement !== 'undefined' && confirmModal instanceof HTMLDialogElement) {
            confirmModal.close();
        } else {
            closeModal(confirmModal);
        }
    });

    confirmCancel?.addEventListener('click', () => {
        pendingForm = null;

        if (typeof HTMLDialogElement !== 'undefined' && confirmModal instanceof HTMLDialogElement) {
            confirmModal.close();
        } else {
            closeModal(confirmModal);
        }
    });

    confirmModal?.addEventListener('click', (event) => {
        if (event.target === confirmModal) {
            pendingForm = null;
            closeModal(confirmModal);
        }
    });

    document.querySelectorAll('form[data-loading-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.ajaxCart === 'true') {
                return;
            }

            if (form.dataset.formSubmitting === 'true' || form.dataset.formPreparing === 'true') {
                event.preventDefault();
                return;
            }

            if (form.dataset.skipBeforeSubmit !== 'true' && typeof form.codexBeforeSubmit === 'function') {
                event.preventDefault();
                form.dataset.formPreparing = 'true';

                const beforeSubmitResult = await Promise.resolve(form.codexBeforeSubmit(event));
                delete form.dataset.formPreparing;

                if (beforeSubmitResult === false) {
                    return;
                }

                form.dataset.skipBeforeSubmit = 'true';
                form.requestSubmit();
                return;
            }

            form.dataset.skipBeforeSubmit = 'false';
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
});
