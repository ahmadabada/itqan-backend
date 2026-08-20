<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\ExamRound;
use App\Models\SystemSetting;
use App\Services\ScoreCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('إعدادات النظام')]
class Index extends Component
{
    public string $passing_score_male     = '';
    public string $passing_score_female   = '';
    public bool   $results_query_enabled  = false;
    public string $reexam_permit_ttl_days = '';
    public string $excel_import_mode      = 'skip';
    public string $academy_name           = '';
    public string $mobile_exam_round_id   = '';
    public string $new_exam_round_name    = '';
    public bool $canManageRounds          = false;

    /** @var Collection<int, ExamRound> */
    public Collection $examRounds;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user->role === UserRole::Examiner) {
            $this->redirect(route('examiner.dashboard'));
        }

        $this->canManageRounds = $user->role === UserRole::SuperAdmin || $user->is_super_admin;

        $this->loadSettings();
        $this->loadRounds();
    }

    public function saveSettings(): void
    {
        $rules = [
            'passing_score_male'     => ['required', 'integer', 'min:0', 'max:100'],
            'passing_score_female'   => ['required', 'integer', 'min:0', 'max:100'],
            'reexam_permit_ttl_days' => ['required', 'integer', 'min:1', 'max:365'],
            'excel_import_mode'      => ['required', 'in:skip,update'],
            'academy_name'           => ['required', 'string', 'max:100'],
        ];

        if ($this->canManageRounds) {
            $rules['mobile_exam_round_id'] = ['required', 'integer', 'exists:exam_rounds,id'];
        }

        $this->validate($rules, [
            'passing_score_male.required'     => 'درجة الإجازة (ذكور) مطلوبة.',
            'passing_score_male.min'          => 'درجة الإجازة (ذكور) لا تقل عن 0.',
            'passing_score_male.max'          => 'درجة الإجازة (ذكور) لا تتجاوز 100.',
            'passing_score_female.required'   => 'درجة الإجازة (إناث) مطلوبة.',
            'passing_score_female.min'        => 'درجة الإجازة (إناث) لا تقل عن 0.',
            'passing_score_female.max'        => 'درجة الإجازة (إناث) لا تتجاوز 100.',
            'reexam_permit_ttl_days.required' => 'مدة صلاحية الإذن مطلوبة.',
            'reexam_permit_ttl_days.min'      => 'مدة الصلاحية يوم على الأقل.',
            'academy_name.required'           => 'اسم الأكاديمية مطلوب.',
            'mobile_exam_round_id.required'   => 'اختر جولة الموبايل.',
            'mobile_exam_round_id.exists'     => 'الجولة المختارة غير موجودة.',
        ]);

        $currentUser = Auth::user();

        $updates = [
            'passing_score_male'     => (string) $this->passing_score_male,
            'passing_score_female'   => (string) $this->passing_score_female,
            'results_query_enabled'  => $this->results_query_enabled ? 'true' : 'false',
            'reexam_permit_ttl_days' => (string) $this->reexam_permit_ttl_days,
            'excel_import_mode'      => $this->excel_import_mode,
            'academy_name'           => $this->academy_name,
        ];

        if ($this->canManageRounds) {
            $updates['mobile_exam_round_id'] = (string) $this->mobile_exam_round_id;
        }

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

    public function createExamRound(): void
    {
        if (! $this->canManageRounds) {
            $this->dispatch('notify', type: 'error', message: 'هذه العملية متاحة للسوبر أدمن فقط.');
            return;
        }

        $this->validate([
            'new_exam_round_name' => ['required', 'string', 'max:100', 'unique:exam_rounds,name'],
        ], [
            'new_exam_round_name.required' => 'اسم الجولة مطلوب.',
            'new_exam_round_name.unique'   => 'اسم الجولة مستخدم مسبقاً.',
        ]);

        $round = ExamRound::create([
            'name' => trim($this->new_exam_round_name),
        ]);

        AuditLog::create([
            'user_id'     => Auth::user()->id,
            'action'      => 'exam_round_created',
            'target_type' => 'exam_round',
            'target_id'   => $round->id,
            'new_values'  => ['name' => $round->name],
        ]);

        $this->new_exam_round_name = '';
        $this->loadRounds();
        $this->dispatch('notify', type: 'success', message: 'تم إنشاء الجولة بنجاح.');
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
        // Fall back to the legacy passing_score for callers that haven't yet split.
        $legacyPassingScore = (string) SystemSetting::get('passing_score', 60);
        $this->passing_score_male     = (string) SystemSetting::get('passing_score_male', $legacyPassingScore);
        $this->passing_score_female   = (string) SystemSetting::get('passing_score_female', $legacyPassingScore);
        $this->results_query_enabled  = (bool)   SystemSetting::get('results_query_enabled', false);
        $this->reexam_permit_ttl_days = (string) SystemSetting::get('reexam_permit_ttl_days', 7);
        $this->excel_import_mode      = SystemSetting::get('excel_import_mode', 'skip');
        $this->academy_name           = SystemSetting::get('academy_name', 'أكاديمية الإتقان');
        $this->mobile_exam_round_id   = (string) SystemSetting::get('mobile_exam_round_id', '');
    }

    private function loadRounds(): void
    {
        $this->examRounds = ExamRound::query()->orderByDesc('id')->get(['id', 'name']);

        if ($this->mobile_exam_round_id === '' && $this->examRounds->isNotEmpty()) {
            $this->mobile_exam_round_id = (string) $this->examRounds->first()->id;
        }
    }
}
