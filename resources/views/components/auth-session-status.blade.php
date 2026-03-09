@props(['status'])

@if ($status)
    <x-ui.alert variant="success" {{ $attributes }}>{{ $status }}</x-ui.alert>
@endif
