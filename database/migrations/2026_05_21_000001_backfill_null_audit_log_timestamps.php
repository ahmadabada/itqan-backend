<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill rows that were inserted before AuditLog auto-stamped
        // created_at on creation. Anchored to "yesterday 8 PM" (2026-05-20 20:00)
        // so historical entries get a plausible, consistent timestamp.
        DB::table('audit_logs')
            ->whereNull('created_at')
            ->update(['created_at' => '2026-05-20 20:00:00']);
    }

    public function down(): void
    {
        // Reverse only the rows we touched — leave any legitimate 2026-05-20 20:00:00
        // entries alone (none should exist, but be safe).
        DB::table('audit_logs')
            ->where('created_at', '2026-05-20 20:00:00')
            ->update(['created_at' => null]);
    }
};
