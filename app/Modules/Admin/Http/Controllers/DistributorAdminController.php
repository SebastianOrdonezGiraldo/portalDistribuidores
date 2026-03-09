<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\StoreDistributorRequest;
use App\Modules\Admin\Http\Requests\UpdateDistributorRequest;
use App\Modules\AuthAccess\Models\Distributor;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DistributorAdminController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Distributor::class);

        return view('admin.distributors.index', [
            'distributors' => Distributor::query()->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Distributor::class);

        return view('admin.distributors.form', ['distributor' => new Distributor()]);
    }

    public function store(StoreDistributorRequest $request): RedirectResponse
    {
        $this->authorize('create', Distributor::class);
        Distributor::create($request->validated());

        return redirect()->route('admin.distributors.index')->with('status', 'Distribuidor creado.');
    }

    public function edit(Distributor $distributor): View
    {
        $this->authorize('update', $distributor);

        return view('admin.distributors.form', ['distributor' => $distributor]);
    }

    public function update(UpdateDistributorRequest $request, Distributor $distributor): RedirectResponse
    {
        $this->authorize('update', $distributor);
        $distributor->update($request->validated());

        return redirect()->route('admin.distributors.index')->with('status', 'Distribuidor actualizado.');
    }

    public function destroy(Distributor $distributor): RedirectResponse
    {
        $this->authorize('delete', $distributor);
        $distributor->delete();

        return redirect()->route('admin.distributors.index')->with('status', 'Distribuidor eliminado.');
    }
}

