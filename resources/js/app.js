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

        const priceInput = productForm.querySelector('[data-live-price]');
        const stockInput = productForm.querySelector('[data-live-stock]');
        const priceOutputs = productForm.querySelectorAll('[data-live-price-output], [data-live-price-output-sidebar]');
        const stockOutputs = productForm.querySelectorAll('[data-live-stock-output], [data-live-stock-output-sidebar]');
        const previewNameInput = productForm.querySelector('[data-preview-name]');
        const previewCategoryInput = productForm.querySelector('[data-preview-category]');
        const previewDescriptionInput = productForm.querySelector('[data-preview-description]');
        const previewNameOutput = productForm.querySelector('[data-preview-name-output]');
        const previewCategoryOutput = productForm.querySelector('[data-preview-category-output]');
        const previewDescriptionOutput = productForm.querySelector('[data-preview-description-output]');

        const refreshProductPreview = () => {
            const currentPrice = priceInput?.value ?? '';
            const currentStock = stockInput?.value ?? '';
            const currentName = previewNameInput?.value?.trim() || 'Nombre del producto';
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

            if (previewCategoryOutput) {
                previewCategoryOutput.textContent = currentCategory;
            }

            if (previewDescriptionOutput) {
                previewDescriptionOutput.textContent = currentDescription.slice(0, 140);
            }
        };

        [priceInput, stockInput, previewNameInput, previewCategoryInput, previewDescriptionInput]
            .filter(Boolean)
            .forEach((field) => {
                field.addEventListener('input', refreshProductPreview);
                field.addEventListener('change', refreshProductPreview);
            });

        refreshProductPreview();

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
});
