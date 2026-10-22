/**
 * Native <dialog> backdrop close and dismissible flash toasts.
 */
export function initDialogsAndToasts() {
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
}
