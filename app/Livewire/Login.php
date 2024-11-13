<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class Login extends Component
{
    use LivewireAlert;

    public $email, $password;

    public function login()
    {
        $validatedData = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $this->email)->first();

        if ($user && Hash::check($this->password, $user->password)) {
            if ($user->hasVerifiedEmail()) {
                Auth::login($user);

                session([
                    'user_id' => $user->id,
                    'role_id' => $user->roles()->wherePivot('user_id', $user->id)->first()->pivot->role_id,
                ]);

                return redirect()->route('dashboard.settings');
            } else {
                $this->alert('warning', 'Please verify your email address before logging in.');
                // session()->flash('error', 'Please verify your email address before logging in.');
                return;
            }
        }

        // session()->flash('error', 'Invalid email or password. Please try again.');
        $this->alert('warning', 'Invalid email or password. Please try again.');
    }


    public function render()
    {
        return view('livewire.login');
    }
}
