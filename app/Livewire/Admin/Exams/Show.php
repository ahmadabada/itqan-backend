<?php

namespace App\Livewire\Admin\Exams;

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('تفاصيل الاختبار')]
class Show extends Component
{
    public Exam $exam;

    public function mount(Exam $exam): void
    {
        $user = Auth::user();
        if ($user->role === UserRole::Examiner) {
            $this->redirect(route('examiner.exams'), navigate: true);
            return;
        }

        $this->exam = $exam->load([
            'student',
            'examiner',
            'questions.recitationQuestion',
            'reexamPermit',
        ]);
    }

    public function approve(): void
    {
        $this->setStatus(ExamStatus::Approved);
    }

    public function exclude(): void
    {
        $this->setStatus(ExamStatus::Excluded);
    }

    private function setStatus(ExamStatus $newStatus): void
    {
        if ($this->exam->status === ExamStatus::InProgress) {
            $this->dispatch('notify', type: 'error', message: 'لا يمكن تغيير حالة اختبار جارٍ.');
            return;
        }
        if ($this->exam->status === $newStatus) {
            return;
        }

        $oldStatus = $this->exam->status;
        $this->exam->update(['status' => $newStatus]);

        AuditLog::create([
            'user_id'     => Auth::user()->id,
            'action'      => $newStatus === ExamStatus::Approved ? 'exam_approved' : 'exam_excluded',
            'target_type' => 'exam',
            'target_id'   => $this->exam->id,
            'old_values'  => ['status' => $oldStatus?->value],
            'new_values'  => ['status' => $newStatus->value],
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            message: $newStatus === ExamStatus::Approved ? 'تم اعتماد الاختبار.' : 'تم استبعاد الاختبار.',
        );
    }

    public function render()
    {
        return view('livewire.admin.exams.show');
    }
}
