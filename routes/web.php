<?php

use App\Enums\UserRole;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Permits\Index as AdminPermits;
use App\Livewire\Admin\Settings\Index as AdminSettings;
use App\Livewire\Admin\Students\Index as AdminStudents;
use App\Livewire\Admin\Users\Index as AdminUsers;
use App\Livewire\Auth\Login;
use App\Livewire\Examiner\Dashboard as ExaminerDashboard;
use App\Livewire\Examiner\ExamSession;
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
    Route::get('dashboard', AdminDashboard::class)->name('dashboard');
    Route::get('users',     AdminUsers::class)->name('users');
    Route::get('students',  AdminStudents::class)->name('students');
    Route::get('permits',   AdminPermits::class)->name('permits');
    Route::get('settings',  AdminSettings::class)->name('settings');
});

// Examiner panel
Route::middleware('auth')->prefix('examiner')->name('examiner.')->group(function () {
    Route::get('dashboard', ExaminerDashboard::class)->name('dashboard');
    Route::get('exam',      ExamSession::class)->name('exam');
});

// Public — no auth required (BR-QUERY-01)
Route::get('results',           ResultQuery::class)->name('public.results');
Route::get('results/{exam}/pdf', ResultPdfController::class)->name('public.result-pdf');
