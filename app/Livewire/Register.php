<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Mail\EmailVerificationMail;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use App\Livewire\Concerns\LivewireAlert;

class Register extends Component
{
    use LivewireAlert;

    public $first_name, $last_name, $email, $phone, $password, $confirm_password;

    public function register()
    {
        $key = 'register:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->reset('password', 'confirm_password');
            $this->alert('warning', 'Too many registration attempts. Please try again later.');

            return;
        }

        $this->validate([
            'first_name' => 'required|string|max:20',
            'last_name' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email|max:100',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|min:8|same:confirm_password',
        ]);

        RateLimiter::hit($key, 60);

        $role = Role::findByName(Role::LEARNER);
        if ($role === null) {
            $this->reset('password', 'confirm_password');
            $this->alert('error', 'Registration is unavailable.');

            return;
        }

        try {
            DB::beginTransaction();

            $verificationToken = Str::random(60);

            $user = User::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => Hash::make($this->password),
                'verification_token' => $verificationToken,
                'microsoft_account' => false,
                'role_id' => $role->id,
                'timezone' => 'UTC',
            ]);


            $this->sendVerificationEmail($user);
            // dd(request());
            DB::commit();
            $this->alert('success', 'Registration successful! Please check your email to verify your account.');
            Notification::create([
                'user_id' => $user->id,
                'message' => '🎉 Welcome to our platform! We are thrilled to have you on board. 🚀',
                'type' => 'general',
            ]);
            $this->resetForm();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->reset('password', 'confirm_password');
            $this->alert('error', 'Something went wrong. Please try again.');
            return;
        }
    }

    protected function sendVerificationEmail(User $user)
    {
        $verificationUrl = route('verify.email', ['token' => $user->verification_token]);
        Mail::to($user->email)->send(new EmailVerificationMail($verificationUrl));
    }
    private function resetForm()
    {
        $this->reset(['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password']);
    }


    public function render()
    {
        return view('livewire.register');
    }
}
