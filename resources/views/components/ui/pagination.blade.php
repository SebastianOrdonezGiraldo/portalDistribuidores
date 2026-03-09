@props(['paginator'])

@if($paginator && $paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'pagination-wrap']) }}>
        {{ $paginator->onEachSide(1)->links() }}
    </div>
@endif
