<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Mail\EmailVerificationMail;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class Register extends Component
{
    use LivewireAlert;

    public $first_name, $last_name, $email, $phone, $password, $confirm_password, $role_id;

    public function register()
    {
        // dd(request()->all());
        $this->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users|max:100',
            'phone' => 'required|string|max:15',
            'password' => 'required|min:8|same:confirm_password',
        ]);

        try {
            DB::beginTransaction();

            $verificationToken = Str::random(60);

            $role_id = 3;

            $user = User::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => Hash::make($this->password),
                'verification_token' => $verificationToken,
            ]);
            $role = Role::findOrFail($role_id);
            
            $user->roles()->attach($role->id);
            
            $this->sendVerificationEmail($user);
            // dd(request());
            DB::commit();
            $this->alert('success', 'Registration successful! Please check your email to verify your account.');
            // session()->flash('success', 'Registration successful! Please check your email to verify your account.');
            $this->resetForm();
            // return redirect()->route('login');
        } catch (\Exception $e) {
            DB::rollBack();
            // session()->flash('error', 'Something went wrong. Please try again.');
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
