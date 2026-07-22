@props(['verifiedAt' => null])

@php
    $isVerified = filled($verifiedAt);
@endphp

<x-ui.badge :variant="$isVerified ? 'success' : 'warning'">
    {{ $isVerified ? 'Verificado' : 'Pendiente' }}
</x-ui.badge>
