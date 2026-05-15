<?php

namespace App\Livewire\Admin\Exams;

use App\Enums\ExamStatus;
use App\Models\AuditLog;
use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('مراجعة التعارضات')]
class PendingReview extends Component
{
    public string $filterConflict = '';

    // BR-CONF-06: Admin resolves conflicts by approving or rejecting
    public function approve(int $examId): void
    {
        $exam = Exam::where('status', ExamStatus::PendingReview)->findOrFail($examId);
        $user = Auth::user();

        $exam->update([
            'status'      => ExamStatus::Approved,
            'is_approved' => true,
        ]);

        // BR-REEX-08: One approved per student
        Exam::where('student_id', $exam->student_id)
            ->where('id', '!=', $exam->id)
            ->where('is_approved', true)
            ->update(['is_approved' => false]);

        // BR-AUDIT-01: Log every approval of a result
        defer(fn () => AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'exam_approved',
            'model_type' => 'Exam',
            'model_id'   => $exam->id,
            'new_values' => json_encode(['status' => ExamStatus::Approved->value, 'is_approved' => true]),
        ]));

        $this->dispatch('notify', type: 'success', message: 'تم اعتماد الاختبار.');
    }

    public function reject(int $examId): void
    {
        $exam = Exam::where('status', ExamStatus::PendingReview)->findOrFail($examId);
        $user = Auth::user();

        $exam->update([
            'status'      => ExamStatus::Rejected,
            'is_approved' => false,
        ]);

        defer(fn () => AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'exam_rejected',
            'model_type' => 'Exam',
            'model_id'   => $exam->id,
            'new_values' => json_encode(['status' => ExamStatus::Rejected->value]),
        ]));

        $this->dispatch('notify', type: 'danger', message: 'تم رفض الاختبار.');
    }

    #[Computed]
    public function exams()
    {
        return Exam::where('status', ExamStatus::PendingReview)
            ->when($this->filterConflict, fn($q) => $q->where('conflict_reason', $this->filterConflict))
            ->with(['student', 'examiner', 'questions'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function totalPending(): int
    {
        return Exam::where('status', ExamStatus::PendingReview)->count();
    }

    public function render(): View
    {
        return view('livewire.admin.exams.pending-review');
    }
}
