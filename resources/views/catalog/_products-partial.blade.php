@php
    $priorityCount = (int) ($priorityCount ?? 0);
@endphp

@foreach($products as $product)
    <x-catalog.product-card
        :product="$product"
        :image-loading="$loop->index < $priorityCount ? 'eager' : 'lazy'"
        :fetchpriority="$loop->first && $priorityCount > 0 ? 'high' : null"
    />
@endforeach
