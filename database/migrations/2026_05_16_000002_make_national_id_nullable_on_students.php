<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // MySQL allows multiple NULLs in a unique column, so the existing
            // unique index keeps national_id unique when present and lets it
            // be NULL for students enrolled without an ID.
            $table->string('national_id', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('national_id', 20)->nullable(false)->change();
        });
    }
};
