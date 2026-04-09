<?php

namespace App\Modules\AuthAccess\Middleware;

use App\Modules\Shared\Enums\DistributorStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isActive()) {
            return $this->logout($request, trans('auth.inactive_user'));
        }

        if ($user->isDistributor() && $user->distributor && ! $user->distributor->isActive()) {
            return $this->logout($request, $this->distributorAccessMessage($user->distributor?->status));
        }

        if (! in_array($user->role->value, $roles, true)) {
            abort(403, 'No autorizado para este recurso.');
        }

        return $next($request);
    }

    private function logout(Request $request, string $message): Response
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    private function distributorAccessMessage(?DistributorStatus $status): string
    {
        return match ($status) {
            DistributorStatus::PendingReview => trans('auth.company_pending_review'),
            DistributorStatus::Rejected => trans('auth.company_rejected'),
            DistributorStatus::Suspended => trans('auth.company_suspended'),
            default => trans('auth.company_inactive'),
        };
    }
}
