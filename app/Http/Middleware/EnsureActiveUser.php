<?php

namespace App\Http\Middleware;

use App\Modules\Shared\Enums\DistributorStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->isActive()) {
            return $this->logout($request, trans('auth.inactive_user'));
        }

        if ($user->isDistributor() && $user->distributor && ! $user->distributor->isActive()) {
            return $this->logout($request, $this->distributorAccessMessage($user->distributor->status));
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
