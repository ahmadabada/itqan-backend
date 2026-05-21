<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Splits the single passing_score into per-gender thresholds. The legacy
// passing_score row is kept as a fallback (ScoreCalculator falls back to it
// if a gender-specific key is missing).
return new class extends Migration
{
    public function up(): void
    {
        $existing = (int) (DB::table('system_settings')
            ->where('key', 'passing_score')
            ->value('value') ?? 60);

        $rows = [
            [
                'key'         => 'passing_score_male',
                'description' => 'درجة الإجازة للذكور من 100',
            ],
            [
                'key'         => 'passing_score_female',
                'description' => 'درجة الإجازة للإناث من 100',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $row['key']],
                [
                    'value'       => (string) $existing,
                    'value_type'  => 'int',
                    'description' => $row['description'],
                    'updated_at'  => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', ['passing_score_male', 'passing_score_female'])
            ->delete();
    }
};
