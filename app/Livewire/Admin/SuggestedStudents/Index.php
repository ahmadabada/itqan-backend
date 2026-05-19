<?php

namespace App\Livewire\Admin\SuggestedStudents;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\SuggestedStudent;
use App\Services\ArabicSearch;
use App\Services\SuggestedStudentImportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('الطلاب المقترحون')]
class Index extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search       = '';
    public string $genderFilter = '';

    public bool $showImportModal = false;

    #[Validate('required|file|mimes:xlsx,xls,csv|max:5120')]
    public $importFile;

    public ?string $lastErrorsCsvPath = null;
    public ?string $lastStoredPath    = null;
    public int     $lastFailedCount   = 0;
    public int     $lastImportedCount = 0;

    public bool $showDeleteAllModal = false;

    public function mount(): void
    {
        if (Auth::user()->role === UserRole::Examiner) {
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

    // ────── Import ──────

    public function openImportModal(): void
    {
        $this->reset(['importFile']);
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function runImport(SuggestedStudentImportService $importer): void
    {
        $this->validate();

        try {
            $stats = $importer->import(
                $this->importFile->getRealPath(),
                $this->importFile->getClientOriginalName()
            );
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'فشل الرفع: ' . $e->getMessage());
            return;
        }

        $this->lastImportedCount = $stats['imported'];
        $this->lastFailedCount   = count($stats['failed_rows']);
        $this->lastStoredPath    = $stats['stored_path'];
        $this->lastErrorsCsvPath = $stats['errors_csv_path'];
        $this->showImportModal   = false;
        $this->reset(['importFile']);

        if ($this->lastFailedCount > 0) {
            $this->dispatch('notify', type: 'error', message: "تم رفض الملف: {$this->lastFailedCount} صف فيه أخطاء. يمكنك تنزيل ملف الأخطاء لمراجعتها.");
            return;
        }

        AuditLog::create([
            'user_id'    => Auth::user()->id,
            'action'     => 'suggested_students_imported',
            'new_values' => ['count' => $stats['imported'], 'stored_path' => $stats['stored_path']],
        ]);

        $this->dispatch('notify', type: 'success', message: "تم رفع {$stats['imported']} طالب.");
    }

    public function downloadErrors()
    {
        if (! $this->lastErrorsCsvPath) {
            return null;
        }
        return Storage::disk('local')->download($this->lastErrorsCsvPath);
    }

    public function downloadTemplate(SuggestedStudentImportService $importer)
    {
        $path = $importer->buildTemplate();
        return response()->download(
            $path,
            'suggested-students-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend();
    }

    // ────── Delete all ──────

    public function confirmDeleteAll(): void
    {
        $this->showDeleteAllModal = true;
    }

    public function deleteAll(SuggestedStudentImportService $importer): void
    {
        $deleted = $importer->deleteAll();

        AuditLog::create([
            'user_id'    => Auth::user()->id,
            'action'     => 'suggested_students_cleared',
            'new_values' => ['count' => $deleted],
        ]);

        $this->showDeleteAllModal = false;
        $this->dispatch('notify', type: 'success', message: "تم حذف {$deleted} مقترح.");
    }

    public function render()
    {
        $rows = SuggestedStudent::query()
            ->when($this->search, fn($q) => ArabicSearch::applyTo(
                $q,
                $this->search,
                ['first_name', 'second_name', 'third_name', 'family_name'],
                ['national_id'],
            ))
            ->when($this->genderFilter, fn($q) => $q->where('gender', $this->genderFilter))
            ->orderBy('family_name')
            ->orderBy('first_name')
            ->paginate(50);

        return view('livewire.admin.suggested-students.index', [
            'rows' => $rows,
        ]);
    }
}
