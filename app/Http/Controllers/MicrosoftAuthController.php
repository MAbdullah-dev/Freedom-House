<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class MicrosoftAuthController extends Controller
{

    public function redirectToMicrosoft()
    {
        $clientId = env('AZURE_CLIENT_ID');
        $redirectUri = env('AZURE_REDIRECT_URI');
        $scopes = 'openid profile email offline_access User.read OnlineMeetings.ReadWrite';
        $state = csrf_token();

        $url = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?" . http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => $scopes,
            'state' => $state,
        ]);

        return redirect($url);
    }
    public function callback(Request $request)
    {
        Log::info('Microsoft callback triggered', ['request' => $request->all()]);

        if ($request->has('error')) {
            return redirect()->route('register')->withErrors(['msg' => 'Microsoft login failed.']);
        }

        $code = $request->input('code');
        Log::info('Code received: ' . $code);

        //get the access token
        $tokenResponse = Http::asForm()->post("https://login.microsoftonline.com/common/oauth2/v2.0/token", [
            'client_id' => env('AZURE_CLIENT_ID'),
            'client_secret' => env('AZURE_CLIENT_SECRET'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => env('AZURE_REDIRECT_URI'),
        ]);

        Log::info('Token response', ['response' => $tokenResponse->json()]);

        if ($tokenResponse->failed()) {
            return redirect()->route('register')->withErrors(['msg' => 'Failed to retrieve access token.']);
        }

        $accessToken = $tokenResponse->json('access_token');
        // $parts = explode('.', $accessToken);
        // $payload = json_decode(base64_decode($parts[1]), true);
        // Log::info('Access Token Payload:', $payload);
        $refreshToken = $tokenResponse->json('refresh_token');
        session(['refresh_token' => $refreshToken]);
        $tokenExpiresAt = now()->addSeconds($tokenResponse->json('expires_in'));

        //fetching user microsoft profile data
        $userResponse = Http::withToken($accessToken)->get('https://graph.microsoft.com/v1.0/me');
        Log::info('Microsoft user response', ['user' => $userResponse->json()]);

        if ($userResponse->failed()) {
            Log::error('Failed to fetch user information', ['response' => $userResponse->json()]);
            return redirect()->route('register')->withErrors(['msg' => 'Failed to fetch user information.']);
        }

        //microsoft userData
        $microsoftUser = $userResponse->json();
        Log::info('Microsoft user data:', $microsoftUser);

        $role_id = 3;

        $user = User::updateOrCreate(
            ['email' => $microsoftUser['mail'] ?? $microsoftUser['userPrincipalName']],
            [
                'first_name' => $microsoftUser['givenName'],
                'last_name' => $microsoftUser['surname'],
                'microsoft_id' => $microsoftUser['id'],
                'microsoft_account' => true,
                'password' => bcrypt(Str::random(16)),
                'phone' => null,
                'token_expires_at' => $tokenExpiresAt,
            ]
        );

        $role = Role::findOrFail($role_id);
        $user->roles()->attach($role->id);

        Log::info('User created or updated from Microsoft login', ['user' => $user]);

        Auth::login($user);

        //store access token in session
        session(['access_token' => $accessToken]);

        return redirect()->route('dashboard.settings');
    }
}
