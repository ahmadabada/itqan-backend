<?php

namespace App\Livewire\Admin\RecitationQuestions;

use App\Enums\QuestionGroup;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\RecitationQuestion;
use App\Services\RecitationQuestionImportService;
use App\Support\Surah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('بنك الأسئلة')]
class Index extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search       = '';
    public string $groupFilter  = '';

    // Import modal
    public bool   $showImportModal = false;
    public string $importMode      = 'replace';

    #[Validate('required|file|mimes:xlsx,xls,csv|max:5120')]
    public $importFile;

    // Last import result — used to render a "download errors" button after closing the modal.
    public ?string $lastErrorsCsvPath = null;
    public ?string $lastStoredPath    = null;
    public int     $lastFailedCount   = 0;

    // Create/edit modal
    public bool   $showFormModal     = false;
    public ?int   $editId            = null;
    public string $f_question_number = '';
    public string $f_group_number    = '';
    public string $f_start_surah     = '';
    public string $f_start_ayah      = '';
    public string $f_start_page      = '';
    public string $f_end_surah       = '';
    public string $f_end_ayah        = '';
    public string $f_end_page        = '';

    public ?int $deleteId = null;

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

    public function updatingGroupFilter(): void
    {
        $this->resetPage();
    }

    // ────── Create / Edit ──────

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $q = RecitationQuestion::findOrFail($id);
        $this->editId            = $id;
        $this->f_question_number = (string) $q->question_number;
        $this->f_group_number    = (string) $q->group_number->value;
        $this->f_start_surah     = (string) $q->start_surah;
        $this->f_start_ayah      = (string) $q->start_ayah;
        $this->f_start_page      = (string) $q->start_page;
        $this->f_end_surah       = (string) $q->end_surah;
        $this->f_end_ayah        = (string) $q->end_ayah;
        $this->f_end_page        = (string) $q->end_page;
        $this->showFormModal     = true;
    }

    public function save(): void
    {
        $this->validate([
            'f_question_number' => ['required', 'integer', 'min:1'],
            'f_group_number'    => ['required', 'integer', 'between:1,6'],
            'f_start_surah'     => ['required', 'integer', 'between:1,114'],
            'f_start_ayah'      => ['required', 'integer', 'min:1'],
            'f_start_page'      => ['required', 'integer', 'between:1,604'],
            'f_end_surah'       => ['required', 'integer', 'between:1,114'],
            'f_end_ayah'        => ['required', 'integer', 'min:1'],
            'f_end_page'        => ['required', 'integer', 'between:1,604'],
        ], [], [
            'f_question_number' => 'رقم السؤال',
            'f_group_number'    => 'رقم المجموعة',
            'f_start_surah'     => 'سورة البداية',
            'f_start_ayah'      => 'آية البداية',
            'f_start_page'      => 'صفحة البداية',
            'f_end_surah'       => 'سورة النهاية',
            'f_end_ayah'        => 'آية النهاية',
            'f_end_page'        => 'صفحة النهاية',
        ]);

        $data = [
            'question_number' => (int) $this->f_question_number,
            'group_number'    => (int) $this->f_group_number,
            'start_surah'     => (int) $this->f_start_surah,
            'start_ayah'      => (int) $this->f_start_ayah,
            'start_page'      => (int) $this->f_start_page,
            'end_surah'       => (int) $this->f_end_surah,
            'end_ayah'        => (int) $this->f_end_ayah,
            'end_page'        => (int) $this->f_end_page,
        ];

        if ($this->editId) {
            $q = RecitationQuestion::findOrFail($this->editId);
            $q->update($data);
        } else {
            RecitationQuestion::create($data + ['is_active' => true]);
        }

        $this->showFormModal = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: $this->editId ? 'تم التحديث.' : 'تمت الإضافة.');
    }

    // ────── Delete ──────

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
    }

    public function deleteItem(): void
    {
        $q = RecitationQuestion::findOrFail($this->deleteId);
        $q->delete();
        $this->deleteId = null;
        $this->dispatch('notify', type: 'success', message: 'تم الحذف.');
    }

    // ────── Import ──────

    public function openImportModal(): void
    {
        $this->reset(['importFile', 'importMode']);
        $this->importMode = 'replace';
        $this->showImportModal = true;
    }

    public function runImport(RecitationQuestionImportService $service): void
    {
        $this->validate();

        try {
            $stats = $service->import(
                $this->importFile->getRealPath(),
                $this->importFile->getClientOriginalName(),
                in_array($this->importMode, ['replace', 'upsert'], true) ? $this->importMode : 'replace'
            );
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'فشل الاستيراد: ' . $e->getMessage());
            return;
        }

        // Slim the audit payload — full row data lives in the archived file.
        // Note: Auth::id() returns national_id here (User::getAuthIdentifierName
        // is overridden), so we must read ->id off the user explicitly.
        // Wrapped defensively: audit logging must never break a successful import.
        try {
            AuditLog::create([
                'user_id'    => Auth::user()->id,
                'action'     => 'recitation_questions_imported',
                'new_values' => [
                    'imported'        => $stats['imported'],
                    'updated'         => $stats['updated'],
                    'skipped'         => $stats['skipped'],
                    'failed_count'    => count($stats['failed_rows']),
                    'stored_path'     => $stats['stored_path'],
                    'errors_csv_path' => $stats['errors_csv_path'],
                    'mode'            => $this->importMode,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->lastStoredPath    = $stats['stored_path'];
        $this->lastErrorsCsvPath = $stats['errors_csv_path'];
        $this->lastFailedCount   = count($stats['failed_rows']);

        $this->showImportModal = false;
        $this->reset(['importFile']);

        $msg = sprintf(
            'تم استيراد %d سؤال، تحديث %d، تجاهل %d.',
            $stats['imported'], $stats['updated'], $stats['skipped']
        );
        if ($this->lastFailedCount > 0) {
            $msg .= " فشل {$this->lastFailedCount} سطر — حمّل سجل الأخطاء.";
        }

        $type = $this->lastFailedCount > 0 ? 'warning' : 'success';
        $this->dispatch('notify', type: $type, message: $msg);
    }

    public function downloadErrors()
    {
        abort_if(! $this->lastErrorsCsvPath, 404);
        abort_if(! Storage::disk('local')->exists($this->lastErrorsCsvPath), 404);

        return Storage::disk('local')->download(
            $this->lastErrorsCsvPath,
            'import-errors-' . now()->format('Ymd-His') . '.csv'
        );
    }

    public function dismissLastResult(): void
    {
        $this->lastErrorsCsvPath = null;
        $this->lastStoredPath    = null;
        $this->lastFailedCount   = 0;
    }

    public function render()
    {
        $items = RecitationQuestion::query()
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                if (ctype_digit($term)) {
                    $q->where(function ($qq) use ($term) {
                        $qq->where('question_number', $term)
                            ->orWhere('start_page', $term)
                            ->orWhere('end_page', $term);
                    });
                }
            })
            ->when($this->groupFilter !== '', fn($q) => $q->where('group_number', $this->groupFilter))
            ->orderBy('group_number')
            ->orderBy('question_number')
            ->paginate(30);

        return view('livewire.admin.recitation-questions.index', [
            'items'    => $items,
            'groups'   => QuestionGroup::cases(),
            'surahMap' => Surah::all(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editId            = null;
        $this->f_question_number = '';
        $this->f_group_number    = '';
        $this->f_start_surah     = '';
        $this->f_start_ayah      = '';
        $this->f_start_page      = '';
        $this->f_end_surah       = '';
        $this->f_end_ayah        = '';
        $this->f_end_page        = '';
        $this->resetValidation();
    }
}
