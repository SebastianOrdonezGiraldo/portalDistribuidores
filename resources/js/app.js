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
    const prefersReducedMotion = typeof window.matchMedia === 'function'
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const animateCartBadges = (withCountFlip = false) => {
        document.querySelectorAll('[data-cart-badge]').forEach((badge) => {
            badge.classList.remove('animate-cart-bump', 'animate-cart-glow', 'animate-cart-count-flip');
            void badge.offsetWidth;
            badge.classList.add('animate-cart-bump', 'animate-cart-glow');
            if (withCountFlip) {
                badge.classList.add('animate-cart-count-flip');
            }

            window.setTimeout(() => {
                badge.classList.remove('animate-cart-bump', 'animate-cart-glow', 'animate-cart-count-flip');
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

        const icons = {
            success: `<svg class="h-4 w-4 mt-0.5 shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>`,
            error: `<svg class="h-4 w-4 mt-0.5 shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
            info: `<svg class="h-4 w-4 mt-0.5 shrink-0 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
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

    // Cart animation helpers
    const getCartBadgeTarget = () => {
        const badges = Array.from(document.querySelectorAll('[data-cart-badge]'));
        const visible = badges.find((badge) => {
            const rect = badge.getBoundingClientRect();
            return rect.width > 0 && rect.top >= 0 && rect.top <= window.innerHeight;
        });
        return visible || badges[0] || null;
    };

    const getProductNameFromForm = (form) => {
        const explicit = form.dataset.productName?.trim();
        if (explicit) {
            return explicit;
        }

        const heading = form.closest('article')?.querySelector('h3 a, h3');
        if (heading?.textContent?.trim()) {
            return heading.textContent.trim();
        }

        return 'Producto';
    };

    const pulseCartButton = (button) => {
        if (!button || prefersReducedMotion) {
            return;
        }

        button.classList.remove('animate-cart-button-pop');
        void button.offsetWidth;
        button.classList.add('animate-cart-button-pop');
    };

    const shakeCartButton = (button) => {
        if (!button || prefersReducedMotion) {
            return;
        }

        button.classList.remove('animate-cart-button-shake');
        void button.offsetWidth;
        button.classList.add('animate-cart-button-shake');
    };

    const pulseProductCard = (fromEl) => {
        if (prefersReducedMotion) {
            return;
        }

        const card = fromEl?.closest('article');
        if (!card) {
            return;
        }

        card.classList.remove('cart-card-highlight');
        void card.offsetWidth;
        card.classList.add('cart-card-highlight');

        window.setTimeout(() => {
            card.classList.remove('cart-card-highlight');
        }, 560);
    };

    const spawnBadgeRing = (toEl) => {
        if (!toEl || prefersReducedMotion) {
            return;
        }

        const to = toEl.getBoundingClientRect();
        const ring = document.createElement('span');
        ring.className = 'cart-target-ring';
        ring.setAttribute('aria-hidden', 'true');
        ring.style.left = `${to.left + to.width / 2 - 16}px`;
        ring.style.top = `${to.top + to.height / 2 - 16}px`;
        document.body.append(ring);
        window.setTimeout(() => ring.remove(), 520);
    };

    const launchParticle = (fromX, fromY, toX, toY, options = {}) => {
        const size = options.size ?? 10;
        const duration = options.duration ?? 560;
        const delay = options.delay ?? 0;
        const arcHeight = options.arcHeight ?? 72;
        const startOpacity = options.opacity ?? 1;

        const particle = document.createElement('span');
        particle.className = 'cart-fly-dot';
        particle.setAttribute('aria-hidden', 'true');
        particle.style.left = `${fromX - size / 2}px`;
        particle.style.top = `${fromY - size / 2}px`;
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.opacity = String(startOpacity);
        document.body.append(particle);

        const deltaX = toX - fromX;
        const deltaY = toY - fromY;

        if (typeof particle.animate === 'function') {
            const animation = particle.animate(
                [
                    { transform: 'translate3d(0, 0, 0) scale(1)', opacity: startOpacity },
                    {
                        transform: `translate3d(${deltaX * 0.56}px, ${deltaY * 0.5 - arcHeight}px, 0) scale(0.86)`,
                        opacity: Math.max(0.65, startOpacity - 0.1),
                        offset: 0.58,
                    },
                    { transform: `translate3d(${deltaX}px, ${deltaY}px, 0) scale(0.24)`, opacity: 0 },
                ],
                {
                    duration,
                    delay,
                    easing: 'cubic-bezier(0.18, 0.88, 0.22, 1)',
                    fill: 'forwards',
                },
            );

            animation.onfinish = () => particle.remove();
            return;
        }

        Object.assign(particle.style, {
            transition: `transform ${duration}ms ease, opacity ${duration}ms ease`,
            transform: `translate3d(${deltaX}px, ${deltaY}px, 0) scale(0.24)`,
            opacity: '0',
        });
        window.setTimeout(() => particle.remove(), duration + delay + 80);
    };

    const flyParticle = (fromEl, toEl) => {
        if (!fromEl || !toEl) {
            return;
        }

        if (prefersReducedMotion) {
            animateCartBadges(true);
            return;
        }

        const from = fromEl.getBoundingClientRect();
        const to = toEl.getBoundingClientRect();

        const fromX = from.left + from.width / 2;
        const fromY = from.top + from.height / 2;
        const toX = to.left + to.width / 2;
        const toY = to.top + to.height / 2;

        launchParticle(fromX, fromY, toX, toY, {
            size: 10,
            duration: 610,
            delay: 0,
            arcHeight: 86,
            opacity: 1,
        });
        launchParticle(fromX, fromY, toX, toY, {
            size: 7,
            duration: 560,
            delay: 40,
            arcHeight: 62,
            opacity: 0.92,
        });
        launchParticle(fromX, fromY, toX, toY, {
            size: 6,
            duration: 520,
            delay: 90,
            arcHeight: 46,
            opacity: 0.78,
        });

        window.setTimeout(() => {
            spawnBadgeRing(toEl);
        }, 360);
    };

    const makeSpinnerSvg = (extraClass = '') =>
        `<svg class="h-4 w-4 btn-spinning${extraClass ? ' ' + extraClass : ''}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.568 3 7.212l3-2.921z"></path></svg>`;

    const makeCheckSvg = () =>
        `<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

    const makeCrossSvg = () =>
        `<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;

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

        const isIconButton = submitButton.textContent.trim() === '';
        const originalHtml = submitButton.innerHTML;
        const loadingLabel = submitButton.dataset.loadingLabel || 'Agregando...';

        const setLoading = () => {
            submitButton.disabled = true;
            submitButton.style.transition = 'background-color 150ms ease, border-color 150ms ease';
            if (isIconButton) {
                submitButton.innerHTML = makeSpinnerSvg();
            } else {
                submitButton.innerHTML = `${makeSpinnerSvg('shrink-0')} ${loadingLabel}`;
            }
        };

        const setSuccess = () => {
            if (isIconButton) {
                submitButton.innerHTML = makeCheckSvg();
            } else {
                submitButton.innerHTML = `${makeCheckSvg()} Agregado`;
            }
            submitButton.classList.add('!bg-emerald-500', '!border-emerald-500');
        };

        const setError = () => {
            if (isIconButton) {
                submitButton.innerHTML = makeCrossSvg();
            } else {
                submitButton.innerHTML = `${makeCrossSvg()} Error`;
            }
            submitButton.classList.add('!bg-red-500', '!border-red-500');
            shakeCartButton(submitButton);
        };

        const resetButton = () => {
            submitButton.innerHTML = originalHtml;
            submitButton.classList.remove(
                '!bg-emerald-500', '!border-emerald-500',
                '!bg-red-500', '!border-red-500',
                'animate-cart-button-pop', 'animate-cart-button-shake',
            );
            submitButton.disabled = false;
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (submitButton.disabled) {
                return;
            }

            setLoading();

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

                // Update badge count and color
                const count = Number(payload.cart_count || 0);
                document.querySelectorAll('[data-cart-badge]').forEach((badge) => {
                    badge.textContent = String(count);
                    badge.classList.remove('border-slate-200', 'bg-slate-100', 'text-slate-700');
                    badge.classList.add('bg-brand-primary/10', 'text-brand-dark');
                });

                // Show success state on button
                setSuccess();
                pulseCartButton(submitButton);
                pulseProductCard(submitButton);

                // Launch particle from button toward cart badge
                const badgeTarget = getCartBadgeTarget();
                window.setTimeout(() => flyParticle(submitButton, badgeTarget), 70);

                // Animate badge when particle lands
                window.setTimeout(() => animateCartBadges(true), 430);

                const productName = getProductNameFromForm(form);
                const shortName = productName.length > 52 ? `${productName.slice(0, 49)}...` : productName;
                const fallbackMessage = `${shortName} agregado al carrito.`;
                const successMessage = payload.message && payload.message !== 'Producto agregado al carrito.'
                    ? payload.message
                    : fallbackMessage;

                showInlineToast(successMessage, 'success');

                // Reset button after success display
                window.setTimeout(resetButton, 1300);

            } catch (error) {
                setError();
                window.setTimeout(resetButton, 1200);
                showInlineToast(error.message || 'No se pudo agregar al carrito.', 'error');
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

    const infiniteGrid = document.querySelector('[data-infinite-grid]');
    const infiniteSentinel = document.querySelector('[data-infinite-sentinel]');
    const infiniteEnd = document.querySelector('[data-infinite-end]');

    if (infiniteGrid && infiniteSentinel) {
        let loading = false;

        const loadMore = async () => {
            if (loading) {
                return;
            }

            const hasMore = infiniteGrid.dataset.hasMore === 'true';

            if (!hasMore) {
                infiniteSentinel.classList.add('hidden');
                infiniteEnd?.classList.remove('hidden');
                observer.disconnect();
                return;
            }

            loading = true;
            infiniteSentinel.classList.remove('hidden');

            const nextPage = infiniteGrid.dataset.nextPage;
            const baseUrl = infiniteGrid.dataset.loadUrl;
            const filters = infiniteGrid.dataset.filters || '';

            const params = new URLSearchParams(filters);
            params.set('page', nextPage);

            try {
                const response = await fetch(`${baseUrl}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('catalog-load-failed');
                }

                const payload = await response.json();

                infiniteGrid.insertAdjacentHTML('beforeend', payload.html);
                infiniteGrid.dataset.nextPage = String(payload.nextPage);
                infiniteGrid.dataset.hasMore = payload.hasMore ? 'true' : 'false';

                if (!payload.hasMore) {
                    infiniteSentinel.classList.add('hidden');
                    infiniteEnd?.classList.remove('hidden');
                    observer.disconnect();
                }
            } catch {
                // Do nothing on error; the user can scroll down again to retry
            } finally {
                loading = false;
            }
        };

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    loadMore();
                }
            },
            { rootMargin: '200px' },
        );

        if (infiniteGrid.dataset.hasMore === 'true') {
            observer.observe(infiniteSentinel);
        } else {
            infiniteSentinel.classList.add('hidden');
            infiniteEnd?.classList.remove('hidden');
        }
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
