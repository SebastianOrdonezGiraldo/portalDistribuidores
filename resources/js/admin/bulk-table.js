/**
 * Select-all and bulk action bar for admin tables (data-bulk-table).
 */
export function initBulkTable() {
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
}
