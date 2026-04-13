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
    public function edit(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorize('editCompany', Distributor::class);

        $distributor = $user->distributor;

        return view('empresa.profile.edit', compact('distributor'));
    }

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorize('editCompany', Distributor::class);

        $user->distributor->update($request->validated());

        return back()->with('status', 'Datos de empresa actualizados correctamente.');
    }
}
