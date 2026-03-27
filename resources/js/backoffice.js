const showInlineToast = (message, variant = 'success') => {
    window.PortalUI?.showInlineToast?.(message, variant);
};

document.addEventListener('DOMContentLoaded', () => {
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
            } catch {
                copyButton.textContent = 'No disponible';
            }
        });

        refreshBulk();
    }

    const productForm = document.querySelector('form[data-product-form]');

    if (!productForm) {
        return;
    }

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
    const mediaSection = document.getElementById('media-documentos');
    const uploadFeedback = productForm.querySelector('[data-upload-feedback]');
    const uploadFeedbackMessage = productForm.querySelector('[data-upload-feedback-message]');
    const photosInput = productForm.querySelector('#photos');
    const techSheetInput = productForm.querySelector('#tech_sheet');
    const generatedVariantsInput = productForm.querySelector('#generated_photo_variants');
    const generatedVariantsManifestInput = productForm.querySelector('#generated_photo_variants_manifest');
    const totalMaxKb = Number(productForm.dataset.totalMaxKb || 0);
    const totalMaxText = productForm.dataset.totalMaxText || `${totalMaxKb / 1024} MB`;
    let generatedVariantSignature = '';

    const resetGeneratedVariants = () => {
        if (generatedVariantsInput) {
            const transfer = new DataTransfer();
            generatedVariantsInput.files = transfer.files;
        }

        if (generatedVariantsManifestInput) {
            generatedVariantsManifestInput.value = '';
        }

        generatedVariantSignature = '';
    };

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
        const techSheetFiles = techSheetInput?.files ? Array.from(techSheetInput.files) : [];
        const photoMaxFiles = Number(photosInput?.dataset.maxFiles || 0);
        const photoMaxSizeKb = Number(photosInput?.dataset.maxSizeKb || 0);
        const photoMaxSizeText = photosInput?.dataset.maxSizeText || `${photoMaxSizeKb / 1024} MB`;
        const photoLabel = photosInput?.dataset.uploadLabel || 'fotos del producto';
        const techSheetMaxSizeKb = Number(techSheetInput?.dataset.maxSizeKb || 0);
        const techSheetMaxSizeText = techSheetInput?.dataset.maxSizeText || `${techSheetMaxSizeKb / 1024} MB`;
        const techSheetLabel = techSheetInput?.dataset.uploadLabel || 'ficha tecnica';

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

        const oversizedTechSheet = techSheetFiles.find((file) => file.size > techSheetMaxSizeKb * 1024);
        if (oversizedTechSheet) {
            showUploadFeedback(
                `La ${techSheetLabel} supera el maximo permitido de ${techSheetMaxSizeText}. Reduce el PDF antes de guardarlo.`,
                techSheetInput,
                { notify },
            );
            return false;
        }

        const totalSelectedBytes = [...photoFiles, ...techSheetFiles]
            .reduce((sum, file) => sum + (Number.isFinite(file.size) ? file.size : 0), 0);

        if (totalMaxKb > 0 && totalSelectedBytes > totalMaxKb * 1024) {
            showUploadFeedback(
                `La carga actual pesa ${formatFileSize(totalSelectedBytes)} y el formulario permite hasta ${totalMaxText} en total. Reduce la cantidad o el peso de fotos y documentos.`,
                photosInput || techSheetInput,
                { notify },
            );
            return false;
        }

        return true;
    };

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

    const loadImageBitmap = async (file) => {
        if (typeof window.createImageBitmap === 'function') {
            return window.createImageBitmap(file);
        }

        return new Promise((resolve, reject) => {
            const image = new Image();
            const objectUrl = URL.createObjectURL(file);
            image.onload = () => {
                URL.revokeObjectURL(objectUrl);
                resolve(image);
            };
            image.onerror = () => {
                URL.revokeObjectURL(objectUrl);
                reject(new Error('image-load-failed'));
            };
            image.src = objectUrl;
        });
    };

    const makeVariantWidths = (originalWidth) => {
        const candidates = [320, 640, 1080].filter((width) => width < originalWidth);

        if (originalWidth <= 1080) {
            candidates.push(originalWidth);
        }

        return [...new Set(candidates)].sort((left, right) => left - right);
    };

    const buildGeneratedVariants = async ({ notify = false } = {}) => {
        const photoFiles = photosInput?.files ? Array.from(photosInput.files) : [];
        if (!generatedVariantsInput || !generatedVariantsManifestInput || photoFiles.length === 0 || typeof DataTransfer === 'undefined') {
            resetGeneratedVariants();
            return true;
        }

        const signature = photoFiles.map((file) => `${file.name}:${file.size}:${file.lastModified}`).join('|');

        if (signature !== '' && signature === generatedVariantSignature && generatedVariantsManifestInput.value !== '') {
            return true;
        }

        const transfer = new DataTransfer();
        const manifest = [];

        try {
            for (const [index, file] of photoFiles.entries()) {
                const bitmap = await loadImageBitmap(file);
                const originalWidth = bitmap.width;
                const originalHeight = bitmap.height;
                const variantWidths = makeVariantWidths(originalWidth);
                const variants = [];

                for (const width of variantWidths) {
                    const ratio = width / originalWidth;
                    const height = Math.max(1, Math.round(originalHeight * ratio));
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const context = canvas.getContext('2d', { alpha: true });

                    if (!context) {
                        continue;
                    }

                    context.drawImage(bitmap, 0, 0, width, height);

                    const blob = await new Promise((resolve) => {
                        canvas.toBlob(resolve, 'image/webp', 0.78);
                    });

                    if (!blob) {
                        continue;
                    }

                    const generatedFile = new File([blob], `generated-photo-${index}-${width}.webp`, {
                        type: 'image/webp',
                        lastModified: file.lastModified,
                    });

                    transfer.items.add(generatedFile);
                    variants.push({
                        file: generatedFile.name,
                        width,
                        height,
                    });
                }

                if (typeof bitmap.close === 'function') {
                    bitmap.close();
                }

                manifest.push({
                    source_index: index,
                    original: {
                        width: originalWidth,
                        height: originalHeight,
                    },
                    variants,
                });
            }
        } catch {
            resetGeneratedVariants();
            if (notify) {
                showInlineToast('No se pudieron preparar las versiones optimizadas de las fotos. Se guardará el original.', 'info');
            }
            return true;
        }

        const techSheetFiles = techSheetInput?.files ? Array.from(techSheetInput.files) : [];
        const generatedFiles = Array.from(transfer.files);
        const totalSelectedBytes = [...photoFiles, ...techSheetFiles, ...generatedFiles]
            .reduce((sum, file) => sum + (Number.isFinite(file.size) ? file.size : 0), 0);

        if (totalMaxKb > 0 && totalSelectedBytes > totalMaxKb * 1024) {
            resetGeneratedVariants();

            if (notify) {
                showInlineToast(
                    `Las variantes optimizadas exceden el limite total de ${totalMaxText}. Se guardarán solo los originales.`,
                    'info',
                );
            }

            return true;
        }

        generatedVariantsInput.files = transfer.files;
        generatedVariantsManifestInput.value = JSON.stringify(manifest);
        generatedVariantSignature = signature;

        return true;
    };

    refreshProductPreview();
    productForm.codexBeforeSubmit = async () => {
        if (!validateProductUploads({ notify: true })) {
            return false;
        }

        return buildGeneratedVariants({ notify: true });
    };

    photosInput?.addEventListener('change', () => {
        resetGeneratedVariants();
        validateProductUploads();
    });

    techSheetInput?.addEventListener('change', () => {
        validateProductUploads();
    });

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

        variantRowsContainer.querySelectorAll('input[name$="[value]"], input[name$="[price]"]').forEach((input) => {
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

        variantRowsContainer.insertAdjacentHTML('beforeend', rawHtml.replaceAll('__INDEX__', String(variantIndex)));
        variantIndex += 1;

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
});
