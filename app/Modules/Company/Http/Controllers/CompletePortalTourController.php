<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompletePortalTourController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->distributor_id !== null, 404);

        $user->distributor()
            ->whereNull('portal_tour_completed_at')
            ->update(['portal_tour_completed_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
