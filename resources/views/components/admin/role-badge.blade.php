@props(['role'])

@php
    use App\Modules\Shared\Enums\UserRole;

    $roleValue = $role instanceof UserRole ? $role->value : (string) $role;
    $isAdmin = $roleValue === UserRole::Admin->value;
@endphp

<x-ui.badge :variant="$isAdmin ? 'brand' : 'info'">
    {{ $isAdmin ? 'Administrador' : 'Cuenta de distribuidor' }}
</x-ui.badge>
