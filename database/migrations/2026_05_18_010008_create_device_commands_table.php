<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Outbound commands from admin to a device (e.g. wipe_data). FCM delivers a notification
// pointing at the row; the device polls /sync/commands as a fallback when push fails.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->string('device_uuid', 64);
            $table->string('command_type', 30);
            $table->json('payload')->nullable();

            $table->foreignId('issued_by_admin_id')
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at')->useCurrent();

            $table->string('status', 20)->default('pending');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->index('device_uuid');
            $table->index('status');
            $table->index(['device_uuid', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
