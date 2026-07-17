<?php

namespace App\Livewire\Public;

use App\Enums\ExamStatus;
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

        // Reset previous result so re-searches don't leak the prior state.
        $this->student = null;
        $this->exam    = null;

        // national_id is UNIQUE now, so this resolves to exactly one student.
        $this->student = Student::where('national_id', $this->national_id)->first();

        if ($this->student) {
            // BR-QUERY-02: show the student's authoritative exam — the single one
            // that counts (newest approved, or the admin's pinned choice).
            // Eager-load student so the is_passed accessor (called several times
            // in the blade) doesn't lazy-load gender on each call.
            $this->exam = Exam::where('student_id', $this->student->id)
                ->where('status', ExamStatus::Approved)
                ->where('is_authoritative', true)
                ->with(['questions', 'student:id,gender'])
                ->first();
        }

        $this->searched = true;
    }

    public function render()
    {
        return view('livewire.public.result-query');
    }
}
