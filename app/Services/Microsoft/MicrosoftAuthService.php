<?php

namespace App\Services\Microsoft;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class MicrosoftAuthService
{
    public const SESSION_KEY = 'microsoft_oauth';

    public function __construct(
        private MicrosoftIdTokenValidator $tokens,
        private MicrosoftIdentityStore $identities
    ) {}

    public function authorizationUrl(string $purpose, ?int $userId): string
    {
        $tenant = $this->tenant();
        $clientId = (string) config('services.microsoft.client_id');
        $redirect = (string) config('services.microsoft.redirect');
        if ($clientId === '' || $redirect === '' || $tenant === null) {
            throw new RuntimeException('Microsoft sign-in is not configured.');
        }

        $state = $this->random();
        $nonce = $this->random();
        $verifier = $this->random(64);
        session([self::SESSION_KEY => [
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'purpose' => $purpose,
            'user_id' => $userId,
        ]]);

        $query = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirect,
            'response_mode' => 'query',
            'scope' => 'openid profile email offline_access User.Read OnlineMeetings.ReadWrite Calendars.ReadWrite',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $this->challenge($verifier),
            'code_challenge_method' => 'S256',
        ]);

        return 'https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/authorize?'.$query;
    }

    /**
     * @return array{ok: bool, route: string, error: ?string, success: ?string}
     */
    public function handleCallback(Request $request): array
    {
        $failed = $this->failure(Auth::check() ? 'dashboard.settings' : 'login');

        $code = $request->query('code');
        $state = $request->query('state');
        $pending = session()->pull(self::SESSION_KEY);
        if ($request->filled('error') || ! is_string($code) || $code === '' || ! is_string($state) || ! is_array($pending)) {
            return $failed;
        }
        if (! hash_equals((string) ($pending['state'] ?? ''), $state)) {
            return $failed;
        }

        $tenant = $this->tenant();
        $clientId = (string) config('services.microsoft.client_id');
        if ($tenant === null || $clientId === '') {
            return $failed;
        }

        try {
            $tokenResponse = Http::asForm()
                ->timeout(10)
                ->post('https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/token', [
                    'client_id' => $clientId,
                    'client_secret' => config('services.microsoft.client_secret'),
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => config('services.microsoft.redirect'),
                    'code_verifier' => $pending['verifier'] ?? '',
                ]);
        } catch (Throwable) {
            return $failed;
        }

        $idToken = $tokenResponse->json('id_token');
        if ($tokenResponse->failed() || ! is_string($idToken) || $idToken === '') {
            return $failed;
        }

        try {
            $claims = $this->tokens->validate($idToken, (string) ($pending['nonce'] ?? ''), $clientId, $tenant);
        } catch (Throwable) {
            return $failed;
        }

        $expiresAt = is_numeric($tokenResponse->json('expires_in'))
            ? Carbon::now()->addSeconds((int) $tokenResponse->json('expires_in'))
            : null;
        $refreshToken = $tokenResponse->json('refresh_token');
        $refreshToken = is_string($refreshToken) ? $refreshToken : null;

        if (($pending['purpose'] ?? null) === 'link') {
            $sessionUserId = $pending['user_id'] ?? null;
            if (! Auth::check() || ! is_numeric($sessionUserId) || (int) Auth::id() !== (int) $sessionUserId) {
                return $this->failure('dashboard.settings');
            }

            $status = $this->identities->link((int) $sessionUserId, $claims['tid'], $claims['oid'], $refreshToken, $expiresAt);

            return match ($status) {
                MicrosoftIdentityStore::LINKED => [
                    'ok' => true,
                    'route' => 'dashboard.settings',
                    'error' => null,
                    'success' => 'Microsoft account connected successfully.',
                ],
                MicrosoftIdentityStore::DUPLICATE => $this->failure('dashboard.settings', 'This Microsoft account is already connected to another user.'),
                MicrosoftIdentityStore::REASSIGNMENT => $this->failure('dashboard.settings', 'This account is already connected to a different Microsoft identity.'),
                default => $this->failure('dashboard.settings'),
            };
        }

        $result = $this->identities->authenticate($claims['tid'], $claims['oid'], $claims['email'], $refreshToken, $expiresAt);
        if ($result['status'] !== MicrosoftIdentityStore::AUTHENTICATED || $result['user'] === null) {
            $message = match ($result['status']) {
                MicrosoftIdentityStore::COLLISION => 'An account with this email already exists. Sign in and connect Microsoft from your account settings.',
                MicrosoftIdentityStore::DUPLICATE => 'This Microsoft account is already connected to another user.',
                MicrosoftIdentityStore::LEGACY_UNCONFIRMED => 'Sign in with your password, then connect Microsoft from account settings.',
                default => null,
            };

            return $this->failure('login', $message);
        }

        if (Auth::check() && (int) Auth::id() !== (int) $result['user']->id) {
            return $this->failure('login');
        }

        Auth::login($result['user']);
        session()->regenerate();

        return [
            'ok' => true,
            'route' => 'dashboard',
            'error' => null,
            'success' => null,
        ];
    }

    private function tenant(): ?string
    {
        $tenant = (string) config('services.microsoft.tenant');
        if ($tenant === '') {
            $tenant = 'common';
        }

        if (preg_match('/^(common|organizations|consumers|[0-9a-fA-F-]{36})$/', $tenant) !== 1) {
            return null;
        }

        return strtolower($tenant);
    }

    private function random(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    private function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * @return array{ok: bool, route: string, error: ?string, success: ?string}
     */
    private function failure(string $route, ?string $message = null): array
    {
        return [
            'ok' => false,
            'route' => $route,
            'error' => $message ?? 'Microsoft sign-in could not be verified.',
            'success' => null,
        ];
    }
}
