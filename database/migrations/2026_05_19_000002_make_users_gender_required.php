<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Suggested-students filtering keys off the examiner's own gender — there is no
// sensible "any" fallback, so we make the column NOT NULL. Any pre-existing
// null rows (super admin seeded before gender was a thing) are backfilled to
// 'male' to keep them logged in; the admin can flip them via the users page.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('gender')->update(['gender' => 'male']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 10)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->change();
        });
    }
};
