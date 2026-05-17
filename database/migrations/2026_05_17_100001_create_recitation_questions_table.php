<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recitation_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('question_number');
            $table->unsignedTinyInteger('group_number');

            $table->unsignedTinyInteger('start_surah');
            $table->unsignedSmallInteger('start_ayah');
            $table->unsignedSmallInteger('start_page');

            $table->unsignedTinyInteger('end_surah');
            $table->unsignedSmallInteger('end_ayah');
            $table->unsignedSmallInteger('end_page');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('group_number');
            $table->index(['group_number', 'is_active']);
            $table->unique(['group_number', 'question_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recitation_questions');
    }
};
