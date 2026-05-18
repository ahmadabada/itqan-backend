<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BR-SYNC-*: client_request_id (UUID) gives the mobile a stable key for safe retries.
// is_authoritative flags the exam chosen by the admin after merging duplicate students.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('client_request_id', 36)->nullable()->unique()->after('device_uuid');

            $table->boolean('is_authoritative')->default(true)->after('is_approved');
            $table->foreignId('authoritative_decision_by')->nullable()->after('is_authoritative')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('authoritative_decision_at')->nullable()->after('authoritative_decision_by');

            $table->index('is_authoritative');

            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();

            $table->dropIndex(['is_authoritative']);

            $table->dropColumn('authoritative_decision_at');
            $table->dropForeign(['authoritative_decision_by']);
            $table->dropColumn('authoritative_decision_by');
            $table->dropColumn('is_authoritative');

            $table->dropUnique(['client_request_id']);
            $table->dropColumn('client_request_id');
        });
    }
};
