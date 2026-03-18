<?php

namespace App\Modules\Company\Policies;

use App\Models\User;

class CompanyPolicy
{
    /**
     * Solo admin_empresa puede editar datos de la empresa.
     * (Admin global manejado en el Gate::define del AppServiceProvider)
     */
    public function editCompany(User $user): bool
    {
        return $user->canEditCompany();
    }

    /**
     * Solo admin_empresa puede gestionar usuarios de su empresa.
     * (Admin global manejado en el Gate::define del AppServiceProvider)
     */
    public function manageUsers(User $user): bool
    {
        return $user->canManageCompanyUsers();
    }
}
