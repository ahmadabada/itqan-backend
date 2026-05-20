<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Backfill: students soft-deleted before this migration left their exams,
// exam_questions, and reexam_permits with deleted_at = NULL, so the admin
// Exams index, dashboard counters, and PDF report leaked rows with a null
// student name. Going forward Student model events cascade automatically;
// this migration brings existing rows in line.
//
// The child's deleted_at is set to its parent student's deleted_at so the
// Student `restoring` event can later pair them by timestamp equality.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE exams e
            INNER JOIN students s ON e.student_id = s.id
               SET e.deleted_at = s.deleted_at
             WHERE s.deleted_at IS NOT NULL
               AND e.deleted_at IS NULL
        ');

        DB::statement('
            UPDATE exam_questions eq
            INNER JOIN exams e ON eq.exam_id = e.id
            INNER JOIN students s ON e.student_id = s.id
               SET eq.deleted_at = s.deleted_at
             WHERE s.deleted_at IS NOT NULL
               AND eq.deleted_at IS NULL
        ');

        DB::statement('
            UPDATE reexam_permits rp
            INNER JOIN students s ON rp.student_id = s.id
               SET rp.deleted_at = s.deleted_at
             WHERE s.deleted_at IS NOT NULL
               AND rp.deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('
            UPDATE reexam_permits rp
            INNER JOIN students s ON rp.student_id = s.id
               SET rp.deleted_at = NULL
             WHERE s.deleted_at IS NOT NULL
               AND rp.deleted_at = s.deleted_at
        ');

        DB::statement('
            UPDATE exam_questions eq
            INNER JOIN exams e ON eq.exam_id = e.id
            INNER JOIN students s ON e.student_id = s.id
               SET eq.deleted_at = NULL
             WHERE s.deleted_at IS NOT NULL
               AND eq.deleted_at = s.deleted_at
        ');

        DB::statement('
            UPDATE exams e
            INNER JOIN students s ON e.student_id = s.id
               SET e.deleted_at = NULL
             WHERE s.deleted_at IS NOT NULL
               AND e.deleted_at = s.deleted_at
        ');
    }
};
