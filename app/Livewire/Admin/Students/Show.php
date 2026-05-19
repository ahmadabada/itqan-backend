<?php

namespace App\Livewire\Admin\Students;

use App\Models\Exam;
use App\Models\Student;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('ملف الطالب')]
class Show extends Component
{
    use WithPagination;

    public Student $student;
    public string $activeTab = 'profile';

    public function mount(Student $student): void
    {
        $this->student = $student->load(['createdBy', 'mergedBy', 'master']);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        if ($tab === 'exams') {
            $this->resetPage(); // Reset pagination when switching to exams tab
        }
    }

    public function render()
    {
        $exams = null;
        if ($this->activeTab === 'exams') {
            // Show every attempt for this master — own attempts plus exams
            // inherited from merged students. The admin needs the full history;
            // approval is signalled per-row (is_approved badge), while the
            // public result page and Excel exports already filter to the
            // canonical approved exam only.
            $exams = Exam::forMasterStudent($this->student->id)
                ->with(['examiner', 'student:id,first_name,family_name,master_id'])
                ->latest('started_at')
                ->paginate(15);
        }

        return view('livewire.admin.students.show', [
            'exams' => $exams,
        ]);
    }
}
