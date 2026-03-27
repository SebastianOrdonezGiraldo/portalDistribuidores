const showInlineToast = (message, variant = 'success') => {
    window.PortalUI?.showInlineToast?.(message, variant);
};

document.addEventListener('DOMContentLoaded', () => {
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

    const isVisibleCartTarget = (element) => {
        if (!element) {
            return false;
        }

        const rect = element.getBoundingClientRect();

        return rect.width > 0 && rect.height > 0 && rect.top >= 0 && rect.top <= window.innerHeight;
    };

    const getCartBadgeTarget = () => {
        const badges = Array.from(document.querySelectorAll('[data-cart-badge]'));
        const visibleBadge = badges.find((badge) => isVisibleCartTarget(badge));

        if (visibleBadge) {
            return visibleBadge;
        }

        const targets = Array.from(document.querySelectorAll('[data-cart-target]'));
        const visibleTarget = targets.find((target) => isVisibleCartTarget(target));

        return visibleTarget || badges[0] || targets[0] || null;
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

        launchParticle(fromX, fromY, toX, toY, { size: 10, duration: 610, arcHeight: 86, opacity: 1 });
        launchParticle(fromX, fromY, toX, toY, { size: 7, duration: 560, delay: 40, arcHeight: 62, opacity: 0.92 });
        launchParticle(fromX, fromY, toX, toY, { size: 6, duration: 520, delay: 90, arcHeight: 46, opacity: 0.78 });

        window.setTimeout(() => {
            spawnBadgeRing(toEl);
        }, 360);
    };

    const makeSpinnerSvg = (extraClass = '') =>
        `<svg class="h-4 w-4 btn-spinning${extraClass ? ` ${extraClass}` : ''}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.568 3 7.212l3-2.921z"></path></svg>`;

    const makeCheckSvg = () =>
        '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>';

    const makeCrossSvg = () =>
        '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';

    if (cartSuccessToast) {
        animateCartBadges();
    }

    const bindAjaxCartForms = (root = document) => {
        root.querySelectorAll('form[action*="/cart"]').forEach((form) => {
            if (form.dataset.ajaxCartBound === 'true') {
                return;
            }

            const hasProductInput = !!form.querySelector('input[name="product_id"]');
            const submitButton = form.querySelector('button[type="submit"]');

            if (!hasProductInput || !submitButton) {
                return;
            }

            form.dataset.ajaxCart = 'true';
            form.dataset.ajaxCartBound = 'true';

            const isIconButton = submitButton.textContent.trim() === '';
            const originalHtml = submitButton.innerHTML;
            const loadingLabel = submitButton.dataset.loadingLabel || 'Agregando...';

            const setLoading = () => {
                submitButton.disabled = true;
                submitButton.style.transition = 'background-color 150ms ease, border-color 150ms ease';
                submitButton.innerHTML = isIconButton
                    ? makeSpinnerSvg()
                    : `${makeSpinnerSvg('shrink-0')} ${loadingLabel}`;
            };

            const setSuccess = () => {
                submitButton.innerHTML = isIconButton
                    ? makeCheckSvg()
                    : `${makeCheckSvg()} Agregado`;
                submitButton.classList.add('!bg-emerald-500', '!border-emerald-500');
            };

            const setError = () => {
                submitButton.innerHTML = isIconButton
                    ? makeCrossSvg()
                    : `${makeCrossSvg()} Error`;
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
                            Accept: 'application/json',
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
                        badge.classList.add('bg-brand-primary/10', 'text-brand-dark');
                    });

                    setSuccess();
                    pulseCartButton(submitButton);
                    pulseProductCard(submitButton);

                    const badgeTarget = getCartBadgeTarget();
                    window.setTimeout(() => flyParticle(submitButton, badgeTarget), 70);
                    window.setTimeout(() => animateCartBadges(true), 430);

                    const productName = getProductNameFromForm(form);
                    const shortName = productName.length > 52 ? `${productName.slice(0, 49)}...` : productName;
                    const fallbackMessage = `${shortName} agregado al carrito.`;
                    const successMessage = payload.message && payload.message !== 'Producto agregado al carrito.'
                        ? payload.message
                        : fallbackMessage;

                    showInlineToast(successMessage, 'success');
                    window.setTimeout(resetButton, 1300);
                } catch (error) {
                    setError();
                    window.setTimeout(resetButton, 1200);
                    showInlineToast(error.message || 'No se pudo agregar al carrito.', 'error');
                }
            });
        });
    };

    bindAjaxCartForms();

    document.querySelectorAll('[data-product-list]').forEach((productList) => {
        let loading = false;

        const setButtonLoading = (isLoading) => {
            const button = productList.querySelector('[data-product-load-more]');
            if (!button) {
                return;
            }

            if (isLoading) {
                button.disabled = true;
                button.dataset.originalLabel = button.textContent.trim();
                button.textContent = button.dataset.loadingLabel || 'Cargando...';
                return;
            }

            button.disabled = false;
            if (button.dataset.originalLabel) {
                button.textContent = button.dataset.originalLabel;
            }
        };

        const loadMore = async () => {
            if (loading || productList.dataset.hasMore !== 'true') {
                return;
            }

            loading = true;
            setButtonLoading(true);

            const params = new URLSearchParams(productList.dataset.baseQuery || '');
            const pageParam = productList.dataset.pageParam || 'page';
            params.set(pageParam, productList.dataset.nextPage || '1');

            const listName = productList.dataset.list || '';
            if (listName) {
                params.set('list', listName);
            }

            try {
                const response = await fetch(`${productList.dataset.loadUrl}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('product-list-load-failed');
                }

                const payload = await response.json();
                const grid = productList.querySelector('[data-product-grid]');
                const controls = productList.querySelector('[data-product-list-controls]');

                grid?.insertAdjacentHTML('beforeend', payload.html || '');
                bindAjaxCartForms(grid || productList);

                if (controls) {
                    controls.innerHTML = payload.controlsHtml || '';
                }

                productList.dataset.currentPage = String(payload.currentPage || productList.dataset.currentPage || '1');
                productList.dataset.nextPage = String(payload.nextPage || productList.dataset.nextPage || '1');
                productList.dataset.hasMore = payload.hasMore ? 'true' : 'false';

                if (payload.pushUrl && typeof window.history?.pushState === 'function') {
                    window.history.pushState({}, '', payload.pushUrl);
                }
            } catch {
                // Keep the current controls so the user can retry manually.
            } finally {
                loading = false;
                setButtonLoading(false);
            }
        };

        productList.addEventListener('click', (event) => {
            const button = event.target.closest('[data-product-load-more]');
            if (!button || !productList.contains(button)) {
                return;
            }

            event.preventDefault();
            loadMore();
        });
    });

    const galleryThumbs = Array.from(document.querySelectorAll('[data-product-thumb]'));
    const mainImage = document.querySelector('[data-product-main-image]');
    const mainSource = document.querySelector('[data-product-main-source]');

    galleryThumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            if (mainSource) {
                mainSource.srcset = thumb.dataset.srcset || '';
            }

            if (mainImage) {
                mainImage.src = thumb.dataset.src || mainImage.src;
                mainImage.alt = thumb.dataset.alt || mainImage.alt;
                if (thumb.dataset.width) {
                    mainImage.width = Number(thumb.dataset.width);
                }
                if (thumb.dataset.height) {
                    mainImage.height = Number(thumb.dataset.height);
                }
            }

            galleryThumbs.forEach((item) => {
                item.classList.remove('border-brand-primary', 'shadow-sm');
                item.classList.add('border-slate-200');
            });

            thumb.classList.remove('border-slate-200');
            thumb.classList.add('border-brand-primary', 'shadow-sm');
        });
    });

    const variantSelect = document.querySelector('[data-variant-select]');
    const priceTarget = document.querySelector('[data-variant-price-target]');
    const mobilePriceTarget = document.querySelector('[data-variant-mobile-price-target]');
    const stockTarget = document.querySelector('[data-variant-stock-target]');
    const unitLabel = variantSelect?.dataset.unitLabel || 'unidad';

    const formatMoney = (value) => `$${Number(value).toLocaleString('es-CO', { maximumFractionDigits: 0 })}`;
    const formatStock = (value) => {
        const parsed = Number(value);
        if (!Number.isFinite(parsed)) {
            return 'Stock a confirmar';
        }

        return `${parsed.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ${unitLabel}`;
    };

    const refreshVariantSummary = () => {
        if (!variantSelect) {
            return;
        }

        const selected = variantSelect.selectedOptions[0];
        const hasSelection = selected && selected.value;

        if (!hasSelection) {
            if (priceTarget) {
                priceTarget.textContent = priceTarget.dataset.defaultValue || '';
            }
            if (mobilePriceTarget) {
                mobilePriceTarget.textContent = mobilePriceTarget.dataset.defaultValue || '';
            }
            if (stockTarget) {
                stockTarget.textContent = stockTarget.dataset.defaultValue || '';
            }
            return;
        }

        const price = Number(selected.dataset.price || 0);
        const stock = selected.dataset.stock;
        const stockText = stock === '' || stock === undefined ? 'Stock a confirmar' : formatStock(stock);
        const formattedPrice = formatMoney(price);

        if (priceTarget) {
            priceTarget.textContent = formattedPrice;
        }
        if (mobilePriceTarget) {
            mobilePriceTarget.textContent = formattedPrice;
        }
        if (stockTarget) {
            stockTarget.textContent = stockText;
        }
    };

    variantSelect?.addEventListener('change', refreshVariantSummary);
    refreshVariantSummary();

    const qtyRoots = Array.from(document.querySelectorAll('[data-qty-control]'));
    const sharedInputs = Array.from(document.querySelectorAll('[data-shared-qty]'));

    const parseValue = (rawValue, fallback = 1) => {
        const normalized = String(rawValue ?? '').replace(',', '.');
        const value = Number(normalized);
        return Number.isFinite(value) ? value : fallback;
    };

    const formatQtyVal = (value) => String(Math.max(1, Math.round(value)));

    const normalizeQty = (value, minValue, multiple) => {
        if (!Number.isFinite(value) || value <= 0) {
            return minValue;
        }

        const base = Math.max(minValue, value);
        return Math.ceil(base / multiple) * multiple;
    };

    const syncQtyInputs = (value) => {
        sharedInputs.forEach((input) => {
            input.value = formatQtyVal(value);
        });
    };

    const applyQty = (requestedValue, root) => {
        const input = root.querySelector('[data-qty-input]');
        if (!input || input.disabled) {
            return;
        }

        const multiple = Math.max(1, Math.round(parseValue(root.dataset.minMultiple, 1)));
        const minValue = Math.max(multiple, parseValue(input.min || multiple, multiple));
        syncQtyInputs(normalizeQty(requestedValue, minValue, multiple));
    };

    qtyRoots.forEach((root) => {
        const input = root.querySelector('[data-qty-input]');
        if (!input || input.disabled) {
            return;
        }

        root.querySelectorAll('[data-qty-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const currentValue = parseValue(input.value, parseValue(input.min, 1));
                const direction = Number(button.dataset.qtyStep || 0);
                const multiple = Math.max(1, Math.round(parseValue(root.dataset.minMultiple, 1)));
                applyQty(currentValue + (direction * multiple), root);
            });
        });

        input.addEventListener('change', () => applyQty(parseValue(input.value, parseValue(input.min, 1)), root));
        input.addEventListener('blur', () => applyQty(parseValue(input.value, parseValue(input.min, 1)), root));
    });

    const primaryQtyInput = document.querySelector('[data-primary-qty]');
    if (primaryQtyInput) {
        const primaryRoot = primaryQtyInput.closest('[data-qty-control]');
        if (primaryRoot) {
            applyQty(parseValue(primaryQtyInput.value, parseValue(primaryQtyInput.min, 1)), primaryRoot);
        }
    }

    document.querySelectorAll('[data-scroll-link]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href');
            if (!href || !href.startsWith('#')) {
                return;
            }

            const target = document.querySelector(href);
            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    const navLinks = Array.from(document.querySelectorAll('[data-nav-link]'));
    const sectionIds = navLinks.map((link) => link.dataset.navLink).filter(Boolean);

    const setActiveNav = (id) => {
        navLinks.forEach((link) => {
            const isActive = link.dataset.navLink === id;
            link.classList.toggle('border-brand-primary', isActive);
            link.classList.toggle('text-brand-dark', isActive);
            link.classList.toggle('border-transparent', !isActive);
            link.classList.toggle('text-slate-500', !isActive);
            link.classList.toggle('text-slate-900', isActive);
        });
    };

    if (sectionIds.length > 0 && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setActiveNav(entry.target.id);
                    }
                });
            },
            { rootMargin: '-20% 0px -70% 0px', threshold: 0 },
        );

        sectionIds.forEach((id) => {
            const element = document.getElementById(id);
            if (element) {
                observer.observe(element);
            }
        });
    }

    const copySkuBtn = document.querySelector('[data-copy-sku]');
    if (copySkuBtn) {
        const iconCopy = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
        const iconCheck = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';

        copySkuBtn.addEventListener('click', async () => {
            const value = copySkuBtn.dataset.value || '';
            try {
                await navigator.clipboard.writeText(value);
                copySkuBtn.innerHTML = iconCheck;
                copySkuBtn.classList.add('border-emerald-300', 'bg-emerald-50');
                window.setTimeout(() => {
                    copySkuBtn.innerHTML = iconCopy;
                    copySkuBtn.classList.remove('border-emerald-300', 'bg-emerald-50');
                }, 1800);
            } catch {
                // Clipboard API unavailable.
            }
        });
    }
});
