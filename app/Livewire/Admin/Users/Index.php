<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ArabicSearch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('المستخدمون')]
class Index extends Component
{
    // List filters
    public string $search     = '';
    public string $filterRole = '';

    // Create modal
    public bool   $showCreateModal = false;
    public string $form_national_id  = '';
    public string $form_first_name   = '';
    public string $form_second_name  = '';
    public string $form_third_name   = '';
    public string $form_family_name  = '';
    public string $form_role         = '';
    public string $form_password     = '';

    // Reset password modal
    public bool   $showResetModal = false;
    public ?int   $resetUserId    = null;
    public string $generatedPassword = '';

    // Delete confirm
    public ?int $deleteUserId = null;

    /** @var \App\Models\User */
    protected User $currentUser;

    public function mount(): void
    {
        $this->currentUser = Auth::user();

        // Only admins and super admins can access this page
        if ($this->currentUser->role === UserRole::Examiner) {
            $this->redirect(route('examiner.dashboard'));
        }
    }

    // ──────────────────────────────────────────
    // Create
    // ──────────────────────────────────────────

    public function openCreateModal(): void
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function createUser(): void
    {
        $currentUser = Auth::user();

        $allowedRoles = $currentUser->isSuperAdmin()
            ? [UserRole::Admin->value, UserRole::Examiner->value]
            : [UserRole::Examiner->value]; // BR-USR-04

        $this->validate([
            'form_national_id' => ['required', 'string', 'unique:users,national_id'],
            'form_first_name'  => ['required', 'string', 'max:50'],
            'form_second_name' => ['nullable', 'string', 'max:50'],
            'form_third_name'  => ['nullable', 'string', 'max:50'],
            'form_family_name' => ['required', 'string', 'max:50'],
            'form_role'        => ['required', 'in:' . implode(',', $allowedRoles)],
            'form_password'    => ['required', 'string', 'min:8'],
        ], [
            'form_national_id.required' => 'رقم الهوية مطلوب.',
            'form_national_id.unique'   => 'رقم الهوية مسجّل مسبقاً.',
            'form_first_name.required'  => 'الاسم الأول مطلوب.',
            'form_family_name.required' => 'اسم العائلة مطلوب.',
            'form_role.required'        => 'الدور مطلوب.',
            'form_role.in'              => 'الدور غير مسموح به.',
            'form_password.required'    => 'كلمة المرور مطلوبة.',
            'form_password.min'         => 'كلمة المرور 8 أحرف على الأقل.',
        ]);

        $user = User::create([
            'national_id'   => $this->form_national_id,
            'first_name'    => $this->form_first_name,
            'second_name'   => $this->form_second_name ?: null,
            'third_name'    => $this->form_third_name ?: null,
            'family_name'   => $this->form_family_name,
            'role'          => $this->form_role,
            'password_hash' => $this->form_password,
            'is_active'     => true,
        ]);

        // BR-AUDIT-02: Log user creation
        AuditLog::create([
            'user_id'     => $currentUser->id,
            'action'      => 'user_created',
            'target_type' => 'user',
            'target_id'   => $user->id,
            'new_values'  => ['national_id' => $user->national_id, 'role' => $user->role->value],
        ]);

        $this->showCreateModal = false;
        $this->resetCreateForm();
        $this->dispatch('notify', type: 'success', message: 'تم إضافة المستخدم بنجاح.');
    }

    // ──────────────────────────────────────────
    // Toggle active
    // ──────────────────────────────────────────

    public function toggleActive(int $userId): void
    {
        $currentUser = Auth::user();
        $target = User::findOrFail($userId);

        // BR-USR-01: Cannot disable super admin
        if ($target->is_super_admin) return;

        // BR-USR-04: Admin can only toggle examiners
        if (! $currentUser->isSuperAdmin() && $target->role !== UserRole::Examiner) return;

        $oldActive  = $target->is_active;
        $target->update(['is_active' => ! $oldActive]);

        // BR-AUDIT-02
        AuditLog::create([
            'user_id'     => $currentUser->id,
            'action'      => $target->is_active ? 'user_activated' : 'user_deactivated',
            'target_type' => 'user',
            'target_id'   => $target->id,
            'old_values'  => ['is_active' => $oldActive],
            'new_values'  => ['is_active' => $target->is_active],
        ]);

        $this->dispatch('notify', type: 'success', message: 'تم تحديث الحالة.');
    }

    // ──────────────────────────────────────────
    // Reset password (super admin only — BR-USR-03)
    // ──────────────────────────────────────────

    public function openResetModal(int $userId): void
    {
        if (! Auth::user()->isSuperAdmin()) return;

        $this->resetUserId       = $userId;
        $this->generatedPassword = Str::password(12, symbols: false);
        $this->showResetModal    = true;
    }

    public function confirmResetPassword(): void
    {
        $currentUser = Auth::user();
        if (! $currentUser->isSuperAdmin()) return; // BR-USR-03

        $target = User::findOrFail($this->resetUserId);

        $target->update(['password_hash' => $this->generatedPassword]);

        // BR-AUDIT-02
        AuditLog::create([
            'user_id'     => $currentUser->id,
            'action'      => 'password_reset',
            'target_type' => 'user',
            'target_id'   => $target->id,
        ]);

        $this->showResetModal = false;
        $this->dispatch('notify', type: 'success', message: 'تم إعادة ضبط كلمة المرور.');
    }

    // ──────────────────────────────────────────
    // Delete
    // ──────────────────────────────────────────

    public function confirmDelete(int $userId): void
    {
        $this->deleteUserId = $userId;
    }

    public function deleteUser(): void
    {
        $currentUser = Auth::user();
        $target      = User::findOrFail($this->deleteUserId);

        // BR-USR-01: Super admin cannot be deleted
        if ($target->is_super_admin) {
            $this->deleteUserId = null;
            return;
        }

        // BR-USR-02: Only super admin deletes admins
        if ($target->role === UserRole::Admin && ! $currentUser->isSuperAdmin()) {
            $this->deleteUserId = null;
            return;
        }

        // BR-AUDIT-02
        AuditLog::create([
            'user_id'     => $currentUser->id,
            'action'      => 'user_deleted',
            'target_type' => 'user',
            'target_id'   => $target->id,
            'old_values'  => ['national_id' => $target->national_id, 'role' => $target->role->value],
        ]);

        $target->delete();
        $this->deleteUserId = null;
        $this->dispatch('notify', type: 'success', message: 'تم حذف المستخدم.');
    }

    // ──────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────

    public function render()
    {
        $currentUser = Auth::user();

        $users = User::query()
            ->when($this->search, fn($q) => ArabicSearch::applyTo(
                $q,
                $this->search,
                ['first_name', 'second_name', 'third_name', 'family_name'],
                ['national_id'],
            ))
            ->when($this->filterRole, fn($q) => $q->where('role', $this->filterRole))
            ->orderByDesc('created_at')
            ->get();

        $roleOptions = $currentUser->isSuperAdmin()
            ? [UserRole::Admin->value => 'أدمن', UserRole::Examiner->value => 'مختبر']
            : [UserRole::Examiner->value => 'مختبر']; // BR-USR-04

        return view('livewire.admin.users.index', [
            'users'       => $users,
            'roleOptions' => $roleOptions,
            'currentUser' => $currentUser,
        ]);
    }

    private function resetCreateForm(): void
    {
        $this->form_national_id = '';
        $this->form_first_name  = '';
        $this->form_second_name = '';
        $this->form_third_name  = '';
        $this->form_family_name = '';
        $this->form_role        = '';
        $this->form_password    = '';
        $this->resetValidation();
    }
}
