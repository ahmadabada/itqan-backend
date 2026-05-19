<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Autocomplete source for the new-exam form. Not a source of truth — every
// upload truncates and replaces the table. Duplicates and null national_id
// are intentionally allowed. The wipe-test-data command intentionally leaves
// this table alone since the admin curates it independently of test data.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suggested_students', function (Blueprint $table) {
            $table->id();
            $table->string('national_id', 9)->nullable();
            $table->string('first_name', 50);
            $table->string('second_name', 50)->nullable();
            $table->string('third_name', 50)->nullable();
            $table->string('family_name', 50);
            // Title-case values to match the existing students.student_zone enum
            // so an auto-filled value can be passed straight through to exam saves.
            $table->enum('student_zone', ['East Gaza', 'West Gaza', 'North Gaza', 'South Gaza']);
            $table->string('gender', 10);
            $table->boolean('is_recite_before')->default(false);
            $table->timestamps();

            $table->index('family_name');
            $table->index('gender');
            $table->index('national_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggested_students');
    }
};
