<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Company\Http\Requests\UpdateCompanyProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    /**
     * Ver perfil de empresa.
     *
     * @group Empresa
     *
     * @authenticated
     *
     * @response 200 {"content":"Vista HTML de perfil de empresa"}
     * @response 403 {"message":"No autorizado"}
     */
    public function edit(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorize('editCompany', Distributor::class);

        $distributor = $user->distributor;

        return view('empresa.profile.edit', compact('distributor'));
    }

    /**
     * Actualizar perfil de empresa.
     *
     * @group Empresa
     *
     * @authenticated
     *
     * @bodyParam name string required Nombre de empresa. Example: Distribuciones Medicas SAS
     * @bodyParam nit string required NIT solo numerico. Example: 900123456
     * @bodyParam address string required Direccion. Example: Calle 100 # 10-20
     * @bodyParam city string required Ciudad. Example: Bogota
     * @bodyParam phone string required Telefono. Example: 3001234567
     * @bodyParam contact_email string required Correo de contacto. Example: compras@example.com
     * @bodyParam contact_name string required Nombre de contacto. Example: Ana Perez
     *
     * @response 302 {"redirect":"back"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorize('editCompany', Distributor::class);

        $user->distributor->update($request->validated());

        return back()->with('status', 'Datos de empresa actualizados correctamente.');
    }
}
