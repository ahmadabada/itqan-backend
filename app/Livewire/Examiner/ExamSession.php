<?php

namespace App\Livewire\Examiner;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ReexamPermit;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Services\ArabicSearch;
use App\Services\ScoreCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// Flow: search → setup → active (Alpine handles Q1/Q2/Q3 + rulings + summary entirely client-side) → saved
#[Layout('layouts.app')]
#[Title('جلسة الاختبار')]
class ExamSession extends Component
{
    // ── State machine ─────────────────────────────────────
    // Server only knows 4 states; question/rulings/summary are client sub-states inside `active`
    public string $step = 'search'; // search|setup|active|saved

    // ── Student selection ──────────────────────────────────
    public string $studentSearch     = '';
    public ?int   $selectedStudentId = null;
    public bool   $showAddStudent    = false;

    // Quick-add student fields (BR-STD-04: examiner can add student manually)
    public string $add_national_id  = '';
    public string $add_first_name   = '';
    public string $add_second_name  = '';
    public string $add_third_name   = '';
    public string $add_family_name  = '';
    public string $add_gender       = '';

    // ── Exam setup ─────────────────────────────────────────
    public string $examType        = '';
    public bool   $needsPermit     = false;
    public string $permitCode      = '';
    public string $inlineNationalId = ''; // populated when selected student has no national_id

    // ── Active exam ────────────────────────────────────────
    public ?int $examId          = null;
    public int  $currentQuestion = 1;

    // Per-question state — keys match DB column names (errors_count, etc.)
    public array $questions = [
        1 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
        2 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
        3 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
    ];

    // ── Rulings ────────────────────────────────────────────
    public int $rulingsScore = 0;

    // ──────────────────────────────────────────────────────
    // Mount
    // ──────────────────────────────────────────────────────

    public function mount(): void
    {
        $user = Auth::user();
        if ($user->role !== UserRole::Examiner) {
            $this->redirect(route('admin.dashboard'), navigate: true);
            return;
        }

        // BR-EXAM-09: Resume in-progress exam if any
        $inProgress = Exam::where('examiner_id', $user->id)
            ->where('status', ExamStatus::InProgress)
            ->with('questions')
            ->latest()
            ->first();

        if ($inProgress) {
            $this->loadExam($inProgress);
        }
    }

    // ──────────────────────────────────────────────────────
    // Step: search
    // ──────────────────────────────────────────────────────

    #[Computed]
    public function searchResults(): array
    {
        if (mb_strlen($this->studentSearch) < 2) return [];

        $examinerGender = Auth::user()->gender?->value;

        $query = Student::query()
            // BR: Examiners only see/assess students of the same gender
            ->where('gender', $examinerGender);

        ArabicSearch::applyTo(
            $query,
            $this->studentSearch,
            ['first_name', 'second_name', 'third_name', 'family_name'],
            ['national_id'],
        );

        return $query->limit(10)->get()->toArray();
    }

    public function selectStudent(int $studentId): void
    {
        $student        = Student::find($studentId);
        $examinerGender = Auth::user()->gender?->value;

        if (! $student) {
            $this->dispatch('notify', type: 'danger', message: 'الطالب غير موجود.');
            return;
        }

        // BR: Examiners can only assess students of the same gender
        if ($student->gender?->value !== $examinerGender) {
            $this->dispatch('notify', type: 'danger',
                message: 'لا يمكن إجراء اختبار لطالب من جنس مختلف.');
            return;
        }

        $this->selectedStudentId = $studentId;
        $this->studentSearch     = '';
        $this->inlineNationalId  = ''; // examiner will fill it in setup if missing

        // BR-REEX-01: Re-exam needs a permit if student has an approved exam
        $this->needsPermit = Exam::where('student_id', $studentId)
            ->where('is_approved', true)
            ->exists();

        $this->step = 'setup';
    }

    public function quickAddStudent(): void
    {
        $examinerGender = Auth::user()->gender?->value;

        // BR-EXAM: From the examiner UI, national_id IS required (can't sit an exam without it).
        // Gender must match the examiner's gender — examiners can only test same-gender students.
        $this->validate([
            'add_national_id' => ['required', 'digits:9', 'unique:students,national_id'],
            'add_first_name'  => ['required', 'string', 'max:50'],
            'add_second_name' => ['nullable', 'string', 'max:50'],
            'add_third_name'  => ['nullable', 'string', 'max:50'],
            'add_family_name' => ['required', 'string', 'max:50'],
            'add_gender'      => ['required', 'in:' . $examinerGender],
        ], [
            'add_national_id.required' => 'رقم الهوية مطلوب لإجراء اختبار.',
            'add_national_id.digits'   => 'رقم الهوية يجب أن يكون 9 أرقام.',
            'add_national_id.unique'   => 'رقم الهوية موجود مسبقاً — ابحث عنه.',
            'add_first_name.required'  => 'الاسم الأول مطلوب.',
            'add_family_name.required' => 'اسم العائلة مطلوب.',
            'add_gender.required'      => 'الجنس مطلوب.',
            'add_gender.in'            => $examinerGender === 'male'
                ? 'لا يمكنك إضافة طالبة — الإضافة محصورة بالذكور.'
                : 'لا يمكنك إضافة طالب ذكر — الإضافة محصورة بالإناث.',
        ]);

        $student = Student::create([
            'national_id'  => $this->add_national_id,
            'first_name'   => $this->add_first_name,
            'second_name'  => $this->add_second_name ?: null,
            'third_name'   => $this->add_third_name ?: null,
            'family_name'  => $this->add_family_name,
            'gender'       => $this->add_gender,
        ]);

        $this->resetAddStudentForm();
        $this->selectStudent($student->id);
    }

    // ──────────────────────────────────────────────────────
    // Step: setup → start exam
    // ──────────────────────────────────────────────────────

    public function startExam(): void
    {
        $student = Student::findOrFail($this->selectedStudentId);

        // BR-EXAM: a student MUST have a national_id to sit an exam.
        // If missing, validate + save the inline-entered ID before continuing.
        if (empty($student->national_id)) {
            $this->validate([
                'inlineNationalId' => ['required', 'digits:9', 'unique:students,national_id'],
            ], [
                'inlineNationalId.required' => 'رقم الهوية مطلوب لإجراء الاختبار.',
                'inlineNationalId.digits'   => 'رقم الهوية يجب أن يكون 9 أرقام.',
                'inlineNationalId.unique'   => 'رقم الهوية مسجّل لطالب آخر.',
            ]);

            $student->update(['national_id' => $this->inlineNationalId]);
        }

        $this->validate([
            'examType' => ['required', 'in:full_quran,half_quran'],
        ], [
            'examType.required' => 'اختر نوع الاختبار.',
        ]);

        // BR-REEX-01: Validate permit if re-exam
        if ($this->needsPermit) {
            $permit = ReexamPermit::where('permit_code', $this->permitCode)
                ->where('student_id', $this->selectedStudentId)
                ->first();

            if (! $permit || ! $permit->isValid()) {
                $this->addError('permitCode', 'رمز الإذن غير صالح أو منتهي الصلاحية.');
                return;
            }

            $permit->update(['is_used' => true, 'used_at' => now()]);
        }

        // Determine attempt number (BR-REEX-06)
        $attemptNumber = (Exam::where('student_id', $this->selectedStudentId)->max('attempt_number') ?? 0) + 1;

        $exam = Exam::create([
            'student_id'     => $this->selectedStudentId,
            'examiner_id'    => Auth::user()->id,
            'exam_type'      => $this->examType,
            'source'         => ExamSource::Web,
            'status'         => ExamStatus::InProgress,
            'attempt_number' => $attemptNumber,
            'is_approved'    => false,
            'started_at'     => now(),
        ]);

        // Create 3 question rows upfront
        foreach ([1, 2, 3] as $qNum) {
            ExamQuestion::create([
                'exam_id'             => $exam->id,
                'question_number'     => $qNum,
                'errors_count'        => 0,
                'warnings_count'      => 0,
                'continuations_count' => 0,
                'final_score'         => config('exam.score_per_question', 30),
            ]);
        }

        $this->examId          = $exam->id;
        $this->currentQuestion = 1;
        $this->rulingsScore    = 0;
        $this->questions       = [
            1 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
            2 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
            3 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
        ];
        $this->step = 'active';
    }

    // ──────────────────────────────────────────────────────
    // Active step — Alpine manages everything client-side (Q1/Q2/Q3 + rulings + summary)
    // Server receives ONE sync payload every 20s with questions + rulings (BR-EXAM-08)
    // ──────────────────────────────────────────────────────

    public function syncExam(array $questions, float $rulingsScore = 0): void
    {
        foreach ($questions as $qNum => $q) {
            $num = (int) $qNum;
            if (! isset($this->questions[$num])) continue;
            $this->questions[$num] = [
                'errors_count'        => (int) ($q['errors_count'] ?? 0),
                'warnings_count'      => (int) ($q['warnings_count'] ?? 0),
                'continuations_count' => (int) ($q['continuations_count'] ?? 0),
                'history'             => array_values($q['history'] ?? []),
            ];
        }

        $this->rulingsScore = max(0, min(10, $rulingsScore));

        if ($this->examId) {
            $this->saveAllQuestionsToDB();
            Exam::where('id', $this->examId)->update(['rulings_score' => $this->rulingsScore]);
        }
    }

    // ──────────────────────────────────────────────────────
    // Final save — only server call from active step
    // ──────────────────────────────────────────────────────

    public function saveExam(): void
    {
        $exam       = Exam::findOrFail($this->examId);
        $totalScore = ScoreCalculator::totalScore($this->questions, $this->rulingsScore);
        $isPassing  = ScoreCalculator::isPassing($totalScore);

        foreach ($this->questions as $qNum => $q) {
            $finalScore = ScoreCalculator::questionScore(
                $q['errors_count'],
                $q['warnings_count'],
                $q['continuations_count'],
            );

            ExamQuestion::where('exam_id', $exam->id)
                ->where('question_number', $qNum)
                ->update([
                    'errors_count'        => $q['errors_count'],
                    'warnings_count'      => $q['warnings_count'],
                    'continuations_count' => $q['continuations_count'],
                    'final_score'         => $finalScore,
                ]);
        }

        // BR-CONF-01: No conflict = auto-approve
        $exam->update([
            'status'       => ExamStatus::Approved,
            'total_score'  => $totalScore,
            'is_passed'    => $isPassing,
            'is_approved'  => true,
            'completed_at' => now(),
        ]);

        // BR-REEX-08: One approved exam per student at a time
        Exam::where('student_id', $exam->student_id)
            ->where('id', '!=', $exam->id)
            ->where('is_approved', true)
            ->update(['is_approved' => false]);

        $this->step = 'saved';
    }

    // ──────────────────────────────────────────────────────
    // Reset
    // ──────────────────────────────────────────────────────

    public function resetSession(): void
    {
        $this->step              = 'search';
        $this->studentSearch     = '';
        $this->selectedStudentId = null;
        $this->showAddStudent    = false;
        $this->examType          = '';
        $this->needsPermit       = false;
        $this->permitCode        = '';
        $this->inlineNationalId  = '';
        $this->examId            = null;
        $this->currentQuestion   = 1;
        $this->rulingsScore      = 0;
        $this->questions         = [
            1 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
            2 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
            3 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
        ];
        $this->resetValidation();
    }

    // ──────────────────────────────────────────────────────
    // Computed helpers
    // ──────────────────────────────────────────────────────

    #[Computed]
    public function selectedStudent(): ?Student
    {
        return $this->selectedStudentId ? Student::find($this->selectedStudentId) : null;
    }

    #[Computed]
    public function totalScore(): float
    {
        return ScoreCalculator::totalScore($this->questions, $this->rulingsScore);
    }

    #[Computed]
    public function isPassing(): bool
    {
        return ScoreCalculator::isPassing($this->totalScore);
    }

    // ──────────────────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.examiner.exam-session', [
            'examiner'        => Auth::user(),
            'passingScore'    => (int) SystemSetting::get('passing_score', 60),
            'scorePerQ'       => (int) config('exam.score_per_question', 30),
            'errorDeduction'  => (float) config('exam.deductions.error', 2),
            'warnDeduction'   => (float) config('exam.deductions.warning', 1),
            'contDeduction'   => (float) config('exam.deductions.continuation', 0.5),
        ]);
    }

    // ──────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────

    private function saveAllQuestionsToDB(): void
    {
        foreach ($this->questions as $qNum => $q) {
            ExamQuestion::where('exam_id', $this->examId)
                ->where('question_number', (int) $qNum)
                ->update([
                    'errors_count'        => $q['errors_count'],
                    'warnings_count'      => $q['warnings_count'],
                    'continuations_count' => $q['continuations_count'],
                    'final_score'         => ScoreCalculator::questionScore(
                        $q['errors_count'],
                        $q['warnings_count'],
                        $q['continuations_count'],
                    ),
                ]);
        }
    }

    private function loadExam(Exam $exam): void
    {
        $this->examId            = $exam->id;
        $this->selectedStudentId = $exam->student_id;
        $this->examType          = $exam->exam_type->value;
        $this->currentQuestion   = 1;
        $this->rulingsScore      = (int) ($exam->rulings_score ?? 0);

        foreach ($exam->questions as $q) {
            $num = $q->question_number;
            $this->questions[$num] = [
                'errors_count'        => $q->errors_count,
                'warnings_count'      => $q->warnings_count,
                'continuations_count' => $q->continuations_count,
                'history'             => [], // history is not persisted
            ];
        }

        $this->step = 'active';
    }

    private function resetAddStudentForm(): void
    {
        $this->add_national_id = '';
        $this->add_first_name  = '';
        $this->add_second_name = '';
        $this->add_third_name  = '';
        $this->add_family_name = '';
        $this->add_gender      = '';
        $this->showAddStudent  = false;
    }
}
