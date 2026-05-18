<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ArabicSearch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// BR-USR-09: Soft-deleted users can be restored by an admin with permission
// to manage that role (super admin → admins; admin → examiners).
#[Layout('layouts.admin')]
#[Title('المستخدمون المحذوفون')]
class Trashed extends Component
{
    public string $search       = '';
    public ?int   $restoreUserId = null;

    public function mount(): void
    {
        if (Auth::user()->role === UserRole::Examiner) {
            $this->redirect(route('examiner.dashboard'));
        }
    }

    public function confirmRestore(int $userId): void
    {
        $this->restoreUserId = $userId;
    }

    public function restoreUser(): void
    {
        $currentUser = Auth::user();
        $target      = User::onlyTrashed()->findOrFail($this->restoreUserId);

        // BR-USR-02 (mirrored): only super admin can restore an admin
        if ($target->role === UserRole::Admin && ! $currentUser->isSuperAdmin()) {
            $this->restoreUserId = null;
            $this->dispatch('notify', type: 'danger', message: 'فقط السوبر أدمن يستعيد الأدمنز.');
            return;
        }

        $target->restore();

        // BR-AUDIT-02
        AuditLog::create([
            'user_id'     => $currentUser->id,
            'action'      => 'user_restored',
            'target_type' => 'user',
            'target_id'   => $target->id,
            'new_values'  => ['national_id' => $target->national_id, 'role' => $target->role->value],
        ]);

        $this->restoreUserId = null;
        $this->dispatch('notify', type: 'success', message: 'تم استعادة المستخدم.');
    }

    public function render()
    {
        $currentUser = Auth::user();

        $users = User::onlyTrashed()
            ->when($this->search, fn($q) => ArabicSearch::applyTo(
                $q,
                $this->search,
                ['first_name', 'second_name', 'third_name', 'family_name'],
                ['national_id'],
            ))
            ->orderByDesc('deleted_at')
            ->get();

        return view('livewire.admin.users.trashed', [
            'users'       => $users,
            'currentUser' => $currentUser,
        ]);
    }
}
