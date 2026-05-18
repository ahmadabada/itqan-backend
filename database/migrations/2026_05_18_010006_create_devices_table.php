<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One row per physical device. fcm_token + last_user_id are upserted on every login,
// so a single device tracks whichever user is currently signed in.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_uuid', 64)->unique();
            $table->text('fcm_token')->nullable();
            $table->string('fcm_platform', 10)->nullable();
            $table->foreignId('last_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();

            $table->index('last_user_id');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
