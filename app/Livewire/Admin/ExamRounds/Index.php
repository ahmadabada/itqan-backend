<?php

namespace App\Livewire\Admin\ExamRounds;

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamRound;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('جولات الاختبارات')]
class Index extends Component
{
    public ?int $editingRoundId = null;
    public string $editingRoundName = '';

    public function mount(): void
    {
        $user = Auth::user();
        if ($user->role === UserRole::Examiner) {
            $this->redirect(route('examiner.exams'), navigate: true);
        }
    }

    public function startRename(int $roundId): void
    {
        if (! $this->canRenameRounds()) {
            $this->dispatch('notify', type: 'error', message: 'هذه العملية متاحة للسوبر أدمن فقط.');
            return;
        }

        $round = ExamRound::find($roundId);
        if (! $round) {
            $this->dispatch('notify', type: 'error', message: 'الجولة غير موجودة.');
            return;
        }

        $this->editingRoundId = $round->id;
        $this->editingRoundName = $round->name;
    }

    public function cancelRename(): void
    {
        $this->editingRoundId = null;
        $this->editingRoundName = '';
    }

    public function renameRound(): void
    {
        if (! $this->canRenameRounds()) {
            $this->dispatch('notify', type: 'error', message: 'هذه العملية متاحة للسوبر أدمن فقط.');
            return;
        }

        if (! $this->editingRoundId) {
            return;
        }

        $round = ExamRound::find($this->editingRoundId);
        if (! $round) {
            $this->dispatch('notify', type: 'error', message: 'الجولة غير موجودة.');
            return;
        }

        $this->validate([
            'editingRoundName' => ['required', 'string', 'max:100', 'unique:exam_rounds,name,' . $round->id],
        ], [
            'editingRoundName.required' => 'اسم الجولة مطلوب.',
            'editingRoundName.unique' => 'اسم الجولة مستخدم مسبقا.',
        ]);

        $oldName = $round->name;
        $newName = trim($this->editingRoundName);
        if ($oldName === $newName) {
            $this->cancelRename();
            return;
        }

        $round->update(['name' => $newName]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'exam_round_renamed',
            'target_type' => 'exam_round',
            'target_id' => $round->id,
            'old_values' => ['name' => $oldName],
            'new_values' => ['name' => $newName],
        ]);

        $this->cancelRename();
        $this->dispatch('notify', type: 'success', message: 'تمت إعادة تسمية الجولة.');
    }

    public function render()
    {
        $mobileRoundId = (int) SystemSetting::get('mobile_exam_round_id', 0);

        $rounds = ExamRound::query()
            ->select('exam_rounds.*')
            ->withCount('exams')
            ->withCount([
                'exams as approved_exams_count' => fn($q) => $q->where('status', ExamStatus::Approved),
            ])
            ->addSelect([
                'students_count' => Exam::query()
                    ->selectRaw('count(distinct student_id)')
                    ->whereColumn('exam_round_id', 'exam_rounds.id'),
                'average_score' => Exam::query()
                    ->selectRaw('avg(total_score)')
                    ->whereColumn('exam_round_id', 'exam_rounds.id')
                    ->whereNotNull('total_score'),
                'first_exam_at' => Exam::query()
                    ->selectRaw('min(started_at)')
                    ->whereColumn('exam_round_id', 'exam_rounds.id'),
                'last_exam_at' => Exam::query()
                    ->selectRaw('max(started_at)')
                    ->whereColumn('exam_round_id', 'exam_rounds.id'),
            ])
            ->orderByDesc('id')
            ->get();

        return view('livewire.admin.exam-rounds.index', [
            'rounds' => $rounds,
            'mobileRoundId' => $mobileRoundId,
            'canRenameRounds' => $this->canRenameRounds(),
        ]);
    }

    private function canRenameRounds(): bool
    {
        $user = Auth::user();
        return $user->role === UserRole::SuperAdmin || $user->is_super_admin;
    }
}
