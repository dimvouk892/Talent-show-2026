<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'Τα στοιχεία σύνδεσης δεν είναι έγκυρα.');

            return;
        }

        if (! Auth::user()->isAdmin()) {
            Auth::logout();
            $this->addError('email', 'Δεν έχετε δικαιώματα διαχειριστή.');

            return;
        }

        session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.login');
    }
}
