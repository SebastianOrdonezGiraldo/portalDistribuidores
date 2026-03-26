<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isActive()) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu usuario esta inactivo. Contacta al administrador de tu empresa.',
            ], 403);
        }

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => 'Tu usuario esta inactivo. Contacta al administrador de tu empresa.',
            ]);
    }
}
