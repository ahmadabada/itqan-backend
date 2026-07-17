<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RETIRED 2026-07-17, kept per "disable, don't delete".
//
// This backed the admin merge flow from the offline-first era, when each offline
// exam created its own student row and duplicates were reconciled after the fact.
// Students are now created only on the server with a UNIQUE national_id, so no
// duplicates arise and nothing needs merging. The table stays for the historical
// record; nothing writes to it.
//
// Reviving this needs more than re-enabling a route: MergeService and the
// Admin\Merges components still read students.master_id / merged_at /
// merged_by_admin_id, which no longer exist. Those columns would have to come
// back first — see 2026_05_18_010001_add_soft_deletes_and_creator_to_students_table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merge_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_student_id')
                ->constrained('students')->restrictOnDelete();
            $table->json('merged_student_ids');
            $table->foreignId('authoritative_exam_id')->nullable()
                ->constrained('exams')->nullOnDelete();

            $table->json('pre_merge_snapshot');

            $table->foreignId('performed_by_admin_id')
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->text('notes')->nullable();

            $table->timestamp('undone_at')->nullable();
            $table->foreignId('undone_by_admin_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('undo_notes')->nullable();

            $table->timestamps();

            $table->index('master_student_id');
            $table->index('performed_by_admin_id');
            $table->index('undone_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merge_operations');
    }
};
