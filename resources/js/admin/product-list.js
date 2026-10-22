/**
 * "Load more" pagination for product lists (data-product-list).
 */
export function initProductLists() {
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
}
