@php
    $listKey = $listKey ?? null;
    $isFirstPage = ! method_exists($products, 'currentPage') || $products->currentPage() === 1;
    $shouldMarkCatalogLcp = $listKey === 'catalog' && $isFirstPage;
@endphp

@foreach($products as $product)
    <x-catalog.product-card :product="$product" :is-lcp-candidate="$shouldMarkCatalogLcp && $loop->first" />
@endforeach
