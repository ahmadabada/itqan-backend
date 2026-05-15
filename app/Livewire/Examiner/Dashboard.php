<?php

namespace App\Livewire\Examiner;

use Livewire\Component;

// Redirect to ExamSession — the examiner's main page
class Dashboard extends Component
{
    public function mount(): void
    {
        $this->redirect(route('examiner.exam'), navigate: true);
    }

    public function render()
    {
        return view('livewire.examiner.dashboard');
    }
}
