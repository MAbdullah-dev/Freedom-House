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
                         'role_ids' => $user->roles()->pluck('id')->toArray(),                
                       ]);


                return redirect()->route('dashboard.settings');
            } else {
                $this->alert('warning', 'Please verify your email address before logging in.');
                
                return;
            }
        }

        $this->alert('warning', 'Invalid email or password. Please try again.');
    }


    public function render()
    {
        return view('livewire.login');
    }
}
