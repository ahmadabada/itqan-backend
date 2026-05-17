<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreignId('recitation_question_id')
                ->nullable()
                ->after('question_number')
                ->constrained('recitation_questions')
                ->nullOnDelete();

            $table->index('recitation_question_id');
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropForeign(['recitation_question_id']);
            $table->dropIndex(['recitation_question_id']);
            $table->dropColumn('recitation_question_id');
        });
    }
};
