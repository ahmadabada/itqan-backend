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
        $this->student = $student->load(['createdBy']);
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
            // Every attempt this student has sat, newest first. The authoritative
            // one is flagged per-row; the public result page shows only it.
            $exams = Exam::where('student_id', $this->student->id)
                ->with(['examiner', 'student:id,first_name,family_name,gender'])
                ->latest('started_at')
                ->paginate(15);
        }

        return view('livewire.admin.students.show', [
            'exams' => $exams,
        ]);
    }
}
