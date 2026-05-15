<?php

namespace App\Livewire\Admin\Exams;

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\User;
use App\Services\ArabicSearch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('الاختبارات')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'examiner')]
    public string $examinerFilter = '';

    #[Url(as: 'gender')]
    public string $genderFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDir = 'desc';

    public function mount(): void
    {
        $user = Auth::user();
        if ($user->role === UserRole::Examiner) {
            $this->redirect(route('examiner.exams'), navigate: true);
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'statusFilter', 'examinerFilter', 'genderFilter', 'dateFrom', 'dateTo'])) {
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
        $this->reset(['search', 'statusFilter', 'examinerFilter', 'genderFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $allowedSort = ['created_at', 'started_at', 'completed_at', 'total_score', 'status'];
        $sortBy      = in_array($this->sortBy, $allowedSort) ? $this->sortBy : 'created_at';

        $exams = Exam::query()
            ->with(['student', 'examiner'])
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
            ->when($this->dateFrom, fn($q) => $q->whereDate('started_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn($q) => $q->whereDate('started_at', '<=', $this->dateTo))
            ->orderBy($sortBy, $this->sortDir)
            ->paginate(25);

        $examiners = User::where('role', UserRole::Examiner)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'second_name', 'third_name', 'family_name']);

        return view('livewire.admin.exams.index', [
            'exams'     => $exams,
            'examiners' => $examiners,
            'statuses'  => ExamStatus::cases(),
        ]);
    }
}
