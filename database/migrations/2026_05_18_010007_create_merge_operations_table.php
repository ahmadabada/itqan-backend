<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Audit trail for admin student merges. pre_merge_snapshot stores the full prior state
// so the merge can be undone (undone_at / undone_by_admin_id) without data loss.
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
