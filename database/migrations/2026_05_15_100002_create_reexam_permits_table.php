<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reexam_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->string('permit_code', 20)->unique();
            $table->string('signature');
            $table->boolean('is_used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('student_id');
            $table->index(['is_used', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reexam_permits');
    }
};
