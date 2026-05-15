<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('national_id', 20)->unique();
            $table->string('first_name', 50);
            $table->string('second_name', 50)->nullable();
            $table->string('third_name', 50)->nullable();
            $table->string('family_name', 50);
            $table->timestamps();

            $table->index('family_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
