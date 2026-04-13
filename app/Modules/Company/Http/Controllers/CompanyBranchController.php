<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Company\Http\Requests\StoreCompanyBranchRequest;
use App\Modules\Company\Http\Requests\UpdateCompanyBranchRequest;
use App\Modules\Company\Models\CompanyBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyBranchController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageBranches');

        /** @var User $user */
        $user = auth()->user();

        $branches = CompanyBranch::query()
            ->where('distributor_id', $user->distributor_id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('empresa.branches.index', compact('branches'));
    }

    public function create(): View
    {
        $this->authorize('manageBranches');

        return view('empresa.branches.form', [
            'branch' => new CompanyBranch,
        ]);
    }

    public function store(StoreCompanyBranchRequest $request): RedirectResponse
    {
        $this->authorize('manageBranches');

        /** @var User $user */
        $user = auth()->user();

        $payload = $request->validated();
        $payload['distributor_id'] = $user->distributor_id;

        if ($payload['is_default'] ?? false) {
            CompanyBranch::where('distributor_id', $user->distributor_id)
                ->update(['is_default' => false]);
        }

        CompanyBranch::create($payload);

        return redirect()->route('empresa.branches.index')
            ->with('status', 'Sucursal creada correctamente.');
    }

    public function edit(CompanyBranch $branch): View
    {
        $this->authorize('manageBranches');
        $this->authorizeBranchBelongsToCompany($branch);

        return view('empresa.branches.form', compact('branch'));
    }

    public function update(UpdateCompanyBranchRequest $request, CompanyBranch $branch): RedirectResponse
    {
        $this->authorize('manageBranches');
        $this->authorizeBranchBelongsToCompany($branch);

        /** @var User $user */
        $user = auth()->user();

        $payload = $request->validated();

        if ($payload['is_default'] ?? false) {
            CompanyBranch::where('distributor_id', $user->distributor_id)
                ->where('id', '!=', $branch->id)
                ->update(['is_default' => false]);
        }

        $branch->update($payload);

        return redirect()->route('empresa.branches.index')
            ->with('status', 'Sucursal actualizada correctamente.');
    }

    public function destroy(CompanyBranch $branch): RedirectResponse
    {
        $this->authorize('manageBranches');
        $this->authorizeBranchBelongsToCompany($branch);

        $name = $branch->name;
        $branch->delete();

        return redirect()->route('empresa.branches.index')
            ->with('status', "Sucursal \"{$name}\" eliminada.");
    }

    public function setDefault(CompanyBranch $branch): RedirectResponse
    {
        $this->authorize('manageBranches');
        $this->authorizeBranchBelongsToCompany($branch);

        /** @var User $user */
        $user = auth()->user();

        CompanyBranch::where('distributor_id', $user->distributor_id)
            ->update(['is_default' => false]);

        $branch->update(['is_default' => true]);

        return back()->with('status', "\"{$branch->name}\" establecida como dirección predeterminada.");
    }

    private function authorizeBranchBelongsToCompany(CompanyBranch $branch): void
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless(
            (int) $branch->distributor_id === (int) $user->distributor_id,
            403,
            'No tienes acceso a esta sucursal.'
        );
    }
}
