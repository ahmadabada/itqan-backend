<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Collapses the historical six-state lifecycle into three.
//   completed       → approved (if is_approved=true) | excluded (otherwise)
//   pending_review  → excluded (no automatic admin-merge in the new model)
//   rejected        → excluded
//   completed_offline → excluded (this state was never actually written by the
//                                   code, but defensively normalize anyway)
//   in_progress     → unchanged
//   approved        → unchanged
//
// The is_approved / is_authoritative columns are kept for backward-compat but
// the application now reads from `status` only.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('exams')->where('status', 'completed')->where('is_approved', true)->update(['status' => 'approved']);
        DB::table('exams')->where('status', 'completed')->where('is_approved', false)->update(['status' => 'excluded']);
        DB::table('exams')->whereIn('status', ['pending_review', 'rejected', 'completed_offline'])->update(['status' => 'excluded']);
    }

    public function down(): void
    {
        // Lossy: cannot reliably reconstruct the original status. The old code
        // tolerated approved/excluded directly so we leave them as-is.
    }
};
