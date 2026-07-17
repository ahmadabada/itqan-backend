<?php

use App\Enums\Halaqah;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->enum('halaqah', array_column(Halaqah::cases(), 'value'))->after('gender');
            $table->index('halaqah');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['halaqah']);
            $table->dropColumn('halaqah');
        });
    }
};
