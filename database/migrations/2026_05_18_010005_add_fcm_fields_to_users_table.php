<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Push delivery for admin commands (wipe_data, etc.) targets the user's current device.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('fcm_token')->nullable()->after('last_login_at');
            $table->string('fcm_platform', 10)->nullable()->after('fcm_token');
            $table->timestamp('last_mobile_login_at')->nullable()->after('fcm_platform');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_mobile_login_at');
            $table->dropColumn('fcm_platform');
            $table->dropColumn('fcm_token');
        });
    }
};
