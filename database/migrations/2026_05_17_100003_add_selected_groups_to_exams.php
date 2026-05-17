<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Three groups the examiner picked for half_quran mode (JSON array of 3 ints in 1..6).
            // NULL for full_quran exams where groups are derived from the fixed pairing.
            $table->json('selected_groups')->nullable()->after('exam_type');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('selected_groups');
        });
    }
};
