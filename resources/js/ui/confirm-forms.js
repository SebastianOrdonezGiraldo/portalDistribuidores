import { closeModal, openModal } from '../lib/modal';

/**
 * Confirmation modal for form[data-confirm] and loading state for form[data-loading-form].
 */
export function initConfirmAndLoadingForms() {
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
}
