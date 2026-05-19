<?php

namespace App\Livewire\Admin;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('لوحة التحكم')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'user'          => Auth::user(),
            'totalStudents' => Student::count(),
            'totalUsers'    => User::where('is_super_admin', false)->count(),
            'totalExams'    => Exam::count(),
            'approvedExams' => Exam::where('status', ExamStatus::Approved)->count(),
        ]);
    }
}
