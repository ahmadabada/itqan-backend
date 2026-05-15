<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('question_number')->unsigned();
            $table->smallInteger('errors_count')->unsigned()->default(0);
            $table->smallInteger('warnings_count')->unsigned()->default(0);
            $table->smallInteger('continuations_count')->unsigned()->default(0);
            $table->decimal('final_score', 4, 2)->default(30);
            $table->timestamps();

            $table->unique(['exam_id', 'question_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
