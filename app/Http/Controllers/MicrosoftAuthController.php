<?php

namespace App\Http\Controllers;

use App\Services\Microsoft\MicrosoftAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class MicrosoftAuthController extends Controller
{
    public function redirectToMicrosoft(MicrosoftAuthService $auth)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard.settings')->withErrors([
                'msg' => 'Confirm Microsoft linking from your account settings.',
            ]);
        }

        try {
            return redirect()->away($auth->authorizationUrl('login', null));
        } catch (Throwable) {
            return redirect()->route('login')->withErrors([
                'msg' => 'Microsoft sign-in is not configured.',
            ]);
        }
    }

    public function link(MicrosoftAuthService $auth)
    {
        try {
            return redirect()->away($auth->authorizationUrl('link', (int) Auth::id()));
        } catch (RuntimeException) {
            return redirect()->route('dashboard.settings')->withErrors([
                'msg' => 'Microsoft sign-in is not configured.',
            ]);
        }
    }

    public function callback(Request $request, MicrosoftAuthService $auth)
    {
        $result = $auth->handleCallback($request);
        $redirect = redirect()->route($result['route']);

        if ($result['ok'] && $result['success']) {
            return $redirect->with('success', $result['success']);
        }

        if (! $result['ok']) {
            return $redirect->withErrors(['msg' => $result['error']]);
        }

        return $redirect;
    }
}
