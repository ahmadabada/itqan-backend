<?php

namespace App\Livewire\Public;

use App\Models\Exam;
use App\Models\Student;
use App\Models\SystemSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// BR-QUERY-01: Public page — no auth required
// BR-QUERY-02: Shows approved exam only
// BR-QUERY-03: Only active when results_query_enabled = true
#[Layout('layouts.guest')]
#[Title('استعلام النتائج')]
class ResultQuery extends Component
{
    public string $national_id = '';
    public bool   $searched    = false;

    /** @var \App\Models\Student|null */
    public ?Student $student = null;

    /** @var \App\Models\Exam|null */
    public ?Exam $exam = null;

    public bool $queryEnabled = false;

    public function mount(): void
    {
        // BR-QUERY-03: Check if query is enabled
        $this->queryEnabled = (bool) SystemSetting::get('results_query_enabled', false);
    }

    public function search(): void
    {
        if (! $this->queryEnabled) return;

        $this->validate([
            'national_id' => ['required', 'digits:9'],
        ], [
            'national_id.required' => 'رقم الهوية مطلوب.',
            'national_id.digits'   => 'رقم الهوية يجب أن يكون 9 أرقام.',
        ]);

        $this->student = Student::where('national_id', $this->national_id)->first();

        if ($this->student) {
            // BR-QUERY-02: Only approved exam
            $this->exam = Exam::where('student_id', $this->student->id)
                ->where('is_approved', true)
                ->with('questions')
                ->latest('completed_at')
                ->first();
        }

        $this->searched = true;
    }

    public function render()
    {
        return view('livewire.public.result-query');
    }
}
