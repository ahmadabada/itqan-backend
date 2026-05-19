<?php

namespace App\Livewire\Admin\Merges;

use App\Enums\UserRole;
use App\Models\MergeOperation;
use App\Services\MergeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('سجل عمليات الدمج')]
class History extends Component
{
    use WithPagination;

    public string $statusFilter = 'active'; // active | undone | all

    // Undo modal state
    public ?int   $undoOperationId = null;
    public string $undoNotes       = '';

    public function mount(): void
    {
        if (Auth::user()->role === UserRole::Examiner) {
            $this->redirect(route('examiner.dashboard'));
        }
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openUndoModal(int $operationId): void
    {
        $this->undoOperationId = $operationId;
        $this->undoNotes       = '';
    }

    public function closeUndoModal(): void
    {
        $this->undoOperationId = null;
        $this->undoNotes       = '';
    }

    public function performUndo(MergeService $merger): void
    {
        if ($this->undoOperationId === null) {
            return;
        }

        try {
            $merger->undo(
                mergeOperationId: $this->undoOperationId,
                adminUserId:      Auth::user()->id, // Auth::id() returns national_id (User override)
                notes:            $this->undoNotes ?: null,
            );
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'فشل التراجع: ' . $e->getMessage());
            return;
        }

        $this->undoOperationId = null;
        $this->undoNotes       = '';
        $this->dispatch('notify', type: 'success', message: 'تم التراجع عن الدمج.');
    }

    public function render()
    {
        $operations = MergeOperation::query()
            ->when($this->statusFilter === 'active', fn($q) => $q->whereNull('undone_at'))
            ->when($this->statusFilter === 'undone', fn($q) => $q->whereNotNull('undone_at'))
            ->with([
                'masterStudent:id,national_id,first_name,family_name',
                'performedBy:id,first_name,family_name',
                'undoneBy:id,first_name,family_name',
            ])
            ->orderByDesc('performed_at')
            ->paginate(25);

        return view('livewire.admin.merges.history', [
            'operations' => $operations,
        ]);
    }
}
