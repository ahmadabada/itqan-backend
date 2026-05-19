<?php

namespace App\Livewire\Admin\Students;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Student;
use App\Services\ArabicSearch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('الطلاب')]
class Index extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $genderFilter  = '';
    public bool   $showMerged    = false;

    // Create/edit modal
    public bool   $showFormModal  = false;
    public ?int   $editStudentId  = null;
    public string $form_national_id  = '';
    public string $form_first_name   = '';
    public string $form_second_name  = '';
    public string $form_third_name   = '';
    public string $form_family_name  = '';
    public string $form_gender       = '';
    public string $form_student_zone = '';
    public bool   $form_is_recite_before = false;

    // Delete
    public ?int $deleteStudentId = null;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user->role === UserRole::Examiner) {
            $this->redirect(route('examiner.dashboard'));
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGenderFilter(): void
    {
        $this->resetPage();
    }

    public function updatingShowMerged(): void
    {
        $this->resetPage();
    }

    // ──────────────────────────────────────────
    // Create
    // ──────────────────────────────────────────

    public function openCreateModal(): void
    {
        $this->editStudentId = null;
        $this->resetStudentForm();
        $this->showFormModal = true;
    }

    // ──────────────────────────────────────────
    // Edit
    // ──────────────────────────────────────────

    public function openEditModal(int $studentId): void
    {
        $student = Student::findOrFail($studentId);
        $this->editStudentId     = $studentId;
        $this->form_national_id  = $student->national_id;
        $this->form_first_name   = $student->first_name;
        $this->form_second_name  = $student->second_name ?? '';
        $this->form_third_name   = $student->third_name ?? '';
        $this->form_family_name  = $student->family_name;
        $this->form_gender       = $student->gender?->value ?? '';
        $this->form_student_zone = $student->student_zone ?? '';
        $this->form_is_recite_before = $student->is_recite_before;
        $this->showFormModal     = true;
    }

    // ──────────────────────────────────────────
    // Save (create or update)
    // ──────────────────────────────────────────

    public function saveStudent(): void
    {
        $currentUser = Auth::user();

        // National ID is optional, but if provided must be 9 digits and unique.
        // Required at exam time (enforced in Examiner\ExamSession::startExam).
        $nationalIdRule = $this->editStudentId
            ? ['nullable', 'digits:9', 'unique:students,national_id,' . $this->editStudentId]
            : ['nullable', 'digits:9', 'unique:students,national_id'];

        $this->validate([
            'form_national_id' => $nationalIdRule,
            'form_first_name'  => ['required', 'string', 'max:50'],
            'form_second_name' => ['nullable', 'string', 'max:50'],
            'form_third_name'  => ['nullable', 'string', 'max:50'],
            'form_family_name' => ['required', 'string', 'max:50'],
            'form_gender'      => ['required', 'in:male,female'],
            'form_student_zone'=> ['required', 'in:East Gaza,West Gaza,North Gaza,South Gaza'],
            'form_is_recite_before' => ['boolean'],
        ], [
            'form_national_id.digits'   => 'رقم الهوية يجب أن يكون 9 أرقام.',
            'form_national_id.unique'   => 'رقم الهوية مسجّل مسبقاً.',
            'form_first_name.required'  => 'الاسم الأول مطلوب.',
            'form_family_name.required' => 'اسم العائلة مطلوب.',
            'form_gender.required'      => 'الجنس مطلوب.',
            'form_student_zone.required'=> 'منطقة الطالب مطلوبة.',
        ]);

        $data = [
            'national_id'  => $this->form_national_id ?: null,
            'first_name'   => $this->form_first_name,
            'second_name'  => $this->form_second_name ?: null,
            'third_name'   => $this->form_third_name ?: null,
            'family_name'  => $this->form_family_name,
            'gender'       => $this->form_gender ?: null,
            'student_zone' => $this->form_student_zone ?: null,
            'is_recite_before' => $this->form_is_recite_before,
        ];

        if ($this->editStudentId) {
            $student = Student::findOrFail($this->editStudentId);
            $old     = $student->only(['national_id', 'first_name', 'family_name']);
            $student->update($data);

            AuditLog::create([
                'user_id'     => $currentUser->id,
                'action'      => 'student_updated',
                'target_type' => 'student',
                'target_id'   => $student->id,
                'old_values'  => $old,
                'new_values'  => $data,
            ]);
        } else {
            $student = Student::create($data);

            AuditLog::create([
                'user_id'     => $currentUser->id,
                'action'      => 'student_created',
                'target_type' => 'student',
                'target_id'   => $student->id,
                'new_values'  => $data,
            ]);
        }

        $this->showFormModal = false;
        $this->resetStudentForm();
        $this->dispatch('notify', type: 'success', message: $this->editStudentId ? 'تم تحديث الطالب.' : 'تم إضافة الطالب.');
    }

    // ──────────────────────────────────────────
    // Delete
    // ──────────────────────────────────────────

    public function confirmDelete(int $studentId): void
    {
        $this->deleteStudentId = $studentId;
    }

    public function deleteStudent(): void
    {
        $student = Student::findOrFail($this->deleteStudentId);

        AuditLog::create([
            'user_id'     => Auth::user()->id,
            'action'      => 'student_deleted',
            'target_type' => 'student',
            'target_id'   => $student->id,
            'old_values'  => $student->only(['national_id', 'first_name', 'family_name']),
        ]);

        $student->delete();
        $this->deleteStudentId = null;
        $this->dispatch('notify', type: 'success', message: 'تم حذف الطالب.');
    }

    // ──────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────

    public function render()
    {
        // Hide merged records by default — they belong to the merge UI only.
        // Why: after admin merge, the duplicate row stays in the DB for audit/undo
        // but should not appear in any "list of students" UI.
        $students = Student::query()
            ->unless($this->showMerged, fn($q) => $q->whereNull('master_id'))
            ->when($this->search, fn($q) => ArabicSearch::applyTo(
                $q,
                $this->search,
                ['first_name', 'second_name', 'third_name', 'family_name'],
                ['national_id'],
            ))
            ->when($this->genderFilter, fn($q) => $q->where('gender', $this->genderFilter))
            ->orderBy('family_name')
            ->orderBy('first_name')
            ->paginate(25);

        return view('livewire.admin.students.index', ['students' => $students]);
    }

    private function resetStudentForm(): void
    {
        $this->editStudentId    = null;
        $this->form_national_id = '';
        $this->form_first_name  = '';
        $this->form_second_name = '';
        $this->form_third_name  = '';
        $this->form_family_name = '';
        $this->form_gender      = '';
        $this->form_student_zone = '';
        $this->form_is_recite_before = false;
        $this->resetValidation();
    }
}
