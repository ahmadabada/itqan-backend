<?php

namespace App\Livewire\Examiner;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Enums\QuestionGroup;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ReexamPermit;
use App\Models\Student;
use App\Models\SuggestedStudent;
use App\Models\SystemSetting;
use App\Services\ArabicSearch;
use App\Services\ExamQuestionPicker;
use App\Services\ScoreCalculator;
use App\Support\Surah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// Flow: search → setup → (selecting_groups for half_quran) → previewing → active → saved
// Active step has its own client-side sub-steps: question/rulings/summary (Alpine).
#[Layout('layouts.app')]
#[Title('جلسة الاختبار')]
class ExamSession extends Component
{
    // ── State machine ─────────────────────────────────────
    // Server states; question/rulings/summary inside `active` are client-side sub-states.
    public string $step = 'search'; // search|setup|selecting_groups|previewing|active|saved

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
    public string $add_student_zone = '';
    public ?bool  $add_is_recite_before = null;

    // BR-SS-5: autocomplete inside the quick-add form. Searches the
    // pre-staged suggested_students list scoped to the examiner's gender
    // (BR-SS-1). Picking a row pre-fills the add_* fields; the examiner can
    // still edit anything before saving.
    public string $suggestionSearch = '';

    // ── Exam setup ─────────────────────────────────────────
    public string $examType        = '';
    public bool   $needsPermit     = false;
    public string $permitCode      = '';
    public string $inlineNationalId = ''; // populated when selected student has no national_id

    // ── Half-quran group selection (BR-EXAM-11) ────────────
    // The live UI state for the group-selection step is owned by Alpine; this
    // server-side copy is only set when proceedFromGroups() is called and is
    // persisted onto the Exam row on confirmAndStart.
    /** @var array<int> exactly 3 distinct values in 1..6 once selected */
    public array $selectedGroups = [];

    // ── Preview of the picked recitation questions ─────────
    // Tab navigation in `previewing` is Alpine-only — no `previewTab` property
    // here, because no server round-trip should fire when the examiner clicks a tab.
    /** @var array<int, array> position (1..3) → snapshot of the picked RecitationQuestion */
    public array $pickedQuestions = [];

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

        // BR-EXAM-09: Resume in-progress exam if any (hydrate recitation details too).
        $inProgress = Exam::where('examiner_id', $user->id)
            ->where('status', ExamStatus::InProgress)
            ->with('questions.recitationQuestion')
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

    // BR-SS-1 + BR-SS-5: autocomplete suggestions for the quick-add form.
    // Always filtered to the examiner's own gender on the server side.
    #[Computed]
    public function suggestionResults(): array
    {
        if (mb_strlen($this->suggestionSearch) < 2) return [];

        $query = SuggestedStudent::query()->forExaminer(Auth::user());

        ArabicSearch::applyTo(
            $query,
            $this->suggestionSearch,
            ['first_name', 'second_name', 'third_name', 'family_name'],
            ['national_id'],
        );

        return $query->orderBy('family_name')->limit(8)->get()->toArray();
    }

    public function selectSuggestion(int $suggestionId): void
    {
        $s = SuggestedStudent::find($suggestionId);
        if (! $s || $s->gender->value !== Auth::user()->gender?->value) {
            $this->dispatch('notify', type: 'error', message: 'المقترح غير موجود.');
            return;
        }

        // Pre-fill; the examiner can still edit anything before saving. The exam
        // record copies these values — suggested_students is not referenced.
        $this->add_national_id      = $s->national_id ?? '';
        $this->add_first_name       = $s->first_name;
        $this->add_second_name      = $s->second_name ?? '';
        $this->add_third_name       = $s->third_name ?? '';
        $this->add_family_name      = $s->family_name;
        $this->add_gender           = $s->gender->value;
        $this->add_student_zone     = $s->student_zone;
        $this->add_is_recite_before = $s->is_recite_before;
        $this->suggestionSearch     = '';
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
            'add_student_zone'=> ['required', 'string', 'in:East Gaza,West Gaza,North Gaza,South Gaza'],
            'add_is_recite_before' => ['required', 'boolean'],
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
            'add_student_zone.required' => 'منطقة الطالب مطلوبة.',
            'add_student_zone.in'       => 'المنطقة المحددة غير صالحة.',
            'add_is_recite_before.required' => 'حقل هل سبق له التسميع مطلوب.',
            'add_is_recite_before.boolean'  => 'القيمة المحددة لحقل هل سبق له التسميع غير صالحة.',
        ]);

        $student = Student::create([
            'national_id'  => $this->add_national_id,
            'first_name'   => $this->add_first_name,
            'second_name'  => $this->add_second_name ?: null,
            'third_name'   => $this->add_third_name ?: null,
            'family_name'  => $this->add_family_name,
            'gender'       => $this->add_gender,
            'student_zone' => $this->add_student_zone,
            'is_recite_before' => $this->add_is_recite_before,
        ]);

        $this->resetAddStudentForm();
        $this->selectStudent($student->id);
    }

    // ──────────────────────────────────────────────────────
    // Step: setup → groups (half_quran) | previewing (full_quran)
    // ──────────────────────────────────────────────────────

    public function proceedFromSetup(ExamQuestionPicker $picker): void
    {
        $student = Student::findOrFail($this->selectedStudentId);

        // BR-EXAM: a student MUST have a national_id to sit an exam.
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

        // BR-REEX-01: Validate permit but don't mark used yet — wait until confirmAndStart.
        if ($this->needsPermit) {
            $permit = ReexamPermit::where('permit_code', $this->permitCode)
                ->where('student_id', $this->selectedStudentId)
                ->first();

            if (! $permit || ! $permit->isValid()) {
                $this->addError('permitCode', 'رمز الإذن غير صالح أو منتهي الصلاحية.');
                return;
            }
        }

        if ($this->examType === 'half_quran') {
            $this->selectedGroups = [];
            $this->step = 'selecting_groups';
            return;
        }

        // full_quran: pick immediately and jump to preview.
        $this->generatePicks($picker);
    }

    // ──────────────────────────────────────────────────────
    // Step: selecting_groups (half_quran only)
    // ──────────────────────────────────────────────────────
    // Group selection is Alpine-managed; we receive the final 3 groups in one
    // call when the examiner clicks "معاينة الأسئلة". This is the only request.
    public function proceedFromGroups(array $groups, ExamQuestionPicker $picker): void
    {
        $groups = array_values(array_filter(
            array_map('intval', $groups),
            fn($g) => $g >= 1 && $g <= 6
        ));

        if (count($groups) !== 3 || count(array_unique($groups)) !== 3) {
            $this->dispatch('notify', type: 'danger', message: 'اختر 3 مجموعات مختلفة.');
            return;
        }

        $this->selectedGroups = $groups;
        $this->generatePicks($picker);
    }

    // ──────────────────────────────────────────────────────
    // Step: previewing — tab navigation is Alpine-only.
    // Server only sees backFromPreview / confirmAndStart.
    // ──────────────────────────────────────────────────────

    public function backFromPreview(): void
    {
        $this->step = $this->examType === 'half_quran' ? 'selecting_groups' : 'setup';
    }

    public function confirmAndStart(): void
    {
        if (count($this->pickedQuestions) !== 3) {
            $this->dispatch('notify', type: 'danger', message: 'لم يتم توليد أسئلة.');
            return;
        }

        // BR-REEX-01: re-validate permit + mark used now (deferred from setup).
        if ($this->needsPermit) {
            $permit = ReexamPermit::where('permit_code', $this->permitCode)
                ->where('student_id', $this->selectedStudentId)
                ->first();

            if (! $permit || ! $permit->isValid()) {
                $this->addError('permitCode', 'رمز الإذن غير صالح أو منتهي الصلاحية.');
                $this->step = 'setup';
                return;
            }
            $permit->update(['is_used' => true, 'used_at' => now()]);
        }

        $attemptNumber = (Exam::where('student_id', $this->selectedStudentId)->max('attempt_number') ?? 0) + 1;

        $exam = DB::transaction(function () use ($attemptNumber) {
            $exam = Exam::create([
                'student_id'      => $this->selectedStudentId,
                'examiner_id'     => Auth::user()->id,
                'exam_type'       => $this->examType,
                'selected_groups' => $this->examType === 'half_quran' ? $this->selectedGroups : null,
                'source'          => ExamSource::Web,
                'status'          => ExamStatus::InProgress,
                'attempt_number'  => $attemptNumber,
                'is_approved'     => false,
                'started_at'      => now(),
            ]);

            foreach ($this->pickedQuestions as $position => $q) {
                ExamQuestion::create([
                    'exam_id'                => $exam->id,
                    'question_number'        => $position,
                    'recitation_question_id' => $q['recitation_question_id'],
                    'errors_count'           => 0,
                    'warnings_count'         => 0,
                    'continuations_count'    => 0,
                    'final_score'            => config('exam.score_per_question', 30),
                ]);
            }

            return $exam;
        });

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

    /**
     * Pick 3 recitation questions according to current exam_type/selected_groups,
     * stash a display snapshot for the preview/active screens, and switch to `previewing`.
     */
    private function generatePicks(ExamQuestionPicker $picker): void
    {
        try {
            $picked = $this->examType === 'half_quran'
                ? $picker->pickForHalfQuran($this->selectedGroups)
                : $picker->pickForFullQuran();
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'danger', message: $e->getMessage());
            return;
        }

        $this->pickedQuestions = [];
        foreach ($picked as $i => $row) {
            $position = $i + 1;
            $q        = $row['question'];
            $this->pickedQuestions[$position] = [
                'recitation_question_id' => $q->id,
                'group_number'           => $row['group']->value,
                'group_label'            => $row['group']->shortLabel(),
                'group_full_label'       => $row['group']->fullLabel(),
                'start_surah'            => (int) $q->start_surah,
                'start_surah_name'       => Surah::nameFor((int) $q->start_surah) ?? '',
                'start_ayah'             => (int) $q->start_ayah,
                'start_page'             => (int) $q->start_page,
                'end_surah'              => (int) $q->end_surah,
                'end_surah_name'         => Surah::nameFor((int) $q->end_surah) ?? '',
                'end_ayah'               => (int) $q->end_ayah,
                'end_page'               => (int) $q->end_page,
            ];
        }
        $this->step = 'previewing';
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
        $this->selectedGroups    = [];
        $this->pickedQuestions   = [];
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
            'groups'          => QuestionGroup::cases(),
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
        $this->selectedGroups    = $exam->selected_groups ?? [];
        $this->currentQuestion   = 1;
        $this->rulingsScore      = (int) ($exam->rulings_score ?? 0);

        $this->pickedQuestions = [];

        foreach ($exam->questions as $q) {
            $num = $q->question_number;
            $this->questions[$num] = [
                'errors_count'        => $q->errors_count,
                'warnings_count'      => $q->warnings_count,
                'continuations_count' => $q->continuations_count,
                'history'             => [], // history is not persisted
            ];

            // Rehydrate the recitation snapshot from the linked bank row so the
            // active view can show surah/ayah/page after a refresh or resume.
            if ($q->recitationQuestion) {
                $rq = $q->recitationQuestion;
                $this->pickedQuestions[$num] = [
                    'recitation_question_id' => $rq->id,
                    'group_number'           => $rq->group_number->value,
                    'group_label'            => $rq->group_number->shortLabel(),
                    'group_full_label'       => $rq->group_number->fullLabel(),
                    'start_surah'            => (int) $rq->start_surah,
                    'start_surah_name'       => Surah::nameFor((int) $rq->start_surah) ?? '',
                    'start_ayah'             => (int) $rq->start_ayah,
                    'start_page'             => (int) $rq->start_page,
                    'end_surah'              => (int) $rq->end_surah,
                    'end_surah_name'         => Surah::nameFor((int) $rq->end_surah) ?? '',
                    'end_ayah'               => (int) $rq->end_ayah,
                    'end_page'               => (int) $rq->end_page,
                ];
            }
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
        $this->add_student_zone = '';
        $this->add_is_recite_before = null;
        $this->suggestionSearch = '';
        $this->showAddStudent  = false;
    }
}
