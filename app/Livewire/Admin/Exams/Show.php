<?php

namespace App\Livewire\Admin\Exams;

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamRound;
use App\Models\User;
use App\Services\AuthoritativeExamResolver;
use App\Services\ExamApprovalService;
use App\Services\ScoreCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('تفاصيل الاختبار')]
class Show extends Component
{
    public Exam $exam;

    // Edit mode. Persisted in the URL so the list page can link straight into
    // the form (?edit=1) instead of making the admin click twice.
    #[Url(as: 'edit')]
    public bool $editing = false;

    public string $editExaminerId = '';
    public string $editRoundId = '';
    public string $editPartsCount = '';
    public string $editNewParts = '';
    public string $editRulingsScore = '';
    public string $editStartedAt = '';
    public string $editCompletedAt = '';

    // Keyed by exam_questions.id → ['errors_count', 'warnings_count', 'continuations_count'].
    // final_score is never edited directly; it is recomputed from these counts.
    public array $editQuestions = [];

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

        // Deep-linked into edit mode (?edit=1) — the form needs its state now.
        if ($this->editing) {
            $this->fillEditState();
        }
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
            'user_id'    => Auth::user()->id,
            'exam_id'    => $this->exam->id,
            'action'     => $newStatus === ExamStatus::Approved ? 'exam_approved' : 'exam_excluded',
            'old_values' => ['status' => $oldStatus?->value],
            'new_values' => ['status' => $newStatus->value],
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

    // ── Edit ────────────────────────────────────────────────────────────────

    public function startEdit(): void
    {
        $this->fillEditState();
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->resetValidation();
        $this->editing = false;
        $this->reset([
            'editExaminerId', 'editRoundId', 'editPartsCount', 'editNewParts',
            'editRulingsScore', 'editStartedAt', 'editCompletedAt', 'editQuestions',
        ]);
    }

    private function fillEditState(): void
    {
        $this->editExaminerId   = (string) ($this->exam->examiner_id ?? '');
        $this->editRoundId      = (string) ($this->exam->exam_round_id ?? '');
        $this->editPartsCount   = $this->exam->parts_count !== null ? (string) (float) $this->exam->parts_count : '';
        $this->editNewParts     = $this->exam->new_memorization_parts !== null ? (string) (float) $this->exam->new_memorization_parts : '';
        $this->editRulingsScore = $this->exam->rulings_score !== null ? (string) (float) $this->exam->rulings_score : '';
        $this->editStartedAt    = $this->exam->started_at?->format('Y-m-d\TH:i') ?? '';
        $this->editCompletedAt  = $this->exam->completed_at?->format('Y-m-d\TH:i') ?? '';

        $this->editQuestions = $this->exam->questions
            ->mapWithKeys(fn($q) => [$q->id => [
                'errors_count'        => (string) $q->errors_count,
                'warnings_count'      => (string) $q->warnings_count,
                'continuations_count' => (string) $q->continuations_count,
            ]])
            ->all();
    }

    public function saveEdit(AuthoritativeExamResolver $authoritative): void
    {
        $this->validate([
            'editExaminerId'   => ['required', Rule::exists('users', 'id')->where('role', UserRole::Examiner->value)],
            'editRoundId'      => ['nullable', 'exists:exam_rounds,id'],
            'editPartsCount'   => ['required', 'numeric', 'min:0', 'max:30'],
            'editNewParts'     => ['required', 'numeric', 'min:0', 'max:30', 'lte:editPartsCount'],
            'editRulingsScore' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'editStartedAt'    => ['nullable', 'date'],
            'editCompletedAt'  => ['nullable', 'date', 'after_or_equal:editStartedAt'],
            'editQuestions.*.errors_count'        => ['required', 'integer', 'min:0', 'max:999'],
            'editQuestions.*.warnings_count'      => ['required', 'integer', 'min:0', 'max:999'],
            'editQuestions.*.continuations_count' => ['required', 'integer', 'min:0', 'max:999'],
        ], [
            'editExaminerId.required'        => 'المختبر مطلوب.',
            'editExaminerId.exists'          => 'المختبر المحدد غير صالح.',
            'editRoundId.exists'             => 'الجولة المحددة غير موجودة.',
            'editPartsCount.required'        => 'عدد الأجزاء مطلوب.',
            'editPartsCount.max'             => 'عدد الأجزاء لا يتجاوز 30.',
            'editNewParts.required'          => 'أجزاء الحفظ الجديد مطلوبة.',
            'editNewParts.lte'               => 'أجزاء الحفظ الجديد لا يمكن أن تتجاوز إجمالي الأجزاء.',
            'editRulingsScore.max'           => 'درجة الأحكام من 0 إلى 10.',
            'editCompletedAt.after_or_equal' => 'تاريخ الانتهاء يجب ألا يسبق تاريخ البدء.',
            'editQuestions.*.errors_count.required'        => 'أدخل عدد الفتحات.',
            'editQuestions.*.warnings_count.required'      => 'أدخل عدد التنبيهات.',
            'editQuestions.*.continuations_count.required' => 'أدخل عدد الحركات.',
            'editQuestions.*.*.integer' => 'القيمة يجب أن تكون رقماً صحيحاً.',
            'editQuestions.*.*.min'     => 'القيمة لا يمكن أن تكون سالبة.',
        ]);

        $oldQuestions = [];
        $newQuestions = [];

        // Rewrite each question's counts and re-derive its final_score from them,
        // so the stored score can never drift from the counts an admin sees.
        foreach ($this->exam->questions as $question) {
            $row = $this->editQuestions[$question->id] ?? null;
            if ($row === null) {
                continue;
            }

            $errors        = (int) $row['errors_count'];
            $warnings      = (int) $row['warnings_count'];
            $continuations = (int) $row['continuations_count'];

            $before = [
                'errors_count'        => (int) $question->errors_count,
                'warnings_count'      => (int) $question->warnings_count,
                'continuations_count' => (int) $question->continuations_count,
                'final_score'         => (float) $question->final_score,
            ];
            $after = [
                'errors_count'        => $errors,
                'warnings_count'      => $warnings,
                'continuations_count' => $continuations,
                'final_score'         => ScoreCalculator::questionScore($errors, $warnings, $continuations),
            ];

            if ($before !== $after) {
                $question->update($after);
                $oldQuestions[$question->question_number] = $before;
                $newQuestions[$question->question_number] = $after;
            }
        }

        $rulingsScore = $this->editRulingsScore === '' ? null : (float) $this->editRulingsScore;

        $updates = [
            'examiner_id'            => (int) $this->editExaminerId,
            'exam_round_id'          => $this->editRoundId === '' ? null : (int) $this->editRoundId,
            'parts_count'            => (float) $this->editPartsCount,
            'new_memorization_parts' => (float) $this->editNewParts,
            'rulings_score'          => $rulingsScore,
            'started_at'             => $this->editStartedAt === '' ? null : $this->editStartedAt,
            'completed_at'           => $this->editCompletedAt === '' ? null : $this->editCompletedAt,
        ];

        // A running exam has no final result yet — leave total_score alone until it
        // completes. For terminal exams the total always follows the edited counts.
        if ($this->exam->status !== ExamStatus::InProgress) {
            $updates['total_score'] = ScoreCalculator::totalScore(
                $this->exam->questions->map(fn($q) => [
                    'errors_count'        => (int) ($this->editQuestions[$q->id]['errors_count'] ?? $q->errors_count),
                    'warnings_count'      => (int) ($this->editQuestions[$q->id]['warnings_count'] ?? $q->warnings_count),
                    'continuations_count' => (int) ($this->editQuestions[$q->id]['continuations_count'] ?? $q->continuations_count),
                ])->all(),
                $rulingsScore ?? 0,
            );
        }

        $oldValues = $this->trackedValues();

        $this->exam->update($updates);
        $this->exam->refresh()->load(['student', 'examiner', 'round', 'questions']);

        // Scores/round may have moved — re-settle which exam counts for the student.
        $authoritative->refreshFor($this->exam->student_id);
        $this->exam->refresh();

        // Log only what actually moved, so the audit trail stays readable.
        $newValues   = $this->trackedValues();
        $changedKeys = array_keys(array_filter(
            $newValues,
            fn($value, $key) => $value !== $oldValues[$key],
            ARRAY_FILTER_USE_BOTH,
        ));

        if ($changedKeys || $newQuestions) {
            $loggedOld = array_intersect_key($oldValues, array_flip($changedKeys));
            $loggedNew = array_intersect_key($newValues, array_flip($changedKeys));

            if ($newQuestions) {
                $loggedOld['questions'] = $oldQuestions;
                $loggedNew['questions'] = $newQuestions;
            }

            AuditLog::create([
                'user_id'    => Auth::user()->id,
                'exam_id'    => $this->exam->id,
                'action'     => 'exam_updated',
                'old_values' => $loggedOld,
                'new_values' => $loggedNew,
            ]);

            $this->dispatch('notify', type: 'success', message: 'تم حفظ تعديلات الاختبار.');
        } else {
            $this->dispatch('notify', type: 'info', message: 'لا توجد تغييرات لحفظها.');
        }

        $this->cancelEdit();
    }

    // The exam-level fields the edit form can touch, normalized for diffing
    // and for the audit log payload.
    private function trackedValues(): array
    {
        return [
            'examiner_id'            => $this->exam->examiner_id,
            'exam_round_id'          => $this->exam->exam_round_id,
            'parts_count'            => $this->exam->parts_count !== null ? (float) $this->exam->parts_count : null,
            'new_memorization_parts' => $this->exam->new_memorization_parts !== null ? (float) $this->exam->new_memorization_parts : null,
            'rulings_score'          => $this->exam->rulings_score !== null ? (float) $this->exam->rulings_score : null,
            'total_score'            => $this->exam->total_score !== null ? (float) $this->exam->total_score : null,
            'started_at'             => $this->exam->started_at?->toDateTimeString(),
            'completed_at'           => $this->exam->completed_at?->toDateTimeString(),
        ];
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
            'user_id'    => Auth::user()->id,
            'exam_id'    => $this->exam->id,
            'action'     => 'exam_pinned_authoritative',
            'new_values' => ['is_authoritative' => true],
        ]);

        $this->exam->refresh();
        $this->dispatch('notify', type: 'success', message: 'تم تثبيت هذا الاختبار كنتيجة معتمدة.');
    }

    // Drop the manual pin and fall back to newest-wins.
    public function unpin(AuthoritativeExamResolver $resolver): void
    {
        $resolver->unpin($this->exam);

        AuditLog::create([
            'user_id'    => Auth::user()->id,
            'exam_id'    => $this->exam->id,
            'action'     => 'exam_unpinned_authoritative',
            'old_values' => ['is_authoritative' => true],
        ]);

        $this->exam->refresh();
        $this->dispatch('notify', type: 'success', message: 'تم إلغاء التثبيت — تُعتمد النتيجة الأحدث تلقائياً.');
    }

    // Live preview of the total the current form state would produce, shown
    // beside the inputs so the admin sees the effect before saving.
    private function previewTotal(): ?float
    {
        if (! $this->editing || $this->exam->status === ExamStatus::InProgress) {
            return null;
        }

        $questions = $this->exam->questions->map(fn($q) => [
            'errors_count'        => (int) ($this->editQuestions[$q->id]['errors_count'] ?? $q->errors_count),
            'warnings_count'      => (int) ($this->editQuestions[$q->id]['warnings_count'] ?? $q->warnings_count),
            'continuations_count' => (int) ($this->editQuestions[$q->id]['continuations_count'] ?? $q->continuations_count),
        ])->all();

        return ScoreCalculator::totalScore(
            $questions,
            is_numeric($this->editRulingsScore) ? (float) $this->editRulingsScore : 0,
        );
    }

    public function render()
    {
        return view('livewire.admin.exams.show', [
            'examiners' => $this->editing
                ? User::where('role', UserRole::Examiner)
                    ->orderBy('first_name')
                    ->get(['id', 'first_name', 'second_name', 'third_name', 'family_name'])
                : collect(),
            'rounds' => $this->editing
                ? ExamRound::orderByDesc('id')->get(['id', 'name'])
                : collect(),
            'previewTotal' => $this->previewTotal(),
        ]);
    }
}
