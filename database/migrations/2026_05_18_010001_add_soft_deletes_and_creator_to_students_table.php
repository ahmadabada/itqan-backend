<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Students are always created on the server — by admin entry or Excel import —
// so the only offline-era metadata worth keeping is who created the row.
// national_id stays UNIQUE: it is the key the Excel import upserts on and the
// identity the mobile roster download relies on.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'created_by_user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('created_by_user_id')->nullable()->after('gender')
                    ->constrained('users')->nullOnDelete();
            });
        }

        if (! $this->hasIndex('students', 'students_created_by_user_id_index')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index('created_by_user_id');
            });
        }

        if (! Schema::hasColumn('students', 'deleted_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! $this->hasIndex('students', 'students_deleted_at_index')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index('deleted_at');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('students', 'students_deleted_at_index')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex(['deleted_at']);
            });
        }

        if (Schema::hasColumn('students', 'deleted_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if ($this->hasIndex('students', 'students_created_by_user_id_index')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex(['created_by_user_id']);
            });
        }

        if ($this->hasForeignKey('students', 'students_created_by_user_id_foreign')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['created_by_user_id']);
            });
        }

        if (Schema::hasColumn('students', 'created_by_user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('created_by_user_id');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return (bool) DB::selectOne(
            'select 1 from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ? limit 1',
            [$table, $indexName]
        );
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        return (bool) DB::selectOne(
            'select 1 from information_schema.table_constraints where table_schema = database() and table_name = ? and constraint_name = ? and constraint_type = ? limit 1',
            [$table, $constraintName, 'FOREIGN KEY']
        );
    }
};
