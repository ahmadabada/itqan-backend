<?php

use App\Enums\SettingValueType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('exam_round_id')
                ->nullable()
                ->after('examiner_id')
                ->constrained('exam_rounds')
                ->restrictOnDelete();
        });

        $legacyRoundId = DB::table('exam_rounds')->insertGetId([
            'name'       => 'جولة ما قبل 18-08-2026',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentRoundId = DB::table('exam_rounds')->insertGetId([
            'name'       => 'جولة من 18-08-2026',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cutoff = '2026-08-18 00:00:00';

        DB::table('exams')
            ->where('started_at', '<', $cutoff)
            ->update(['exam_round_id' => $legacyRoundId]);

        DB::table('exams')
            ->where('started_at', '>=', $cutoff)
            ->update(['exam_round_id' => $currentRoundId]);

        DB::table('exams')
            ->whereNull('exam_round_id')
            ->where('created_at', '<', $cutoff)
            ->update(['exam_round_id' => $legacyRoundId]);

        DB::table('exams')
            ->whereNull('exam_round_id')
            ->update(['exam_round_id' => $currentRoundId]);

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'mobile_exam_round_id'],
            [
                'value'       => (string) $currentRoundId,
                'value_type'  => SettingValueType::Int->value,
                'description' => 'معرّف الجولة التي تُلحق بها اختبارات الموبايل تلقائياً',
                'updated_at'  => now(),
            ],
        );

        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_round_guard')
                ->nullable()
                ->storedAs("case when `status` = 'approved' then `exam_round_id` else null end");

            $table->unique(['student_id', 'approved_round_guard'], 'exams_one_approved_per_round');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropUnique('exams_one_approved_per_round');
            $table->dropColumn('approved_round_guard');

            $table->dropForeign(['exam_round_id']);
            $table->dropColumn('exam_round_id');
        });

        Schema::dropIfExists('exam_rounds');

        DB::table('system_settings')->where('key', 'mobile_exam_round_id')->delete();
    }
};
