@php
    $categories = $headerQuickCategories ?? collect();
@endphp

@if($categories?->isNotEmpty())
    <div class="catalog-quick-categories">
        <a href="{{ route('catalog.index') }}" class="catalog-category-chip is-active">
            Todas
        </a>
        @foreach($categories as $category)
            <a href="{{ route('catalog.index', ['category_id' => $category->id]) }}" class="catalog-category-chip">
                {{ $category->name }}
            </a>
        @endforeach
    </div>
@endif
