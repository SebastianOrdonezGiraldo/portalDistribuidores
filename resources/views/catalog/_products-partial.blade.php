@foreach($products as $product)
    <x-catalog.product-card :product="$product" />
@endforeach
