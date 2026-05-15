<?php

namespace App\Livewire\Examiner;

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.examiner')]
#[Title('اختباراتي')]
class MyExams extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'gender')]
    public string $genderFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    public function mount(): void
    {
        $user = Auth::user();
        if ($user->role !== UserRole::Examiner) {
            $this->redirect(route('admin.dashboard'), navigate: true);
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'statusFilter', 'genderFilter', 'dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'genderFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $exams = Exam::query()
            ->with(['student'])
            ->where('examiner_id', Auth::user()->id)
            ->when($this->search, fn($q) => $q->whereHas('student', fn($s) =>
                $s->where('national_id', 'like', "%{$this->search}%")
                  ->orWhere('first_name',  'like', "%{$this->search}%")
                  ->orWhere('family_name', 'like', "%{$this->search}%")
            ))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->genderFilter, fn($q) =>
                $q->whereHas('student', fn($s) => $s->where('gender', $this->genderFilter))
            )
            ->when($this->dateFrom, fn($q) => $q->whereDate('started_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn($q) => $q->whereDate('started_at', '<=', $this->dateTo))
            ->latest('started_at')
            ->paginate(20);

        return view('livewire.examiner.my-exams', [
            'exams'    => $exams,
            'statuses' => ExamStatus::cases(),
        ]);
    }
}
