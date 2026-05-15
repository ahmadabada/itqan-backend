<?php

namespace App\Livewire\Examiner;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('لوحة المختبر')]
class Dashboard extends Component
{
    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Only examiners can access this panel
        if ($user->role !== UserRole::Examiner) {
            $this->redirect(route('admin.dashboard'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.examiner.dashboard', [
            'user' => Auth::user(),
        ]);
    }
}
