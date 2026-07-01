<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Mostrar formulario de confirmacion de contrasena.
     *
     * @group Autenticacion
     *
     * @authenticated
     *
     * @response 200 {"content":"Vista HTML de confirmacion de contrasena"}
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirmar contrasena antes de accion sensible.
     *
     * Valida la contrasena actual del usuario autenticado y marca la sesion como confirmada.
     *
     * @group Autenticacion
     *
     * @authenticated
     *
     * @bodyParam password string required Contrasena actual del usuario. Example: secret
     *
     * @response 302 {"redirect":"dashboard"}
     * @response 422 {"message":"La contrasena proporcionada es incorrecta."}
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
