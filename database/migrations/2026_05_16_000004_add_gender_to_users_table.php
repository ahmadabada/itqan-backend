<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Required at validation level. Kept nullable in DB to allow existing rows
            // (super admin / pre-existing examiners) to be backfilled without migration failure.
            $table->string('gender', 10)->nullable()->after('family_name');
            $table->index('gender');
        });

        // Backfill existing users — default super admin & legacy users to 'male'
        // (admin can edit later from the admin panel).
        \DB::table('users')->whereNull('gender')->update(['gender' => 'male']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['gender']);
            $table->dropColumn('gender');
        });
    }
};
