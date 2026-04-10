<?php

namespace App\Modules\Company\Policies;

use App\Models\User;

class CompanyPolicy
{
    /**
     * Cualquier usuario distribuidor activo vinculado a una empresa puede
     * editar la ficha maestra de su empresa.
     * (Admin global manejado en el Gate::define del AppServiceProvider)
     */
    public function editCompany(User $user): bool
    {
        return $user->isDistributor()
            && $user->isActive()
            && $user->distributor_id !== null;
    }
}
