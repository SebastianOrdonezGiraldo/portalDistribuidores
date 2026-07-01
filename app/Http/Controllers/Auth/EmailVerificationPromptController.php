<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Mostrar aviso de verificacion de correo.
     *
     * Si el correo ya esta verificado, redirige al dashboard.
     *
     * @group Autenticacion
     *
     * @authenticated
     *
     * @response 200 {"content":"Vista HTML de aviso de verificacion"}
     * @response 302 {"redirect":"dashboard"}
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(route('dashboard', absolute: false))
                    : view('auth.verify-email');
    }
}
