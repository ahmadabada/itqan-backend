<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'approved_round_guard')) {
                $table->dropUnique('exams_one_approved_per_round');
                $table->dropColumn('approved_round_guard');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'approved_round_guard')) {
                $table->unsignedBigInteger('approved_round_guard')
                    ->nullable()
                    ->storedAs("case when `status` = 'approved' then `exam_round_id` else null end");

                $table->unique(['student_id', 'approved_round_guard'], 'exams_one_approved_per_round');
            }
        });
    }
};
