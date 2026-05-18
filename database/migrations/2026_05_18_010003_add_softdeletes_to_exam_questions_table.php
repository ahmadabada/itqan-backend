<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BR-SYNC-*: After admin merges duplicates, the losing exam's questions stay reachable
// (is_authoritative=false) and need soft-delete to survive an explicit admin removal.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
