<?php

namespace App\Livewire\Admin\Exams;

use App\Enums\UserRole;
use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('تفاصيل الاختبار')]
class Show extends Component
{
    public Exam $exam;

    public function mount(Exam $exam): void
    {
        $user = Auth::user();
        if ($user->role === UserRole::Examiner) {
            $this->redirect(route('examiner.exams'), navigate: true);
            return;
        }

        $this->exam = $exam->load(['student', 'examiner', 'questions', 'reexamPermit']);
    }

    public function render()
    {
        return view('livewire.admin.exams.show');
    }
}
