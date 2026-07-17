<?php

namespace App\Livewire\Examiner;

use Livewire\Component;

// The examiner's landing page is now the shared students screen.
class Dashboard extends Component
{
    public function mount(): void
    {
        $this->redirect(route('examiner.students'), navigate: true);
    }

    public function render()
    {
        return view('livewire.examiner.dashboard');
    }
}
