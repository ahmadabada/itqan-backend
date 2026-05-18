<?php

use App\Enums\ExamSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BR-SYNC-*: Offline-first design allows duplicate students by national_id.
// Each offline exam creates its own student row; admin merges duplicates post-hoc
// via master_id. client_request_id provides idempotency for the upload pipeline.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Duplicates are now allowed — keep the column indexed for lookup,
            // but no longer UNIQUE (admin merges duplicates after exam period).
            $table->dropUnique(['national_id']);
            $table->index('national_id');

            // Offline-source metadata
            $table->string('created_via', 20)->default(ExamSource::Web->value)->after('gender');
            $table->foreignId('created_by_user_id')->nullable()->after('created_via')
                ->constrained('users')->nullOnDelete();
            $table->string('device_uuid', 64)->nullable()->after('created_by_user_id');
            $table->string('client_request_id', 36)->nullable()->unique()->after('device_uuid');

            // Admin merge support
            $table->foreignId('master_id')->nullable()->after('client_request_id')
                ->constrained('students')->nullOnDelete();
            $table->timestamp('merged_at')->nullable()->after('master_id');
            $table->foreignId('merged_by_admin_id')->nullable()->after('merged_at')
                ->constrained('users')->nullOnDelete();

            $table->index('master_id');
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
            $table->dropIndex(['master_id']);

            $table->dropForeign(['merged_by_admin_id']);
            $table->dropColumn('merged_by_admin_id');
            $table->dropColumn('merged_at');
            $table->dropForeign(['master_id']);
            $table->dropColumn('master_id');

            $table->dropUnique(['client_request_id']);
            $table->dropColumn('client_request_id');
            $table->dropColumn('device_uuid');
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn('created_by_user_id');
            $table->dropColumn('created_via');

            $table->dropIndex(['national_id']);
            $table->unique('national_id');
        });
    }
};
