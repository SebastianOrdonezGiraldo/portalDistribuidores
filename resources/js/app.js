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

    const showSidebar = () => {
        sidebar?.classList.remove('-translate-x-full');
        sidebarOverlay?.classList.remove('hidden');
    };

    const hideSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        sidebarOverlay?.classList.add('hidden');
    };

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', showSidebar);
    });

    document.querySelectorAll('[data-sidebar-close], [data-sidebar-overlay]').forEach((button) => {
        button.addEventListener('click', hideSidebar);
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

        pendingForm.dataset.confirmed = 'true';
        pendingForm.requestSubmit();
        pendingForm = null;
        closeModal(confirmModal);
    });

    confirmCancel?.addEventListener('click', () => {
        pendingForm = null;
        closeModal(confirmModal);
    });

    confirmModal?.addEventListener('click', (event) => {
        if (event.target === confirmModal) {
            pendingForm = null;
            closeModal(confirmModal);
        }
    });

    document.querySelectorAll('form[data-loading-form]').forEach((form) => {
        form.addEventListener('submit', () => {
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

    const bulkTable = document.querySelector('[data-bulk-table]');

    if (bulkTable) {
        const master = bulkTable.querySelector('[data-bulk-master]');
        const rows = () => Array.from(bulkTable.querySelectorAll('[data-bulk-row]'));
        const countEl = document.querySelector('[data-bulk-count]');
        const copyButton = document.querySelector('[data-bulk-copy]');

        const refreshBulk = () => {
            const selected = rows().filter((row) => row.checked).map((row) => row.value);

            if (countEl) {
                countEl.textContent = String(selected.length);
            }

            if (copyButton) {
                copyButton.disabled = selected.length === 0;
                copyButton.dataset.codes = selected.join(', ');
            }

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

        refreshBulk();
    }
});
