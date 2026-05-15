<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReexamPermit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReexamPermitController extends Controller
{
    // POST /reexam-permits/verify
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'permit_code' => ['required', 'string'],
            'student_id'  => ['required', 'integer'],
        ]);

        $permit = ReexamPermit::where('permit_code', $request->permit_code)->first();

        if (! $permit) {
            return response()->json([
                'error'   => 'permit_not_found',
                'message' => 'رمز الإذن غير موجود.',
            ], 404);
        }

        // BR-REEX-04: permit is single-use
        if ($permit->is_used) {
            return response()->json([
                'error'   => 'permit_already_used',
                'message' => 'تم استخدام هذا الإذن مسبقاً.',
            ], 409);
        }

        // BR-REEX-05: permit has an expiry date
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

    // GET /reexam-permits/active — BR-REEX-03: Flutter fetches for offline verification
    public function active(Request $request): JsonResponse
    {
        $permits = ReexamPermit::where('is_used', false)
            ->where('expires_at', '>', now())
            ->get(['student_id', 'permit_code', 'signature', 'expires_at']);

        return response()->json([
            'permits' => $permits->map(fn($p) => [
                'student_id' => $p->student_id,
                'permit_code' => $p->permit_code,
                'signature'  => $p->signature,
                'expires_at' => $p->expires_at->toISOString(),
            ]),
        ]);
    }
}
