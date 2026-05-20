<?php

use App\Enums\UserRole;
use App\Livewire\Admin\AuditLogs\Index as AdminAuditLogs;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Devices\Index as AdminDevices;
use App\Livewire\Admin\Exams\Index as AdminExamsIndex;
use App\Livewire\Admin\Exams\Show as AdminExamShow;
use App\Livewire\Admin\Merges\History as AdminMergesHistory;
use App\Livewire\Admin\Merges\Index as AdminMerges;
use App\Livewire\Admin\Merges\Show as AdminMergeShow;
use App\Livewire\Admin\Permits\Index as AdminPermits;
use App\Livewire\Admin\RecitationQuestions\Index as AdminRecitationQuestions;
use App\Livewire\Admin\SuggestedStudents\Index as AdminSuggestedStudents;
use App\Livewire\Admin\Settings\Index as AdminSettings;
use App\Livewire\Admin\Students\Index as AdminStudents;
use App\Livewire\Admin\Students\Show as AdminStudentShow;
use App\Livewire\Admin\Users\Index as AdminUsers;
use App\Livewire\Admin\Users\Trashed as AdminUsersTrashed;
use App\Livewire\Auth\Login;
use App\Livewire\Examiner\Dashboard as ExaminerDashboard;
use App\Livewire\Examiner\ExamSession;
use App\Livewire\Examiner\MyExams as ExaminerMyExams;
use App\Livewire\Public\ResultQuery;
use App\Http\Controllers\ResultPdfController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Root redirect based on auth state and role
Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return redirect(
        Auth::user()->role === UserRole::Examiner
            ? route('examiner.exam')
            : route('admin.dashboard')
    );
});

// Guest-only routes
Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
});

// Logout
Route::post('logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Admin panel (super_admin + admin)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard',         AdminDashboard::class)->name('dashboard');
    Route::get('users',             AdminUsers::class)->name('users');
    Route::get('users/trashed',     AdminUsersTrashed::class)->name('users.trashed');
    Route::get('students',              AdminStudents::class)->name('students');
    Route::get('students/{student}',    AdminStudentShow::class)->name('students.show')->whereNumber('student');
    Route::get('recitation-questions',  AdminRecitationQuestions::class)->name('recitation-questions');
    Route::get('suggested-students',    AdminSuggestedStudents::class)->name('suggested-students');
    Route::get('permits',               AdminPermits::class)->name('permits');
    Route::get('exams',             AdminExamsIndex::class)->name('exams.index');
    Route::get('exams/{exam}',      AdminExamShow::class)->name('exams.show')->whereNumber('exam');
    Route::get('merges',                       AdminMerges::class)->name('merges');
    Route::get('merges/history',               AdminMergesHistory::class)->name('merges.history');
    Route::get('merges/history/{operation}',   AdminMergeShow::class)->name('merges.show')->whereNumber('operation');
    Route::get('devices',           AdminDevices::class)->name('devices');
    Route::get('audit-logs',        AdminAuditLogs::class)->name('audit-logs');
    Route::get('settings',          AdminSettings::class)->name('settings');
});

// Examiner panel
Route::middleware('auth')->prefix('examiner')->name('examiner.')->group(function () {
    Route::get('dashboard', ExaminerDashboard::class)->name('dashboard');
    Route::get('exam',      ExamSession::class)->name('exam');
    Route::get('exams',     ExaminerMyExams::class)->name('exams');
});

// Public — no auth required (BR-QUERY-01)
Route::get('results',           ResultQuery::class)->name('public.results');
Route::get('results/{exam}/pdf', ResultPdfController::class)->name('public.result-pdf');
