<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    public function showResetForm(Request $request, $token)
    {
        return view('emails.password-reset', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->only('email'));
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'password_reset_token' => null,
                    'password_token_created_at' => null,
                ])->save();

                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                if ((int) Auth::id() === (int) $user->id) {
                    Auth::logout();
                    session()->invalidate();
                    session()->regenerateToken();
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password reset successfully!');
        }

        return back()->withErrors(['email' => 'Invalid or expired token.'])->withInput($request->only('email'));
    }
}
