<?php

namespace App\Modules\Admin\Policies;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;

class DistributorPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Distributor $distributor): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Distributor $distributor): bool
    {
        return false;
    }

    public function updateTier(User $user, Distributor $distributor): bool
    {
        // Solo administradores (concedidos vía before()) pueden cambiar el nivel.
        return false;
    }

    public function delete(User $user, Distributor $distributor): bool
    {
        return false;
    }
}
