<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuthAccess\Mail\EmailVerificationCodeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailVerificationCodeController extends Controller
{
    /**
     * Mostrar formulario de codigo de verificacion.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @response 200 {"content":"Vista HTML para ingresar codigo"}
     * @response 302 {"redirect":"register"}
     */
    public function show(Request $request): RedirectResponse|View
    {
        $userId = $request->session()->get('verify_email_user_id');

        if (! $userId) {
            return redirect()->route('register');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget('verify_email_user_id');

            return redirect()->route('register');
        }

        if ($user->email_verified_at !== null) {
            $request->session()->forget('verify_email_user_id');

            return redirect()->route('register.pending')
                ->with('status', 'Tu correo ya estaba verificado.');
        }

        return view('auth.verify-email-code', ['email' => $user->email]);
    }

    /**
     * Verificar correo con codigo.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @bodyParam code string required Codigo numerico de 6 digitos. Example: 123456
     *
     * @response 302 {"redirect":"register.pending"}
     * @response 422 {"message":"El codigo es incorrecto o ha expirado."}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], [
            'code.required' => 'El código de verificación es requerido.',
            'code.digits' => 'El código debe ser de 6 dígitos numéricos.',
        ]);

        $userId = $request->session()->get('verify_email_user_id');

        if (! $userId) {
            return redirect()->route('register');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget('verify_email_user_id');

            return redirect()->route('register');
        }

        if (! $user->hasValidVerificationCode($request->input('code'))) {
            return back()->withErrors([
                'code' => 'El código es incorrecto o ha expirado.',
            ]);
        }

        $user->email_verified_at = now();
        $user->save();
        $user->clearEmailVerificationCode();

        $request->session()->forget('verify_email_user_id');

        return redirect()->route('register.pending')
            ->with('status', 'Recibimos tu solicitud. Validaremos tus datos y activaremos tu acceso.');
    }

    /**
     * Reenviar codigo de verificacion.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @response 302 {"redirect":"back"}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('verify_email_user_id');

        if (! $userId) {
            return redirect()->route('register');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget('verify_email_user_id');

            return redirect()->route('register');
        }

        if ($user->email_verified_at !== null) {
            $request->session()->forget('verify_email_user_id');

            return redirect()->route('register.pending');
        }

        $code = $user->generateEmailVerificationCode();

        try {
            Mail::to($user->email)->send(new EmailVerificationCodeMail($user, $code));
        } catch (\Throwable $exception) {
            Log::error('email_verification_code.resend.failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Te enviamos un nuevo código de verificación.');
    }
}
