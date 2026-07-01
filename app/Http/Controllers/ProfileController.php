<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Mostrar formulario de perfil de usuario.
     *
     * @group Autenticacion
     *
     * @authenticated
     *
     * @response 200 {"content":"Vista HTML del perfil"}
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Actualizar perfil del usuario autenticado.
     *
     * Si se cambia el email, se marca como no verificado.
     *
     * @group Autenticacion
     *
     * @authenticated
     *
     * @bodyParam name string required Nombre del usuario. Example: Carlos Perez
     * @bodyParam email string required Correo electronico unico. Example: carlos@example.com
     *
     * @response 302 {"redirect":"profile.edit", "status":"profile-updated"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
