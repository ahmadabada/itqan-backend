<?php

namespace App\Services;

use App\Models\ReexamPermit;
use App\Models\Student;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// BR-REEX-02: Permit = short code + HMAC-SHA256 signature
// BR-REEX-03: Signature verifiable offline (Flutter side uses same HMAC_SECRET)
class ReexamPermitService
{
    // Generate a short permit code and sign it with HMAC-SHA256
    public static function grant(Student $student): ReexamPermit
    {
        $ttlDays = (int) SystemSetting::get('reexam_permit_ttl_days', 7);

        $code      = static::generateCode();
        $signature = static::sign($code, $student->national_id);

        return ReexamPermit::create([
            'student_id'  => $student->id,
            'granted_by'  => Auth::id(),
            'permit_code' => $code,
            'signature'   => $signature,
            'is_used'     => false,
            'expires_at'  => now()->addDays($ttlDays),
        ]);
    }

    // BR-REEX-03: Verify a permit code offline using HMAC
    public static function verifyOffline(string $code, string $nationalId, string $signature): bool
    {
        $expected = static::sign($code, $nationalId);
        return hash_equals($expected, $signature);
    }

    private static function generateCode(): string
    {
        // Format: RT-XXXX-YY  (e.g. RT-A7F2-9X)
        return 'RT-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(2));
    }

    private static function sign(string $code, string $nationalId): string
    {
        $secret = config('app.reexam_hmac_secret') ?? env('REEXAM_HMAC_SECRET', '');
        return hash_hmac('sha256', "{$code}:{$nationalId}", $secret);
    }
}
