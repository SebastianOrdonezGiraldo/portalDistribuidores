<option value="{{ $category->id }}" @selected($selected == $category->id)>
    {{ str_repeat('— ', $depth) }}{{ $category->name }}
</option>
@foreach($category->children as $child)
    @include('catalog._category-option', ['category' => $child, 'depth' => $depth + 1, 'selected' => $selected])
@endforeach

