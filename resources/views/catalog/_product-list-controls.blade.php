{{--
View contract:
- Source: CatalogController/ProductController AJAX payloads and product-grid-section component.
- Expects: $products paginator with hasMorePages/hasPages.
- Owns: optional load-more and server-side pagination controls only.
- Notes: JavaScript reads data-product-load-more from the surrounding product list container.
--}}
@php($showLoadMore = $showLoadMore ?? true)

@if($showLoadMore && $products->hasMorePages())
    <div class="flex justify-center">
        <button
            type="button"
            class="btn btn-secondary w-full justify-center sm:w-auto"
            data-product-load-more
            data-loading-label="Cargando..."
        >
            Cargar más
        </button>
    </div>
@endif

@if($products->hasPages())
    <x-ui.pagination :paginator="$products" />
@endif
