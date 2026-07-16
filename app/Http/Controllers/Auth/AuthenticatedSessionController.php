<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Mostrar formulario de login.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @response 200 {"content":"Vista HTML del login"}
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Iniciar sesion.
     *
     * Autentica con sesion web y redirige segun el rol.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @bodyParam email string required Correo del usuario. Example: admin@importcorporal.test
     * @bodyParam password string required Contrasena del usuario. Example: secret
     *
     * @response 302 {"redirect":"catalog.index|dashboard"}
     * @response 422 {"message":"Credenciales invalidas o campos requeridos"}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->user()?->isDistributor()) {
            return redirect()->route('catalog.index');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Cerrar sesion.
     *
     * @group Autenticacion
     *
     * @authenticated
     *
     * @response 302 {"redirect":"/"}
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
