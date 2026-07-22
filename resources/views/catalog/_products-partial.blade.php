{{--
View contract:
- Source: CatalogController/ProductController AJAX payloads and product-grid-section component.
- Expects: $products paginator/collection and optional $listKey, $pricingMode, $tier.
- Owns: rendering product cards and marking the first catalog card as the LCP candidate.
- Notes: product loading and pagination URLs are prepared by the caller.
--}}
@php
    $listKey = $listKey ?? null;
    $isFirstPage = ! method_exists($products, 'currentPage') || $products->currentPage() === 1;
    $shouldMarkCatalogLcp = $listKey === 'catalog' && $isFirstPage;
    $pricingMode = $pricingMode ?? null;
    $tier = $tier ?? null;
@endphp

@foreach($products as $product)
    <x-catalog.product-card
        :product="$product"
        :is-lcp-candidate="$shouldMarkCatalogLcp && $loop->first"
        :pricing-mode="$pricingMode"
        :tier="$tier"
    />
@endforeach
