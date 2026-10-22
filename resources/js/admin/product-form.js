/**
 * Admin product editor: live price/stock previews, variants and unsaved-changes guard.
 */
export function initProductForm({ showInlineToast }) {
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

        const goldPriceInput = productForm.querySelector('[data-live-gold-price]');
        const silverPriceInput = productForm.querySelector('[data-live-silver-price]');
        const stockInput = productForm.querySelector('[data-live-stock]');
        const goldPriceOutputs = productForm.querySelectorAll('[data-live-gold-price-output], [data-live-gold-price-output-sidebar]');
        const silverPriceOutputs = productForm.querySelectorAll('[data-live-silver-price-output], [data-live-silver-price-output-sidebar]');
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
            const currentGoldPrice = goldPriceInput?.value ?? '';
            const currentSilverPrice = silverPriceInput?.value ?? '';
            const currentStock = stockInput?.value ?? '';
            const currentName = previewNameInput?.value?.trim() || 'Nombre del producto';
            const currentBrand = previewBrandInput?.value?.trim() || 'Sin marca';
            const categoryOption = previewCategoryInput?.selectedOptions?.[0];
            const currentCategory = categoryOption?.textContent?.trim() || 'Sin categoría seleccionada';
            const currentDescription = previewDescriptionInput?.value?.trim() || 'Sin descripción comercial';

            goldPriceOutputs.forEach((output) => {
                output.textContent = formatMoney(currentGoldPrice);
            });

            silverPriceOutputs.forEach((output) => {
                output.textContent = formatMoney(currentSilverPrice);
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

        [goldPriceInput, silverPriceInput, stockInput, previewNameInput, previewBrandInput, previewCategoryInput, previewDescriptionInput]
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
            [goldPriceInput, silverPriceInput].forEach((input) => {
                if (input instanceof HTMLInputElement) {
                    input.required = !enabled;
                }
            });

            if (!variantRowsContainer) {
                return;
            }

            variantRowsContainer
                .querySelectorAll('input[name$="[value]"], input[name$="[price]"], input[name$="[silver_price]"]')
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
}
