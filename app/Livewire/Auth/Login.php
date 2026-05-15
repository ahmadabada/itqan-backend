<?php

namespace App\Livewire\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// BR-AUTH-01: Login via national_id + password, session-based
#[Layout('layouts.guest')]
#[Title('تسجيل الدخول')]
class Login extends Component
{
    public string $national_id = '';
    public string $password    = '';
    public bool   $remember    = false;

    protected function rules(): array
    {
        return [
            'national_id' => ['required', 'string'],
            'password'    => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'national_id.required' => 'رقم الهوية مطلوب.',
            'password.required'    => 'كلمة المرور مطلوبة.',
        ];
    }

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['national_id' => $this->national_id, 'password' => $this->password], $this->remember)) {
            $this->addError('national_id', 'رقم الهوية أو كلمة المرور غير صحيحة.');
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $this->addError('national_id', 'هذا الحساب غير مفعّل. تواصل مع المدير.');
            return;
        }

        $user->forceFill(['last_login_at' => now()])->save();

        session()->regenerate();

        $this->redirect(
            $user->role === UserRole::Examiner
                ? route('examiner.dashboard')
                : route('admin.dashboard'),
            navigate: true,
        );
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
