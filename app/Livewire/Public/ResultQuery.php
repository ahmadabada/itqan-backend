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

    // True when the searched national_id belonged to a record that has since been
    // merged into another — used to render a notice on the public page.
    public bool $wasMerged = false;

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
        $this->student   = null;
        $this->exam      = null;
        $this->wasMerged = false;

        // Prefer the master record when the same national_id exists on multiple rows
        // (duplicate captured by the mobile app before an admin merge).
        $found = Student::where('national_id', $this->national_id)
            ->orderByRaw('master_id IS NULL DESC') // masters first
            ->orderBy('id')
            ->first();

        if ($found) {
            // If this row was merged into another, resolve to the master so the
            // public page shows the canonical identity.
            $this->wasMerged = (bool) $found->master_id;
            $this->student   = $found->master_id
                ? (Student::find($found->master_id) ?? $found)
                : $found;

            // BR-QUERY-02: Only the canonical exam — searched across the master
            // and any students merged into it so the result survives admin merges.
            //
            // Multiple Approved rows are theoretically possible (e.g. mobile
            // sync writes Approved per record, and admin merges only collapse
            // them on demand). The admin is expected to manually ensure one
            // Approved per master before publishing results; until then, this
            // query deterministically picks the EARLIEST approved attempt —
            // oldest completed_at first, falling back to lowest id on ties.
            $this->exam = Exam::forMasterStudent($this->student->id)
                ->where('status', ExamStatus::Approved)
                ->with('questions')
                ->oldest('completed_at')
                ->oldest('id')
                ->first();
        }

        $this->searched = true;
    }

    public function render()
    {
        return view('livewire.public.result-query');
    }
}
