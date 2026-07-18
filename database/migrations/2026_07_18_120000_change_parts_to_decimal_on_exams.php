<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Parts fields become decimal(4,2) so an exam can span a fractional number of
// أجزاء (e.g. 5.5), still capped at 30.00 by app-level validation. They were
// previously tinyInteger. Existing whole-number rows convert cleanly.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->decimal('parts_count', 4, 2)->unsigned()->default(0)->change();
            $table->decimal('new_memorization_parts', 4, 2)->unsigned()->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->tinyInteger('parts_count')->unsigned()->default(0)->change();
            $table->tinyInteger('new_memorization_parts')->unsigned()->default(0)->change();
        });
    }
};
