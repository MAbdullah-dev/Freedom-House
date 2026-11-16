<?php

namespace App\Services\Microsoft;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class MicrosoftIdTokenValidator
{
    /**
     * @return array{tid: string, oid: string, email: ?string}
     */
    public function validate(string $jwt, string $nonce, string $clientId, string $tenant): array
    {
        $header = $this->header($jwt);
        if (($header['alg'] ?? null) !== 'RS256') {
            throw new RuntimeException('Microsoft ID token could not be verified.');
        }

        JWT::$leeway = 60;

        try {
            $claims = JWT::decode($jwt, JWK::parseKeySet($this->jwks($tenant), 'RS256'));
        } catch (Throwable) {
            throw new RuntimeException('Microsoft ID token could not be verified.');
        }

        $audience = $claims->aud ?? null;
        $audiences = is_array($audience) ? $audience : [$audience];
        if (! in_array($clientId, $audiences, true)) {
            throw new RuntimeException('Microsoft ID token could not be verified.');
        }

        $tid = (string) ($claims->tid ?? '');
        $oid = (string) ($claims->oid ?? '');
        if (! $this->isGuid($tid) || ! $this->isObjectId($oid)) {
            throw new RuntimeException('Microsoft ID token could not be verified.');
        }

        $issuer = 'https://login.microsoftonline.com/'.$tid.'/v2.0';
        if (! hash_equals($issuer, (string) ($claims->iss ?? ''))) {
            throw new RuntimeException('Microsoft ID token could not be verified.');
        }

        if (! $this->tenantAllows($tenant, $tid)) {
            throw new RuntimeException('Microsoft ID token could not be verified.');
        }

        $tokenNonce = (string) ($claims->nonce ?? '');
        if ($tokenNonce === '' || ! hash_equals($nonce, $tokenNonce)) {
            throw new RuntimeException('Microsoft ID token could not be verified.');
        }

        return [
            'tid' => strtolower($tid),
            'oid' => strtolower($oid),
            'email' => $this->emailClaim($claims),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jwks(string $tenant): array
    {
        $jwks = Cache::remember('microsoft.jwks.'.$tenant, 600, function () use ($tenant) {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('https://login.microsoftonline.com/'.$tenant.'/discovery/v2.0/keys');

            if ($response->failed() || ! is_array($response->json('keys')) || $response->json('keys') === []) {
                throw new RuntimeException('Microsoft signing keys are unavailable.');
            }

            return $response->json();
        });

        return is_array($jwks) ? $jwks : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function header(string $jwt): array
    {
        $segment = explode('.', $jwt)[0] ?? '';
        $decoded = json_decode(base64_decode(strtr($segment, '-_', '+/'), true) ?: '', true);

        return is_array($decoded) ? $decoded : [];
    }

    private function tenantAllows(string $configured, string $tid): bool
    {
        if (in_array($configured, ['common', 'organizations', 'consumers'], true)) {
            return true;
        }

        return hash_equals(strtolower($configured), strtolower($tid));
    }

    private function isGuid(string $value): bool
    {
        return preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $value) === 1;
    }

    private function isObjectId(string $value): bool
    {
        return preg_match('/^[0-9a-zA-Z-]{8,64}$/', $value) === 1 && ! str_contains($value, ':');
    }

    private function emailClaim(object $claims): ?string
    {
        foreach (['email', 'preferred_username'] as $claim) {
            $value = $claims->{$claim} ?? null;
            if (! is_string($value)) {
                continue;
            }
            $value = trim($value);
            if (strlen($value) <= 100 && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        return null;
    }
}
