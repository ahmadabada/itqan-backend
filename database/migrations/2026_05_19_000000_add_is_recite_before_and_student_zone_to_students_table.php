<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_recite_before')->default(false)->after('gender');
            // Nullable so the migration succeeds on tables with existing rows.
            // The admin can backfill historical students later via the merge UI.
            $table->enum('student_zone', ['East Gaza', 'West Gaza', 'North Gaza', 'South Gaza'])->nullable()->after('is_recite_before');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['is_recite_before', 'student_zone']);
        });
    }
};
