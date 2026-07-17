<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Students are always created on the server — by admin entry or Excel import —
// so the only offline-era metadata worth keeping is who created the row.
// national_id stays UNIQUE: it is the key the Excel import upserts on and the
// identity the mobile roster download relies on.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('gender')
                ->constrained('users')->nullOnDelete();
            $table->index('created_by_user_id');

            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();

            $table->dropIndex(['created_by_user_id']);
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn('created_by_user_id');
        });
    }
};
