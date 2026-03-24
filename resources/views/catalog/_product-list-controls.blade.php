@if($products->hasMorePages())
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
