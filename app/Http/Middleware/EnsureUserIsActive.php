<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Blocks any authenticated API request from a deactivated user. Mirrors
// AuthController::login's account_disabled check so a Sanctum token issued
// before the admin flipped is_active off stops working on the next call —
// closing the window where a disabled examiner could still upload exams.
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->is_active) {
            return response()->json([
                'error'   => 'account_disabled',
                'message' => 'هذا الحساب غير مفعّل.',
            ], 403);
        }

        return $next($request);
    }
}
