<?php

namespace App\Livewire\Examiner;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ReexamPermit;
use App\Models\Student;
use App\Services\ScoreCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// Flow: search → setup → question(1→2→3) → rulings → summary → saved → (reset)
#[Layout('layouts.app')]
#[Title('جلسة الاختبار')]
class ExamSession extends Component
{
    // ── State machine ─────────────────────────────────────
    public string $step = 'search'; // search|setup|question|rulings|summary|saved

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

    // ── Exam setup ─────────────────────────────────────────
    public string $examType    = '';
    public bool   $needsPermit = false;
    public string $permitCode  = '';

    // ── Active exam ────────────────────────────────────────
    public ?int $examId          = null;
    public int  $currentQuestion = 1;

    // Per-question state — keys match DB column names (errors_count, etc.)
    public array $questions = [
        1 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
        2 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
        3 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
    ];

    // Map deduction button type → question array key
    private const DEDUCTION_KEY = [
        'error'        => 'errors_count',
        'warning'      => 'warnings_count',
        'continuation' => 'continuations_count',
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
        if (strlen($this->studentSearch) < 2) return [];

        return Student::where(fn($q) =>
            $q->where('national_id', 'like', "%{$this->studentSearch}%")
              ->orWhere('first_name',  'like', "%{$this->studentSearch}%")
              ->orWhere('family_name', 'like', "%{$this->studentSearch}%")
        )->limit(10)->get()->toArray();
    }

    public function selectStudent(int $studentId): void
    {
        $this->selectedStudentId = $studentId;
        $this->studentSearch     = '';

        // BR-REEX-01: Re-exam needs a permit if student has an approved exam
        $this->needsPermit = Exam::where('student_id', $studentId)
            ->where('is_approved', true)
            ->exists();

        $this->step = 'setup';
    }

    public function quickAddStudent(): void
    {
        $this->validate([
            'add_national_id' => ['required', 'string', 'unique:students,national_id'],
            'add_first_name'  => ['required', 'string', 'max:50'],
            'add_second_name' => ['nullable', 'string', 'max:50'],
            'add_third_name'  => ['nullable', 'string', 'max:50'],
            'add_family_name' => ['required', 'string', 'max:50'],
        ], [
            'add_national_id.required' => 'رقم الهوية مطلوب.',
            'add_national_id.unique'   => 'رقم الهوية موجود مسبقاً — ابحث عنه.',
            'add_first_name.required'  => 'الاسم الأول مطلوب.',
            'add_family_name.required' => 'اسم العائلة مطلوب.',
        ]);

        $student = Student::create([
            'national_id'  => $this->add_national_id,
            'first_name'   => $this->add_first_name,
            'second_name'  => $this->add_second_name ?: null,
            'third_name'   => $this->add_third_name ?: null,
            'family_name'  => $this->add_family_name,
        ]);

        $this->resetAddStudentForm();
        $this->selectStudent($student->id);
    }

    // ──────────────────────────────────────────────────────
    // Step: setup → start exam
    // ──────────────────────────────────────────────────────

    public function startExam(): void
    {
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
        $this->questions       = [
            1 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
            2 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
            3 => ['errors_count' => 0, 'warnings_count' => 0, 'continuations_count' => 0, 'history' => []],
        ];
        $this->step = 'question';
    }

    // ──────────────────────────────────────────────────────
    // Step: question — deduction buttons
    // ──────────────────────────────────────────────────────

    public function pressDeduction(string $type): void
    {
        if (! isset(self::DEDUCTION_KEY[$type])) return;

        $q = $this->questions[$this->currentQuestion];

        // BR-EXAM-04: stop at zero
        $currentScore = ScoreCalculator::questionScore(
            $q['errors_count'],
            $q['warnings_count'],
            $q['continuations_count'],
        );

        $deductionAmounts = ['error' => 2, 'warning' => 1, 'continuation' => 0.5];
        if ($currentScore - $deductionAmounts[$type] < 0) return;

        $key                      = self::DEDUCTION_KEY[$type];
        $q[$key]++;
        $q['history'][]           = $type;
        $this->questions[$this->currentQuestion] = $q;
    }

    public function pressUndo(): void
    {
        $q = $this->questions[$this->currentQuestion];

        if (empty($q['history'])) return;

        $lastAction = array_pop($q['history']);
        $key        = self::DEDUCTION_KEY[$lastAction];
        $q[$key]    = max(0, $q[$key] - 1);

        $this->questions[$this->currentQuestion] = $q;
    }

    public function nextQuestion(): void
    {
        $this->saveCurrentQuestionToDB();

        if ($this->currentQuestion < 3) {
            $this->currentQuestion++;
        } else {
            $this->step = 'rulings';
        }
    }

    // ──────────────────────────────────────────────────────
    // Step: rulings
    // ──────────────────────────────────────────────────────

    public function submitRulings(): void
    {
        $this->validate([
            'rulingsScore' => ['required', 'integer', 'min:0', 'max:10'],
        ], [
            'rulingsScore.required' => 'أدخل درجة الأحكام.',
            'rulingsScore.min'      => 'درجة الأحكام لا تقل عن 0.',
            'rulingsScore.max'      => 'درجة الأحكام لا تتجاوز 10.',
        ]);

        Exam::where('id', $this->examId)->update(['rulings_score' => $this->rulingsScore]);

        $this->step = 'summary';
    }

    // ──────────────────────────────────────────────────────
    // Step: summary → save
    // ──────────────────────────────────────────────────────

    public function saveExam(): void
    {
        $exam       = Exam::findOrFail($this->examId);
        $totalScore = ScoreCalculator::totalScore($this->questions, $this->rulingsScore);
        $isPassing  = ScoreCalculator::isPassing($totalScore);

        // Save each question's final score
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
    // Auto-save (BR-EXAM-08: every 10 seconds on web)
    // ──────────────────────────────────────────────────────

    public function autosave(): void
    {
        if ($this->step === 'question' && $this->examId) {
            $this->saveCurrentQuestionToDB();
        }
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
    public function currentQuestionScore(): float
    {
        $q = $this->questions[$this->currentQuestion];
        return ScoreCalculator::questionScore($q['errors_count'], $q['warnings_count'], $q['continuations_count']);
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
            'examiner' => Auth::user(),
        ]);
    }

    // ──────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────

    private function saveCurrentQuestionToDB(): void
    {
        if (! $this->examId) return;

        $q = $this->questions[$this->currentQuestion];

        ExamQuestion::where('exam_id', $this->examId)
            ->where('question_number', $this->currentQuestion)
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

    private function loadExam(Exam $exam): void
    {
        $this->examId            = $exam->id;
        $this->selectedStudentId = $exam->student_id;
        $this->examType          = $exam->exam_type->value;
        $this->currentQuestion   = 1;

        foreach ($exam->questions as $q) {
            $num = $q->question_number;
            $this->questions[$num] = [
                'errors_count'        => $q->errors_count,
                'warnings_count'      => $q->warnings_count,
                'continuations_count' => $q->continuations_count,
                'history'             => [], // history is not persisted
            ];
        }

        $this->step = 'question';
    }

    private function resetAddStudentForm(): void
    {
        $this->add_national_id = '';
        $this->add_first_name  = '';
        $this->add_second_name = '';
        $this->add_third_name  = '';
        $this->add_family_name = '';
        $this->showAddStudent  = false;
    }
}
