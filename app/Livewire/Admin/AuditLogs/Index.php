<?php

namespace App\Livewire\Admin\AuditLogs;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('سجل النشاط')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'action')]
    public string $actionFilter = '';

    #[Url(as: 'user')]
    public string $userFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDir = 'desc';

    public array $expanded = [];

    public const ACTION_LABELS = [
        'exam_approved'                  => 'اعتماد اختبار',
        'exam_excluded'                  => 'استبعاد اختبار',
        'merge_students'                 => 'دمج طلاب',
        'undo_merge'                     => 'التراجع عن دمج',
        'device_wipe_issued'             => 'أمر مسح جهاز',
        'settings_updated'               => 'تحديث الإعدادات',
        'user_created'                   => 'إنشاء مستخدم',
        'user_activated'                 => 'تفعيل مستخدم',
        'user_deactivated'               => 'تعطيل مستخدم',
        'user_deleted'                   => 'حذف مستخدم',
        'user_restored'                  => 'استرجاع مستخدم',
        'password_reset'                 => 'إعادة كلمة المرور',
        'student_created'                => 'إنشاء طالب',
        'student_updated'                => 'تحديث طالب',
        'student_deleted'                => 'حذف طالب',
        'suggested_students_imported'    => 'استيراد طلاب مقترحين',
        'suggested_students_cleared'     => 'مسح الطلاب المقترحين',
        'recitation_questions_imported'  => 'استيراد أسئلة التلاوة',
        'reexam_permit_granted'          => 'منح إذن إعادة',
    ];

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            $this->redirect(
                $user->role === UserRole::Examiner ? route('examiner.dashboard') : route('admin.dashboard'),
            );
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['actionFilter', 'userFilter', 'dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }

    public function toggleExpand(int $id): void
    {
        if (in_array($id, $this->expanded, true)) {
            $this->expanded = array_values(array_diff($this->expanded, [$id]));
        } else {
            $this->expanded[] = $id;
        }
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['user_id', 'created_at'], true)) {
            return;
        }
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = $column === 'user_id' ? 'asc' : 'desc';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['actionFilter', 'userFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $sortBy  = in_array($this->sortBy, ['user_id', 'created_at'], true) ? $this->sortBy : 'created_at';
        $sortDir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        $query = AuditLog::query()
            ->when($this->actionFilter, fn($q) => $q->where('action', $this->actionFilter))
            ->when($this->userFilter, fn($q) => $q->where('user_id', $this->userFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->with(['user:id,first_name,family_name,role']);

        // When sorting by user, sub-order by date DESC so each user's most
        // recent activity surfaces first inside their group.
        if ($sortBy === 'user_id') {
            $query->orderBy('user_id', $sortDir)->orderByDesc('created_at');
        } else {
            $query->orderBy('created_at', $sortDir);
        }

        $logs = $query->paginate(30);

        // Distinct actions actually present in the table — keeps the dropdown
        // free of action keys we've never logged.
        $distinctActions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $users = User::whereIn('id', AuditLog::query()->select('user_id')->distinct())
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'second_name', 'third_name', 'family_name']);

        return view('livewire.admin.audit-logs.index', [
            'logs'            => $logs,
            'distinctActions' => $distinctActions,
            'users'           => $users,
            'actionLabels'    => self::ACTION_LABELS,
        ]);
    }
}
