<?php

use App\Enums\UserRole;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Examiner\Dashboard as ExaminerDashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Root redirect based on auth state and role
Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return redirect(
        Auth::user()->role === UserRole::Examiner
            ? route('examiner.dashboard')
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
});

// Examiner panel
Route::middleware('auth')->prefix('examiner')->name('examiner.')->group(function () {
    Route::get('dashboard', ExaminerDashboard::class)->name('dashboard');
});
