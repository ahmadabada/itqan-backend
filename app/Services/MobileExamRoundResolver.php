<?php

namespace App\Services;

use App\Enums\SettingValueType;
use App\Models\ExamRound;
use App\Models\SystemSetting;

class MobileExamRoundResolver
{
    public function resolveId(): int
    {
        $configuredId = (int) SystemSetting::get('mobile_exam_round_id', 0);
        if ($configuredId > 0 && ExamRound::whereKey($configuredId)->exists()) {
            return $configuredId;
        }

        $latest = ExamRound::latest('id')->first();
        if ($latest) {
            return (int) $latest->id;
        }

        // Safety net for fresh databases where rounds were not seeded yet.
        $created = ExamRound::create(['name' => 'جولة افتراضية']);
        SystemSetting::query()->updateOrInsert(
            ['key' => 'mobile_exam_round_id'],
            [
                'value'       => (string) $created->id,
                'value_type'  => SettingValueType::Int->value,
                'description' => 'معرّف الجولة التي تُلحق بها اختبارات الموبايل تلقائياً',
                'updated_at'  => now(),
            ]
        );

        return (int) $created->id;
    }
}
