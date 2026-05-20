<?php

namespace App\Livewire\Admin\Exams;

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\User;
use App\Services\ArabicSearch;
use App\Services\ScoreCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#[Layout('layouts.admin')]
#[Title('الاختبارات')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    // Default to "approved" — the official counted exams. Admins can switch to
    // "الكل" or another status from the dropdown. clearFilters() returns here.
    #[Url(as: 'status')]
    public string $statusFilter = 'approved';

    #[Url(as: 'examiner')]
    public string $examinerFilter = '';

    #[Url(as: 'gender')]
    public string $genderFilter = '';

    #[Url(as: 'min')]
    public string $minScore = '';

    #[Url(as: 'max')]
    public string $maxScore = '';

    // '' | passed | failed. Evaluated live against ScoreCalculator::passingScore()
    // so a settings change immediately shifts the cohort, matching is_passed.
    #[Url(as: 'pass')]
    public string $passedFilter = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDir = 'desc';

    // Export modal state. NOT persisted in URL — purely UI/session.
    public bool $showExportModal = false;

    // Per-column include flag. Keys match the match() arms in export().
    // Defaults to all-on; openExportModal() resets to this set on every open.
    public array $exportColumns = [
        'id'           => true,
        'student_name' => true,
        'national_id'  => true,
        'zone'         => true,
        'gender'       => true,
        'examiner'     => true,
        'exam_type'    => true,
        'score'        => true,
        'passed'       => true,
        'status'       => true,
        'started_at'   => true,
    ];

    // Human labels — shared between the modal checkboxes and the Excel header row.
    public const EXPORT_COLUMN_LABELS = [
        'id'           => '#',
        'student_name' => 'اسم الطالب',
        'national_id'  => 'رقم الهوية',
        'zone'         => 'المنطقة',
        'gender'       => 'الجنس',
        'examiner'     => 'المختبر',
        'exam_type'    => 'النوع',
        'score'        => 'الدرجة',
        'passed'       => 'مجاز؟',
        'status'       => 'الحالة',
        'started_at'   => 'تاريخ البدء',
    ];

    public function mount(): void
    {
        $user = Auth::user();
        if ($user->role === UserRole::Examiner) {
            $this->redirect(route('examiner.exams'), navigate: true);
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'statusFilter', 'examinerFilter', 'genderFilter', 'minScore', 'maxScore', 'passedFilter'])) {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'desc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'examinerFilter', 'genderFilter', 'minScore', 'maxScore', 'passedFilter']);
        $this->resetPage();
    }

    // Approve / Exclude — admin override on any completed exam. Either action
    // is reversible by calling the opposite, and every flip is audit-logged.
    // Examiners cannot reach this page (mount() redirects), so role gating is
    // already enforced at the controller level.
    public function approve(int $examId): void
    {
        $this->setStatus($examId, ExamStatus::Approved);
    }

    public function exclude(int $examId): void
    {
        $this->setStatus($examId, ExamStatus::Excluded);
    }

    private function setStatus(int $examId, ExamStatus $newStatus): void
    {
        $exam = Exam::find($examId);
        if (! $exam) {
            $this->dispatch('notify', type: 'error', message: 'الاختبار غير موجود.');
            return;
        }
        if ($exam->status === ExamStatus::InProgress) {
            $this->dispatch('notify', type: 'error', message: 'لا يمكن تغيير حالة اختبار جارٍ.');
            return;
        }
        if ($exam->status === $newStatus) {
            return;
        }

        $oldStatus = $exam->status;
        $exam->update(['status' => $newStatus]);

        AuditLog::create([
            'user_id'     => Auth::user()->id,
            'action'      => $newStatus === ExamStatus::Approved ? 'exam_approved' : 'exam_excluded',
            'target_type' => 'exam',
            'target_id'   => $exam->id,
            'old_values'  => ['status' => $oldStatus?->value],
            'new_values'  => ['status' => $newStatus->value],
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            message: $newStatus === ExamStatus::Approved ? 'تم اعتماد الاختبار.' : 'تم استبعاد الاختبار.',
        );
    }

    // Builds the filtered base query — reused by the table (with pagination),
    // the counter card aggregates, and the Excel export so all three stay in
    // lockstep with the current filter state.
    private function filteredQuery(): Builder
    {
        $passingScore = ScoreCalculator::passingScore();

        return Exam::query()
            ->when($this->search, fn($q) => $q->whereHas('student', fn($s) =>
                ArabicSearch::applyTo(
                    $s,
                    $this->search,
                    ['first_name', 'second_name', 'third_name', 'family_name'],
                    ['national_id'],
                )
            ))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->examinerFilter, fn($q) => $q->where('examiner_id', $this->examinerFilter))
            ->when($this->genderFilter, fn($q) =>
                $q->whereHas('student', fn($s) => $s->where('gender', $this->genderFilter))
            )
            ->when(is_numeric($this->minScore), fn($q) => $q->where('total_score', '>=', (float) $this->minScore))
            ->when(is_numeric($this->maxScore), fn($q) => $q->where('total_score', '<=', (float) $this->maxScore))
            ->when($this->passedFilter === 'passed', fn($q) => $q->where('total_score', '>=', $passingScore))
            ->when($this->passedFilter === 'failed', fn($q) => $q->whereNotNull('total_score')->where('total_score', '<', $passingScore));
    }

    public function openExportModal(): void
    {
        // Reset to all-selected on every open so toggling off-columns in one
        // session doesn't silently carry over to the next export attempt.
        $this->exportColumns = array_fill_keys(array_keys(self::EXPORT_COLUMN_LABELS), true);
        $this->showExportModal = true;
    }

    public function export()
    {
        // Honor the modal's column toggles — at least one must be selected.
        $selectedKeys = array_keys(array_filter($this->exportColumns));
        if (empty($selectedKeys)) {
            $this->dispatch('notify', type: 'error', message: 'اختر عموداً واحداً على الأقل.');
            return null;
        }

        $passingScore = ScoreCalculator::passingScore();
        $allowedSort  = ['created_at', 'started_at', 'completed_at', 'total_score', 'status'];
        $sortBy       = in_array($this->sortBy, $allowedSort) ? $this->sortBy : 'created_at';

        $rows = $this->filteredQuery()
            ->with(['student.master', 'examiner'])
            ->orderBy($sortBy, $this->sortDir)
            ->get();

        // Preserve the canonical column order (LABELS array order), even if the
        // user toggled checkboxes in a different sequence.
        $orderedKeys = array_keys(array_intersect_key(self::EXPORT_COLUMN_LABELS, array_flip($selectedKeys)));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('الاختبارات');

        // Header row — PhpSpreadsheet 2.x+ removed setCellValueByColumnAndRow();
        // use the array-coords form [col, row].
        foreach ($orderedKeys as $i => $key) {
            $sheet->setCellValue([$i + 1, 1], self::EXPORT_COLUMN_LABELS[$key]);
        }
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($orderedKeys));
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $zones = [
            'East Gaza'  => 'شرق غزة',
            'West Gaza'  => 'غرب غزة',
            'North Gaza' => 'شمال غزة',
            'South Gaza' => 'جنوب غزة',
        ];

        $rowNum = 2;
        foreach ($rows as $exam) {
            $student  = $exam->student?->master ?? $exam->student;
            $score    = $exam->total_score !== null ? (float) $exam->total_score : null;
            $isPassed = $score !== null ? ($score >= $passingScore) : null;

            foreach ($orderedKeys as $i => $key) {
                $value = match ($key) {
                    'id'           => $exam->id,
                    'student_name' => $student?->fullName() ?? '',
                    'national_id'  => $student?->national_id ?? '',
                    'zone'         => $zones[$student?->student_zone] ?? $student?->student_zone ?? '',
                    'gender'       => $student?->gender?->label() ?? '',
                    'examiner'     => $exam->examiner?->fullName() ?? '',
                    'exam_type'    => $exam->exam_type?->label() ?? '',
                    'score'        => $score,
                    'passed'       => $isPassed === null ? '' : ($isPassed ? 'نعم' : 'لا'),
                    'status'       => $exam->status?->label() ?? '',
                    'started_at'   => $exam->started_at?->format('Y-m-d H:i') ?? '',
                };
                $sheet->setCellValue([$i + 1, $rowNum], $value);
            }
            $rowNum++;
        }

        for ($i = 1; $i <= count($orderedKeys); $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'exams_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);

        $filename = 'exams_' . now()->format('Y-m-d_His') . '.xlsx';
        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    public function render()
    {
        $allowedSort = ['created_at', 'started_at', 'completed_at', 'total_score', 'status'];
        $sortBy      = in_array($this->sortBy, $allowedSort) ? $this->sortBy : 'created_at';
        $passingScore = ScoreCalculator::passingScore();

        $base = $this->filteredQuery();

        $exams = (clone $base)
            ->with(['student.master', 'examiner'])
            ->orderBy($sortBy, $this->sortDir)
            ->paginate(25);

        // Aggregates against the same filtered set (NOT against the paginated
        // 25 rows). Each clone resets the WHERE chain so the counts are exact.
        $totalCount   = (clone $base)->count();
        $passedCount  = (clone $base)->where('total_score', '>=', $passingScore)->count();
        $failedCount  = (clone $base)->whereNotNull('total_score')->where('total_score', '<', $passingScore)->count();
        $avgScore     = (clone $base)->whereNotNull('total_score')->avg('total_score');

        $examiners = User::where('role', UserRole::Examiner)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'second_name', 'third_name', 'family_name']);

        return view('livewire.admin.exams.index', [
            'exams'        => $exams,
            'examiners'    => $examiners,
            'statuses'     => ExamStatus::cases(),
            'totalCount'   => $totalCount,
            'passedCount'  => $passedCount,
            'failedCount'  => $failedCount,
            'avgScore'     => $avgScore !== null ? (float) $avgScore : null,
            'passingScore' => $passingScore,
        ]);
    }
}
