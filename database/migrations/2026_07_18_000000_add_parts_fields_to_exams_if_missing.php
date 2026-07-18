<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The parts fields were originally added by editing create_exams_table, which
// only reaches a freshly-migrated database. Any environment that had already
// run create_exams_table (e.g. production) never got these columns, so a mobile
// sync insert fails with a 500 (unknown column). This migration adds them
// idempotently: it is a no-op where the CREATE already provided them.
//
// Documentary only — default 0 so it also applies cleanly to any pre-existing
// exam rows.
return new class extends Migration
{
    public function up(): void
    {
        $add = [];
        if (! Schema::hasColumn('exams', 'parts_count')) {
            $add[] = 'parts_count';
        }
        if (! Schema::hasColumn('exams', 'new_memorization_parts')) {
            $add[] = 'new_memorization_parts';
        }
        if (empty($add)) {
            return;
        }

        Schema::table('exams', function (Blueprint $table) use ($add) {
            if (in_array('parts_count', $add, true)) {
                $table->tinyInteger('parts_count')->unsigned()->default(0)->after('attempt_number');
            }
            if (in_array('new_memorization_parts', $add, true)) {
                $table->tinyInteger('new_memorization_parts')->unsigned()->default(0)->after('parts_count');
            }
        });
    }

    public function down(): void
    {
        // Leave the columns in place on rollback — dropping them would break the
        // app the same way their absence does now. No-op is the safe reverse.
    }
};
