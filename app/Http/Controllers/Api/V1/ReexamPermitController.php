<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerifyPermitRequest;
use App\Models\ReexamPermit;
use Illuminate\Http\JsonResponse;

class ReexamPermitController extends Controller
{
    // POST /reexam-permits/verify
    public function verify(VerifyPermitRequest $request): JsonResponse
    {
        $permit = ReexamPermit::where('permit_code', $request->permit_code)->first();

        if (! $permit) {
            return response()->json([
                'error'   => 'permit_not_found',
                'message' => 'رمز الإذن غير موجود.',
            ], 404);
        }

        // BR-REEX-04: single-use
        if ($permit->is_used) {
            return response()->json([
                'error'   => 'permit_already_used',
                'message' => 'تم استخدام هذا الإذن مسبقاً.',
            ], 409);
        }

        // BR-REEX-05: expiry
        if (! $permit->expires_at->isFuture()) {
            return response()->json([
                'error'   => 'permit_expired',
                'message' => 'انتهت صلاحية هذا الإذن.',
            ], 410);
        }

        if ($permit->student_id !== (int) $request->student_id) {
            return response()->json([
                'error'   => 'permit_student_mismatch',
                'message' => 'هذا الإذن لا يخص هذا الطالب.',
            ], 403);
        }

        return response()->json([
            'valid'      => true,
            'expires_at' => $permit->expires_at->toISOString(),
        ]);
    }

    // GET /reexam-permits/active — BR-REEX-03: Flutter fetches for offline HMAC verification
    public function active(): JsonResponse
    {
        $permits = ReexamPermit::where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->get(['student_id', 'permit_code', 'signature', 'expires_at']);

        return response()->json([
            'permits' => $permits->map(fn($p) => [
                'student_id'  => $p->student_id,
                'permit_code' => $p->permit_code,
                'signature'   => $p->signature,
                'expires_at'  => $p->expires_at->toISOString(),
            ]),
        ]);
    }
}
