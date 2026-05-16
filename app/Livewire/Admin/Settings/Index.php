<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\ScoreCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('إعدادات النظام')]
class Index extends Component
{
    public string $passing_score          = '';
    public bool   $results_query_enabled  = false;
    public string $reexam_permit_ttl_days = '';
    public string $excel_import_mode      = 'skip';
    public string $academy_name           = '';

    public function mount(): void
    {
        $user = Auth::user();
        if ($user->role === UserRole::Examiner) {
            $this->redirect(route('examiner.dashboard'));
        }

        $this->loadSettings();
    }

    public function saveSettings(): void
    {
        $this->validate([
            'passing_score'          => ['required', 'integer', 'min:0', 'max:100'],
            'reexam_permit_ttl_days' => ['required', 'integer', 'min:1', 'max:365'],
            'excel_import_mode'      => ['required', 'in:skip,update'],
            'academy_name'           => ['required', 'string', 'max:100'],
        ], [
            'passing_score.required'          => 'درجة الإجازة مطلوبة.',
            'passing_score.min'               => 'درجة الإجازة لا تقل عن 0.',
            'passing_score.max'               => 'درجة الإجازة لا تتجاوز 100.',
            'reexam_permit_ttl_days.required' => 'مدة صلاحية الإذن مطلوبة.',
            'reexam_permit_ttl_days.min'      => 'مدة الصلاحية يوم على الأقل.',
            'academy_name.required'           => 'اسم الأكاديمية مطلوب.',
        ]);

        $currentUser = Auth::user();

        $updates = [
            'passing_score'          => (string) $this->passing_score,
            'results_query_enabled'  => $this->results_query_enabled ? 'true' : 'false',
            'reexam_permit_ttl_days' => (string) $this->reexam_permit_ttl_days,
            'excel_import_mode'      => $this->excel_import_mode,
            'academy_name'           => $this->academy_name,
        ];

        $old = [];
        foreach ($updates as $key => $value) {
            $setting  = SystemSetting::where('key', $key)->first();
            $old[$key] = $setting?->value;
            SystemSetting::where('key', $key)->update(['value' => $value, 'updated_at' => now()]);
        }

        // BR-AUDIT-02: Log settings change
        AuditLog::create([
            'user_id'     => $currentUser->id,
            'action'      => 'settings_updated',
            'target_type' => 'system_setting',
            'old_values'  => $old,
            'new_values'  => $updates,
        ]);

        // Drop the per-request passing-score cache so the new threshold is reflected
        // immediately on the same request (e.g. when re-rendering exam lists).
        ScoreCalculator::clearPassingScoreCache();

        $this->dispatch('notify', type: 'success', message: 'تم حفظ الإعدادات بنجاح.');
    }

    public function toggleResultsQuery(): void
    {
        $this->results_query_enabled = ! $this->results_query_enabled;
    }

    public function render()
    {
        return view('livewire.admin.settings.index');
    }

    private function loadSettings(): void
    {
        $this->passing_score          = (string) SystemSetting::get('passing_score', 60);
        $this->results_query_enabled  = (bool)   SystemSetting::get('results_query_enabled', false);
        $this->reexam_permit_ttl_days = (string) SystemSetting::get('reexam_permit_ttl_days', 7);
        $this->excel_import_mode      = SystemSetting::get('excel_import_mode', 'skip');
        $this->academy_name           = SystemSetting::get('academy_name', 'أكاديمية الإتقان');
    }
}
