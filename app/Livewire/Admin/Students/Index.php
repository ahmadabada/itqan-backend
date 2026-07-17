<?php

namespace App\Livewire\Admin\Students;

use App\Enums\Halaqah;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Student;
use App\Services\ArabicSearch;
use App\Services\StudentImportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

// Shared by admins and examiners. Examiners are scoped to their own gender
// throughout — search, listing, and the create/edit form — since they may only
// deal with same-gender students. Admins see everyone.
#[Layout('layouts.admin')]
#[Title('الطلاب')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search        = '';
    public string $genderFilter  = '';

    // Create/edit modal
    public bool   $showFormModal  = false;
    public ?int   $editStudentId  = null;
    public string $form_national_id  = '';
    public string $form_first_name   = '';
    public string $form_second_name  = '';
    public string $form_third_name   = '';
    public string $form_family_name  = '';
    public string $form_gender       = '';
    public string $form_halaqah      = '';

    // Import modal (admins only)
    public bool $showImportModal = false;
    public $importFile           = null;
    /** @var array<string, mixed>|null */
    public ?array $importResult  = null;

    // Delete
    public ?int $deleteStudentId = null;

    private function isExaminer(): bool
    {
        return Auth::user()->role === UserRole::Examiner;
    }

    // The gender an examiner is locked to; null for admins (no restriction).
    private function lockedGender(): ?string
    {
        return $this->isExaminer() ? Auth::user()->gender?->value : null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGenderFilter(): void
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

        // An examiner must not edit a student outside their gender.
        if ($this->lockedGender() && $student->gender?->value !== $this->lockedGender()) {
            $this->dispatch('notify', type: 'danger', message: 'لا يمكنك تعديل طالب من جنس مختلف.');
            return;
        }

        $this->editStudentId     = $studentId;
        $this->form_national_id  = $student->national_id;
        $this->form_first_name   = $student->first_name;
        $this->form_second_name  = $student->second_name ?? '';
        $this->form_third_name   = $student->third_name ?? '';
        $this->form_family_name  = $student->family_name;
        $this->form_gender       = $student->gender?->value ?? '';
        $this->form_halaqah      = $student->halaqah?->value ?? '';
        $this->showFormModal     = true;
    }

    // ──────────────────────────────────────────
    // Save (create or update)
    // ──────────────────────────────────────────

    public function saveStudent(): void
    {
        $currentUser = Auth::user();

        // national_id is now mandatory and UNIQUE — it is the identity every exam
        // and the mobile roster keys on.
        $nationalIdRule = $this->editStudentId
            ? ['required', 'digits:9', 'unique:students,national_id,' . $this->editStudentId]
            : ['required', 'digits:9', 'unique:students,national_id'];

        // Examiners may only create/edit within their own gender.
        $genderRule = $this->lockedGender()
            ? ['required', 'in:' . $this->lockedGender()]
            : ['required', 'in:male,female'];

        $this->validate([
            'form_national_id' => $nationalIdRule,
            'form_first_name'  => ['required', 'string', 'max:50'],
            'form_second_name' => ['nullable', 'string', 'max:50'],
            'form_third_name'  => ['nullable', 'string', 'max:50'],
            'form_family_name' => ['required', 'string', 'max:50'],
            'form_gender'      => $genderRule,
            'form_halaqah'     => ['required', 'in:' . implode(',', array_column(Halaqah::cases(), 'value'))],
        ], [
            'form_national_id.required' => 'رقم الهوية مطلوب.',
            'form_national_id.digits'   => 'رقم الهوية يجب أن يكون 9 أرقام.',
            'form_national_id.unique'   => 'رقم الهوية مسجّل مسبقاً.',
            'form_first_name.required'  => 'الاسم الأول مطلوب.',
            'form_family_name.required' => 'اسم العائلة مطلوب.',
            'form_gender.required'      => 'الجنس مطلوب.',
            'form_gender.in'            => 'لا يمكنك إضافة طالب من جنس مختلف.',
            'form_halaqah.required'     => 'الحلقة مطلوبة.',
            'form_halaqah.in'           => 'الحلقة المحددة غير صالحة.',
        ]);

        $data = [
            'national_id' => $this->form_national_id,
            'first_name'  => $this->form_first_name,
            'second_name' => $this->form_second_name ?: null,
            'third_name'  => $this->form_third_name ?: null,
            'family_name' => $this->form_family_name,
            'gender'      => $this->form_gender,
            'halaqah'     => $this->form_halaqah,
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
    // Excel import (admins only) → students
    // ──────────────────────────────────────────

    public function openImportModal(): void
    {
        if ($this->isExaminer()) {
            abort(403);
        }
        $this->reset(['importFile', 'importResult']);
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function downloadTemplate(StudentImportService $service)
    {
        if ($this->isExaminer()) {
            abort(403);
        }
        return response()->download($service->buildTemplate(), 'students-template.xlsx')
            ->deleteFileAfterSend();
    }

    public function runImport(StudentImportService $service): void
    {
        if ($this->isExaminer()) {
            abort(403);
        }

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'importFile.required' => 'اختر ملفاً.',
            'importFile.mimes'    => 'الملف يجب أن يكون xlsx أو xls أو csv.',
            'importFile.max'      => 'حجم الملف يجب ألا يتجاوز 5 ميجابايت.',
        ]);

        try {
            $this->importResult = $service->import(
                $this->importFile->getRealPath(),
                // Auth::id() returns national_id here (User::getAuthIdentifierName),
                // not the primary key — use ->id for the created_by_user_id FK.
                Auth::user()->id,
                $this->importFile->getClientOriginalName(),
            );
        } catch (\Throwable $e) {
            $this->addError('importFile', $e->getMessage());
            return;
        }

        if (empty($this->importResult['failed_rows'])) {
            AuditLog::create([
                'user_id'     => Auth::user()->id,
                'action'      => 'students_imported',
                'target_type' => 'student',
                'new_values'  => [
                    'inserted' => $this->importResult['inserted'],
                    'updated'  => $this->importResult['updated'],
                ],
            ]);
            $this->dispatch('notify', type: 'success', message:
                "تم الاستيراد: {$this->importResult['inserted']} جديد، {$this->importResult['updated']} محدّث.");
        } else {
            $this->dispatch('notify', type: 'danger', message:
                'الملف يحتوي أخطاءً — لم يُحفظ أي صف. راجع التفاصيل.');
        }
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

        if ($this->lockedGender() && $student->gender?->value !== $this->lockedGender()) {
            $this->dispatch('notify', type: 'danger', message: 'لا يمكنك حذف طالب من جنس مختلف.');
            $this->deleteStudentId = null;
            return;
        }

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
        $lockedGender = $this->lockedGender();

        $students = Student::query()
            // Examiners see only their own gender; the filter chip is theirs to ignore.
            ->when($lockedGender, fn($q) => $q->where('gender', $lockedGender))
            ->when($this->search, fn($q) => ArabicSearch::applyTo(
                $q,
                $this->search,
                ['first_name', 'second_name', 'third_name', 'family_name'],
                ['national_id'],
            ))
            ->when(! $lockedGender && $this->genderFilter, fn($q) => $q->where('gender', $this->genderFilter))
            ->orderBy('family_name')
            ->orderBy('first_name')
            ->paginate(25);

        return view('livewire.admin.students.index', [
            'students'     => $students,
            'halaqat'      => Halaqah::cases(),
            'lockedGender' => $lockedGender,
        ])->layout($this->isExaminer() ? 'layouts.examiner' : 'layouts.admin');
    }

    private function resetStudentForm(): void
    {
        $this->editStudentId    = null;
        $this->form_national_id = '';
        $this->form_first_name  = '';
        $this->form_second_name = '';
        $this->form_third_name  = '';
        $this->form_family_name = '';
        // Pre-lock an examiner's gender so the field is correct without a choice.
        $this->form_gender      = $this->lockedGender() ?? '';
        $this->form_halaqah     = '';
        $this->resetValidation();
    }
}
