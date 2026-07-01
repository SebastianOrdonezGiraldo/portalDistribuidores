<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Verificar correo electronico con firma.
     *
     * URL firmada enviada por correo. Si ya estaba verificado, redirige igualmente.
     *
     * @group Autenticacion
     *
     * @authenticated
     *
     * @urlParam id integer required ID del usuario. Example: 1
     * @urlParam hash string required Hash de verificacion firmado.
     *
     * @response 302 {"redirect":"dashboard?verified=1"}
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
