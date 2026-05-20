<?php

namespace App\Livewire\Admin\Merges;

use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\Student;
use App\Services\ArabicSearch;
use App\Services\MergeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

// Admin-driven merge workflow:
//   1. Sort by national_id to surface adjacent duplicates.
//   2. Tick the rows that represent the same person.
//   3. Open the merge modal, pick master + authoritative exam, confirm.
#[Layout('layouts.admin')]
#[Title('دمج الطلاب')]
class Index extends Component
{
    use WithPagination;

    public string $search          = '';
    public string $genderFilter    = '';
    public string $statusFilter    = 'not_merged'; // not_merged | merged | all
    // '' = no filter | 'national_id' = same national_id | 'name' = same first+second+family.
    public string $duplicateFilter = '';

    public array $selectedIds = [];

    // Merge modal state
    public bool   $showMergeModal      = false;
    public array  $mergeStudents       = [];
    public array  $mergeExams          = [];
    public ?int   $masterId            = null;
    public ?int   $authoritativeExamId = null;
    public string $masterFirstName     = '';
    public string $masterSecondName    = '';
    public string $masterThirdName     = '';
    public string $masterFamilyName    = '';
    public string $masterNationalId    = '';
    public string $notes               = '';

    public function mount(): void
    {
        // Merging students is a destructive, hard-to-reverse data operation;
        // restricted to super admins per security policy.
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            $this->redirect(
                $user->role === UserRole::Examiner ? route('examiner.dashboard') : route('admin.dashboard'),
            );
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGenderFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDuplicateFilter(): void
    {
        $this->resetPage();
    }

    public function toggleSelect(int $studentId): void
    {
        if (in_array($studentId, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$studentId]));
        } else {
            $this->selectedIds[] = $studentId;
        }
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function openMergeModal(): void
    {
        if (count($this->selectedIds) < 2) {
            $this->dispatch('notify', type: 'error', message: 'حدد طالبين على الأقل للدمج.');
            return;
        }

        $students = Student::whereIn('id', $this->selectedIds)
            ->orderBy('created_at')
            ->get();

        if ($students->count() !== count($this->selectedIds)) {
            $this->dispatch('notify', type: 'error', message: 'بعض الطلاب لم يعد موجوداً.');
            return;
        }

        $exams = Exam::whereIn('student_id', $this->selectedIds)
            ->with('examiner:id,first_name,family_name')
            ->orderBy('completed_at')
            ->get();

        $this->mergeStudents = $students->map(fn($s) => [
            'id'          => $s->id,
            'national_id' => $s->national_id,
            'full_name'   => $s->fullName(),
            'gender'      => $s->gender?->value,
            'created_at'  => $s->created_at?->format('Y-m-d H:i'),
        ])->all();

        $this->mergeExams = $exams->map(fn($e) => [
            'id'            => $e->id,
            'student_id'    => $e->student_id,
            'total_score'   => $e->total_score !== null ? (float) $e->total_score : null,
            'examiner_name' => $e->examiner
                ? trim(($e->examiner->first_name ?? '') . ' ' . ($e->examiner->family_name ?? ''))
                : '—',
            'completed_at'  => $e->completed_at?->format('Y-m-d H:i') ?? '—',
        ])->all();

        // Defaults: earliest student = master; highest-score exam = authoritative.
        $first = $students->first();
        $this->masterId         = $first?->id;
        $this->masterFirstName  = $first?->first_name ?? '';
        $this->masterSecondName = $first?->second_name ?? '';
        $this->masterThirdName  = $first?->third_name ?? '';
        $this->masterFamilyName = $first?->family_name ?? '';
        $this->masterNationalId = $first?->national_id ?? '';

        $top = $exams->whereNotNull('total_score')->sortByDesc('total_score')->first();
        $this->authoritativeExamId = $top?->id ?? $exams->first()?->id;

        $this->showMergeModal = true;
    }

    public function performMerge(MergeService $merger): void
    {
        $this->validate([
            'masterId'          => ['required', 'integer'],
            'masterFirstName'   => ['required', 'string', 'max:50'],
            'masterFamilyName'  => ['required', 'string', 'max:50'],
            'masterSecondName'  => ['nullable', 'string', 'max:50'],
            'masterThirdName'   => ['nullable', 'string', 'max:50'],
            'masterNationalId'  => ['nullable', 'digits:9'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ], [
            'masterFirstName.required'  => 'الاسم الأول مطلوب.',
            'masterFamilyName.required' => 'اسم العائلة مطلوب.',
            'masterNationalId.digits'   => 'رقم الهوية يجب أن يكون 9 أرقام.',
        ]);

        try {
            $merger->merge(
                studentIds:          $this->selectedIds,
                masterId:            $this->masterId,
                authoritativeExamId: $this->authoritativeExamId,
                masterDataOverride:  [
                    'first_name'  => $this->masterFirstName,
                    'second_name' => $this->masterSecondName ?: null,
                    'third_name'  => $this->masterThirdName ?: null,
                    'family_name' => $this->masterFamilyName,
                    'national_id' => $this->masterNationalId ?: null,
                ],
                adminUserId: Auth::user()->id, // Auth::id() returns national_id (User override)
                notes:       $this->notes ?: null,
            );
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'فشل الدمج: ' . $e->getMessage());
            return;
        }

        $this->showMergeModal = false;
        $this->selectedIds    = [];
        $this->resetMergeForm();
        $this->dispatch('notify', type: 'success', message: 'تم الدمج بنجاح.');
    }

    private function resetMergeForm(): void
    {
        $this->mergeStudents       = [];
        $this->mergeExams          = [];
        $this->masterId            = null;
        $this->authoritativeExamId = null;
        $this->masterFirstName     = '';
        $this->masterSecondName    = '';
        $this->masterThirdName     = '';
        $this->masterFamilyName    = '';
        $this->masterNationalId    = '';
        $this->notes               = '';
        $this->resetValidation();
    }

    public function render()
    {
        $byName = $this->duplicateFilter === 'name';

        $students = Student::query()
            ->when($this->search, fn($q) => ArabicSearch::applyTo(
                $q,
                $this->search,
                ['first_name', 'second_name', 'third_name', 'family_name'],
                ['national_id'],
            ))
            ->when($this->genderFilter, fn($q) => $q->where('gender', $this->genderFilter))
            ->when($this->statusFilter === 'not_merged', fn($q) => $q->whereNull('master_id'))
            ->when($this->statusFilter === 'merged',     fn($q) => $q->whereNotNull('master_id'))
            // Same national_id duplicates (NULLs excluded so empty IDs don't all match).
            ->when($this->duplicateFilter === 'national_id', fn($q) => $q->whereIn('national_id', function ($sub) {
                $sub->from('students')
                    ->select('national_id')
                    ->whereNotNull('national_id')
                    ->whereNull('deleted_at')
                    ->groupBy('national_id')
                    ->havingRaw('COUNT(*) > 1');
            }))
            // Same name triple (first + second + family). whereExists handles
            // NULL-vs-NULL equality on second_name explicitly — otherwise standard
            // SQL would treat two NULL second_names as non-equal.
            ->when($byName, fn($q) => $q->whereExists(function ($sub) {
                $sub->from('students as dup')
                    ->whereColumn('dup.first_name',  'students.first_name')
                    ->whereColumn('dup.family_name', 'students.family_name')
                    ->whereColumn('dup.id', '!=', 'students.id')
                    ->whereNull('dup.deleted_at')
                    ->where(function ($n) {
                        $n->whereColumn('dup.second_name', 'students.second_name')
                          ->orWhere(function ($both) {
                              $both->whereNull('dup.second_name')->whereNull('students.second_name');
                          });
                    });
            }))
            ->withCount('exams')
            // Surface duplicates as adjacent rows: when filtering by name, order
            // by the name triple; otherwise fall back to national_id grouping.
            ->when($byName,
                fn($q) => $q->orderBy('first_name')->orderBy('second_name')->orderBy('family_name'),
                fn($q) => $q->orderBy('national_id')->orderBy('family_name'),
            )
            ->paginate(50);

        return view('livewire.admin.merges.index', [
            'students' => $students,
        ]);
    }
}
