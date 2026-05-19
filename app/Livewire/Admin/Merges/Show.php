<?php

namespace App\Livewire\Admin\Merges;

use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\MergeOperation;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// Renders the full snapshot of a merge operation:
//   • pre-merge state (from MergeOperation.pre_merge_snapshot)
//   • current state of the master + merged students (live from DB)
//   • all exams across the merged students, highlighting the authoritative one
#[Layout('layouts.admin')]
#[Title('تفاصيل عملية الدمج')]
class Show extends Component
{
    public MergeOperation $operation;

    public function mount(MergeOperation $operation): void
    {
        if (Auth::user()->role === UserRole::Examiner) {
            $this->redirect(route('examiner.dashboard'));
            return;
        }

        $this->operation = $operation->load([
            'masterStudent',
            'authoritativeExam',
            'performedBy:id,first_name,family_name',
            'undoneBy:id,first_name,family_name',
        ]);
    }

    public function render()
    {
        $snapshot = $this->operation->pre_merge_snapshot ?? [];

        // All students touched by the merge (master + merged), live from DB.
        $allStudentIds = collect($snapshot['students'] ?? [])->pluck('id')->all();

        $currentStudents = Student::withTrashed()
            ->whereIn('id', $allStudentIds)
            ->get()
            ->keyBy('id');

        $currentExams = Exam::whereIn('student_id', $allStudentIds)
            ->with('examiner:id,first_name,family_name')
            ->orderBy('completed_at')
            ->get();

        return view('livewire.admin.merges.show', [
            'snapshot'        => $snapshot,
            'currentStudents' => $currentStudents,
            'currentExams'    => $currentExams,
        ]);
    }
}
