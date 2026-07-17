<?php

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Enums\ExamType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('examiner_id')->constrained('users')->restrictOnDelete();
            $table->string('exam_type')->default(ExamType::FullQuran->value);
            $table->tinyInteger('attempt_number')->unsigned()->default(1);

            // Scope of the exam, entered by the examiner when it starts.
            // Documentary only — neither value feeds the score or pass/fail.
            $table->tinyInteger('parts_count')->unsigned();
            $table->tinyInteger('new_memorization_parts')->unsigned();

            $table->decimal('rulings_score', 4, 2)->nullable();
            $table->decimal('total_score', 5, 2)->nullable();
            $table->boolean('is_passed')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->string('status')->default(ExamStatus::InProgress->value);
            $table->string('source')->default(ExamSource::Web->value);
            $table->string('device_uuid', 64)->nullable();
            $table->foreignId('reexam_permit_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('examiner_id');
            $table->index('status');
            $table->index('is_approved');
            $table->index('device_uuid');
            $table->unique(['student_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
