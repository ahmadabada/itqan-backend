<?php

namespace App\Livewire\Admin\Exams;

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Services\AuthoritativeExamResolver;
use App\Services\ExamApprovalService;
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
            'round',
            'questions',
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
        $this->exam->update([
            'status'      => $newStatus,
            'is_approved' => $newStatus === ExamStatus::Approved,
        ]);

        if ($newStatus === ExamStatus::Approved) {
            app(ExamApprovalService::class)->demoteOthersInRound($this->exam);
        }

        AuditLog::create([
            'user_id'     => Auth::user()->id,
            'action'      => $newStatus === ExamStatus::Approved ? 'exam_approved' : 'exam_excluded',
            'target_type' => 'exam',
            'target_id'   => $this->exam->id,
            'old_values'  => ['status' => $oldStatus?->value],
            'new_values'  => ['status' => $newStatus->value],
        ]);

        // Excluding the counted exam must promote the next one; approving another
        // may change which is newest-and-approved. Let the resolver settle it.
        app(AuthoritativeExamResolver::class)->refreshFor($this->exam->student_id);
        $this->exam->refresh();

        $this->dispatch(
            'notify',
            type: 'success',
            message: $newStatus === ExamStatus::Approved ? 'تم اعتماد الاختبار.' : 'تم استبعاد الاختبار.',
        );
    }

    // Pin this exam as the student's counted result, overriding newest-wins.
    public function pin(AuthoritativeExamResolver $resolver): void
    {
        if ($this->exam->status !== ExamStatus::Approved) {
            $this->dispatch('notify', type: 'error', message: 'لا يمكن تثبيت اختبار غير معتمد.');
            return;
        }

        $resolver->pin($this->exam, Auth::user()->id);

        AuditLog::create([
            'user_id'     => Auth::user()->id,
            'action'      => 'exam_pinned_authoritative',
            'target_type' => 'exam',
            'target_id'   => $this->exam->id,
            'new_values'  => ['is_authoritative' => true],
        ]);

        $this->exam->refresh();
        $this->dispatch('notify', type: 'success', message: 'تم تثبيت هذا الاختبار كنتيجة معتمدة.');
    }

    // Drop the manual pin and fall back to newest-wins.
    public function unpin(AuthoritativeExamResolver $resolver): void
    {
        $resolver->unpin($this->exam);

        AuditLog::create([
            'user_id'     => Auth::user()->id,
            'action'      => 'exam_unpinned_authoritative',
            'target_type' => 'exam',
            'target_id'   => $this->exam->id,
            'old_values'  => ['is_authoritative' => true],
        ]);

        $this->exam->refresh();
        $this->dispatch('notify', type: 'success', message: 'تم إلغاء التثبيت — تُعتمد النتيجة الأحدث تلقائياً.');
    }

    public function render()
    {
        return view('livewire.admin.exams.show');
    }
}
