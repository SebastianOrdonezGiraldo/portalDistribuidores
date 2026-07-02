<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Cambiar contrasena del usuario autenticado.
     *
     * Valida la contrasena actual y actualiza con la nueva.
     *
     * @group Autenticacion
     *
     * @authenticated
     *
     * @bodyParam current_password string required Contrasena actual del usuario. Example: secret
     * @bodyParam password string required Nueva contrasena (min 8 caracteres). Example: nuevo-secret-123
     * @bodyParam password_confirmation string required Confirmacion de la nueva contrasena. Example: nuevo-secret-123
     *
     * @response 302 {"redirect":"back", "status":"password-updated"}
     * @response 422 {"message":"current_password", "errors":{"current_password":["La contrasena actual es incorrecta."]}}
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('status', 'password-updated');
    }
}
