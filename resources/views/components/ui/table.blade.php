@props(['dense' => false])

<div class="table-wrap">
    <table {{ $attributes->merge(['class' => 'table-base '.($dense ? 'text-xs' : '')]) }}>
        {{ $slot }}
    </table>
</div>
