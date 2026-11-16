<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Livewire\Concerns\LivewireAlert;

class Login extends Component
{
    use LivewireAlert;

    public $email, $password, $reset_email;

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'login:'.Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->reset('password');
            $this->alert('warning', 'Too many login attempts. Please try again later.');

            return;
        }

        $user = User::where('email', $this->email)->first();

        if ($user && Hash::check($this->password, $user->password)) {
            if (! $user->hasVerifiedEmail()) {
                $this->reset('password');
                $this->alert('warning', 'Please verify your email address before logging in.');

                return;
            }

            RateLimiter::clear($key);
            Auth::login($user);
            session()->regenerate();
            session(['user_id' => $user->id]);
            $this->reset('password');

            return redirect()->route('dashboard.settings');
        }

        RateLimiter::hit($key, 60);
        $this->reset('password');
        $this->alert('warning', 'Invalid email or password. Please try again.');
    }

    public function sendPasswordResetLink()
    {
        $this->validate([
            'reset_email' => 'required|email',
        ]);

        $key = 'password-reset:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->alert('warning', 'Please wait before requesting another password reset.');

            return;
        }

        RateLimiter::hit($key, 60);
        $status = Password::sendResetLink(['email' => $this->reset_email]);

        if ($status === Password::RESET_LINK_SENT) {
            User::query()->where('email', $this->reset_email)->update([
                'password_reset_token' => null,
                'password_token_created_at' => null,
            ]);
        }

        $this->alert('success', 'If that email is registered, a reset link has been sent.');
        $this->dispatch('close-modal');
        $this->reset('reset_email');
    }



    public function render()
    {
        return view('livewire.login');
    }
}
