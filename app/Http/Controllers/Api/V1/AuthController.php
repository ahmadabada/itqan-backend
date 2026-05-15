<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // BR-AUTH-01: Login via national_id + password
    // BR-AUTH-02: Token lifetime = SANCTUM_TOKEN_EXPIRATION minutes (30 days)
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'national_id' => ['required', 'string'],
            'password'    => ['required', 'string'],
            'device_uuid' => ['required', 'string', 'max:64'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        if (! Auth::attempt(['national_id' => $request->national_id, 'password' => $request->password])) {
            return response()->json([
                'error'   => 'invalid_credentials',
                'message' => 'بيانات الدخول غير صحيحة.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            return response()->json([
                'error'   => 'account_disabled',
                'message' => 'هذا الحساب غير مفعّل.',
            ], 403);
        }

        $expiresAt = now()->addMinutes(config('sanctum.expiration', 43200));

        // BR-AUTH-03: Token name includes device_uuid for tracking
        $token = $user->createToken(
            $request->device_name . '|' . $request->device_uuid,
            ['*'],
            $expiresAt,
        );

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token'      => $token->plainTextToken,
            'expires_at' => $expiresAt->toISOString(),
            'user'       => new UserResource($user),
        ]);
    }

    // BR-AUTH-04: Logout invalidates the current token
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        return response()->json([
            'user'             => new UserResource($request->user()),
            'token_expires_at' => $token->expires_at?->toISOString(),
        ]);
    }
}
