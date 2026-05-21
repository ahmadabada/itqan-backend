<?php

namespace Database\Seeders;

use App\Enums\SettingValueType;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key'         => 'passing_score',
                'value'       => '60',
                'value_type'  => SettingValueType::Int,
                'description' => 'درجة الإجازة من 100 (fallback إذا لم يوجد المفتاح حسب الجنس)',
            ],
            [
                'key'         => 'passing_score_male',
                'value'       => '60',
                'value_type'  => SettingValueType::Int,
                'description' => 'درجة الإجازة للذكور من 100',
            ],
            [
                'key'         => 'passing_score_female',
                'value'       => '60',
                'value_type'  => SettingValueType::Int,
                'description' => 'درجة الإجازة للإناث من 100',
            ],
            [
                'key'         => 'results_query_enabled',
                'value'       => 'false',
                'value_type'  => SettingValueType::Bool,
                'description' => 'تفعيل استعلام الطلاب عن نتائجهم',
            ],
            [
                'key'         => 'reexam_permit_ttl_days',
                'value'       => '7',
                'value_type'  => SettingValueType::Int,
                'description' => 'صلاحية رمز إعادة الاختبار (أيام)',
            ],
            [
                'key'         => 'excel_import_mode',
                'value'       => 'skip',
                'value_type'  => SettingValueType::String,
                'description' => 'عند تكرار الطالب في الاستيراد: skip أو update',
            ],
            [
                'key'         => 'academy_name',
                'value'       => 'أكاديمية الإتقان',
                'value_type'  => SettingValueType::String,
                'description' => 'اسم الأكاديمية (يظهر في الشهادات)',
            ],
        ];

        foreach ($settings as $data) {
            SystemSetting::firstOrCreate(
                ['key' => $data['key']],
                $data + ['updated_at' => now()],
            );
        }

        $this->command->info('System settings seeded (' . count($settings) . ' entries).');
    }
}
