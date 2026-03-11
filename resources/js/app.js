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

    const cartSuccessToast = document.querySelector('[data-toast][data-cart-success="true"]');
    const animateCartBadges = () => {
        document.querySelectorAll('[data-cart-badge]').forEach((badge) => {
            badge.classList.remove('animate-cart-bump', 'animate-cart-glow');
            void badge.offsetWidth;
            badge.classList.add('animate-cart-bump', 'animate-cart-glow');

            window.setTimeout(() => {
                badge.classList.remove('animate-cart-bump', 'animate-cart-glow');
            }, 750);
        });
    };

    const showInlineToast = (message, variant = 'success') => {
        let container = document.querySelector('[data-inline-toast-stack]');

        if (!container) {
            container = document.createElement('div');
            container.dataset.inlineToastStack = 'true';
            container.className = 'pointer-events-none fixed right-4 top-4 z-[85] w-[min(92vw,22rem)] space-y-2';
            document.body.append(container);
        }

        const palette = {
            success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
            error: 'border-red-200 bg-red-50 text-red-900',
            info: 'border-sky-200 bg-sky-50 text-sky-900',
        };

        const toast = document.createElement('div');
        toast.className = `pointer-events-auto toast ${palette[variant] || palette.info} inline-toast-enter`;
        toast.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-semibold">${message}</p>
                <button type="button" class="btn btn-ghost !h-7 !px-2 !py-0 text-xs" data-inline-toast-close>Cerrar</button>
            </div>
        `;

        container.append(toast);

        const close = () => {
            toast.classList.remove('inline-toast-enter');
            toast.classList.add('inline-toast-leave');
            window.setTimeout(() => toast.remove(), 220);
        };

        toast.querySelector('[data-inline-toast-close]')?.addEventListener('click', close);
        window.setTimeout(close, 2400);
    };

    if (cartSuccessToast) {
        animateCartBadges();
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
        form.addEventListener('submit', (event) => {
            if (form.dataset.ajaxCart === 'true') {
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

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (submitButton.disabled) {
                return;
            }

            submitButton.classList.add('cart-submit-feedback');
            submitButton.disabled = true;

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

                if (!response.ok) {
                    throw new Error(payload.message || 'No se pudo agregar al carrito.');
                }

                const count = Number(payload.cart_count || 0);
                document.querySelectorAll('[data-cart-badge]').forEach((badge) => {
                    badge.textContent = String(count);
                    badge.classList.remove('border-slate-200', 'bg-slate-100', 'text-slate-700');
                    badge.classList.add('border-brand-primary/30', 'bg-brand-primary/10', 'text-[#15565c]');
                });

                animateCartBadges();
                showInlineToast(payload.message || 'Producto agregado al carrito.', 'success');
            } catch (error) {
                showInlineToast(error.message || 'No se pudo agregar al carrito.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.classList.remove('cart-submit-feedback');
            }
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
        const previewBrandInput = productForm.querySelector('[data-preview-brand]');
        const previewCategoryInput = productForm.querySelector('[data-preview-category]');
        const previewDescriptionInput = productForm.querySelector('[data-preview-description]');
        const previewNameOutput = productForm.querySelector('[data-preview-name-output]');
        const previewBrandOutput = productForm.querySelector('[data-preview-brand-output]');
        const previewCategoryOutput = productForm.querySelector('[data-preview-category-output]');
        const previewDescriptionOutput = productForm.querySelector('[data-preview-description-output]');

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

        const variantToggle = productForm.querySelector('input[name="has_variants"][value="1"]');
        const variantSection = productForm.querySelector('[data-variant-section]');
        const variantRowsContainer = productForm.querySelector('[data-variant-rows]');
        const variantTemplate = productForm.querySelector('[data-variant-template]');
        const addVariantRowButton = productForm.querySelector('[data-variant-add-row]');
        const setVariantRequiredRules = (enabled) => {
            if (priceInput instanceof HTMLInputElement) {
                priceInput.required = !enabled;
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
                row.querySelectorAll('input').forEach((input) => {
                    input.value = '';
                });
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
});
