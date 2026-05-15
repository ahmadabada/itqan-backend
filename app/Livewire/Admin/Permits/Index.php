<?php

namespace App\Livewire\Admin\Permits;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\ReexamPermit;
use App\Models\Student;
use App\Services\ReexamPermitService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('أذونات إعادة الاختبار')]
class Index extends Component
{
    use WithPagination;

    // Grant form
    public string  $searchStudent = '';
    public ?int    $selectedStudentId = null;
    public ?string $generatedCode     = null;

    public function mount(): void
    {
        if (Auth::user()->role === UserRole::Examiner) {
            $this->redirect(route('examiner.dashboard'));
        }
    }

    // ──────────────────────────────────────────
    // Student search
    // ──────────────────────────────────────────

    public function selectStudentForPermit(int $studentId): void
    {
        $this->selectedStudentId = $studentId;
        $this->searchStudent     = '';
        $this->generatedCode     = null;
    }

    // ──────────────────────────────────────────
    // Grant permit (BR-REEX-02)
    // ──────────────────────────────────────────

    public function grantPermit(): void
    {
        if (! $this->selectedStudentId) return;

        $student = Student::findOrFail($this->selectedStudentId);
        $permit  = ReexamPermitService::grant($student);

        $this->generatedCode = $permit->permit_code;

        // BR-AUDIT-02: Log permit grant
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'reexam_permit_granted',
            'target_type' => 'student',
            'target_id'   => $student->id,
            'new_values'  => [
                'permit_code' => $permit->permit_code,
                'expires_at'  => $permit->expires_at->toDateString(),
            ],
        ]);
    }

    public function clearGrant(): void
    {
        $this->selectedStudentId = null;
        $this->generatedCode     = null;
        $this->searchStudent     = '';
    }

    // ──────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────

    public function render()
    {
        $studentResults = strlen($this->searchStudent) >= 2
            ? Student::where(fn($q) =>
                $q->where('national_id', 'like', "%{$this->searchStudent}%")
                  ->orWhere('first_name',  'like', "%{$this->searchStudent}%")
                  ->orWhere('family_name', 'like', "%{$this->searchStudent}%")
              )->limit(8)->get()
            : collect();

        $permits = ReexamPermit::with(['student', 'grantedBy'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.permits.index', [
            'studentResults' => $studentResults,
            'selectedStudent' => $this->selectedStudentId ? Student::find($this->selectedStudentId) : null,
            'permits'        => $permits,
        ]);
    }
}
