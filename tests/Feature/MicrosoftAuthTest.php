<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\Microsoft\MicrosoftAuthService;
use App\Services\Microsoft\MicrosoftIdentityStore;
use App\Services\MicrosoftGraphService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MicrosoftAuthTest extends TestCase
{
    use DatabaseTransactions;

    private const CLIENT = 'azure-client-test';

    private const TID = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

    private const OID = '11111111-2222-4333-8444-555555555555';

    private string $privateKey;

    private string $otherPrivateKey;

    private array $jwks;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
        config([
            'services.microsoft.client_id' => self::CLIENT,
            'services.microsoft.client_secret' => 'azure-client-secret',
            'services.microsoft.redirect' => route('microsoft.callback'),
            'services.microsoft.tenant' => 'common',
        ]);

        $this->privateKey = $this->rsaKey();
        $this->otherPrivateKey = $this->rsaKey();
        $this->jwks = ['keys' => [$this->jwk($this->privateKey, 'test-key')]];
        Role::query()->firstOrCreate(['role_name' => Role::LEARNER]);
    }

    public function test_valid_state_nonce_and_pkce_sign_in_a_new_learner(): void
    {
        $query = $this->startLogin();
        $pending = session(MicrosoftAuthService::SESSION_KEY);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $pending['verifier'], true)), '+/', '-_'), '=');

        $this->assertSame($challenge, $query['code_challenge']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame($pending['nonce'], $query['nonce']);
        $this->assertSame($pending['state'], $query['state']);

        $this->complete($query)->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'new-learner@example.test')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRoleName(Role::LEARNER));
        $this->assertNull($user->email_verified_at);
        $this->assertSame(self::TID, $user->microsoft_tenant_id);
        $this->assertSame(self::OID, $user->microsoft_id);
        $this->assertSame(MicrosoftIdentityStore::identityKey(self::TID, self::OID), $user->microsoft_identity_key);
        $this->assertTrue((bool) $user->microsoft_account);
        $this->assertSame('refresh-secret-value', $user->refresh_token);
        $this->assertNotSame('refresh-secret-value', DB::table('users')->where('id', $user->id)->value('refresh_token'));
        $this->assertNull(session(MicrosoftAuthService::SESSION_KEY));
    }

    public function test_missing_or_mismatched_state_does_not_authenticate(): void
    {
        $this->fakeMicrosoft($this->token());
        $this->get(route('microsoft.callback', ['code' => 'auth-code-value', 'state' => 'missing']))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $query = $this->startLogin();
        $this->fakeMicrosoft($this->token());
        $this->get(route('microsoft.callback', ['code' => 'auth-code-value', 'state' => 'wrong-state']))
            ->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNull(session(MicrosoftAuthService::SESSION_KEY));
        $this->assertSame(0, User::query()->where('email', 'new-learner@example.test')->count());
        $this->assertNotEmpty($query['state']);
    }

    public function test_a_replayed_callback_does_not_create_another_account(): void
    {
        $query = $this->startLogin();
        $token = $this->token();
        $this->fakeMicrosoft($token);
        $this->get(route('microsoft.callback', ['code' => 'auth-code-value', 'state' => $query['state']]))
            ->assertRedirect(route('dashboard'));

        $this->fakeMicrosoft($token);
        $this->get(route('microsoft.callback', ['code' => 'auth-code-value', 'state' => $query['state']]))
            ->assertRedirect(route('dashboard.settings'));

        $this->assertSame(1, User::query()->where('microsoft_id', self::OID)->count());
        $this->assertAuthenticated();
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $query = $this->startLogin();
        $this->complete($query, [], $this->otherPrivateKey)->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(0, User::query()->where('microsoft_id', self::OID)->count());
    }

    public function test_the_wrong_audience_or_issuer_or_tenant_is_rejected(): void
    {
        $query = $this->startLogin();
        $this->complete($query, ['aud' => 'some-other-client'])->assertRedirect(route('login'));
        $this->assertGuest();

        $query = $this->startLogin();
        $this->complete($query, ['iss' => 'https://evil.example/'.self::TID.'/v2.0'])->assertRedirect(route('login'));
        $this->assertGuest();

        config(['services.microsoft.tenant' => '99999999-9999-4999-8999-999999999999']);
        $query = $this->startLogin();
        $this->complete($query)->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame(0, User::query()->where('microsoft_id', self::OID)->count());
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $query = $this->startLogin();
        $this->complete($query, ['iat' => time() - 7200, 'nbf' => time() - 7200, 'exp' => time() - 3600])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(0, User::query()->where('email', 'new-learner@example.test')->count());
    }

    public function test_the_wrong_nonce_is_rejected(): void
    {
        $query = $this->startLogin();
        $this->complete($query, ['nonce' => 'different-nonce'])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(0, User::query()->where('email', 'new-learner@example.test')->count());
    }

    public function test_an_existing_linked_user_can_sign_in_without_changing_email(): void
    {
        $user = $this->makeUser('linked@example.test');
        $user->forceFill([
            'microsoft_id' => self::OID,
            'microsoft_tenant_id' => self::TID,
            'microsoft_identity_key' => MicrosoftIdentityStore::identityKey(self::TID, self::OID),
            'microsoft_account' => true,
        ])->save();

        $query = $this->startLogin();
        $this->complete($query, ['email' => 'attacker@example.test'])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('linked@example.test', $user->fresh()->email);
        $this->assertSame(1, User::query()->where('microsoft_id', self::OID)->count());
    }

    public function test_an_unknown_microsoft_identity_creates_an_unverified_learner(): void
    {
        $query = $this->startLogin();
        $this->complete($query, ['email' => 'brand-new@example.test'])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'brand-new@example.test')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRoleName(Role::LEARNER));
    }

    public function test_an_email_collision_does_not_link_or_sign_in_the_local_user(): void
    {
        $local = $this->makeUser('taken@example.test');
        $query = $this->startLogin();
        $this->complete($query, ['email' => 'taken@example.test'])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull($local->fresh()->microsoft_id);
        $this->assertFalse((bool) $local->fresh()->microsoft_account);
        $this->assertSame(1, User::query()->where('email', 'taken@example.test')->count());
    }

    public function test_a_microsoft_identity_cannot_be_linked_to_a_second_local_account(): void
    {
        $owner = $this->makeUser('owner-ms@example.test');
        $owner->forceFill([
            'microsoft_id' => self::OID,
            'microsoft_tenant_id' => self::TID,
            'microsoft_identity_key' => MicrosoftIdentityStore::identityKey(self::TID, self::OID),
            'microsoft_account' => true,
        ])->save();
        $other = $this->makeUser('other-ms@example.test');

        $response = $this->actingAs($other)->post(route('microsoft.link'));
        $response->assertRedirect();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->complete($query)->assertRedirect(route('dashboard.settings'));

        $this->assertAuthenticatedAs($other);
        $this->assertNull($other->fresh()->microsoft_identity_key);
        $this->assertSame(
            $owner->id,
            User::query()->where('microsoft_identity_key', MicrosoftIdentityStore::identityKey(self::TID, self::OID))->value('id')
        );
    }

    public function test_a_signed_in_user_can_link_only_after_confirmation(): void
    {
        $user = $this->makeUser('confirm-link@example.test');
        $this->actingAs($user);

        $this->get(route('microsoft.redirect'))->assertRedirect(route('dashboard.settings'));
        $this->assertNull(session(MicrosoftAuthService::SESSION_KEY));
        $this->get(route('dashboard.settings'))->assertSee(route('microsoft.link'), false);

        $response = $this->post(route('microsoft.link'));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('login.microsoftonline.com', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->complete($query, ['email' => 'someone-else@example.test'])->assertRedirect(route('dashboard.settings'));

        $fresh = $user->fresh();
        $this->assertAuthenticatedAs($fresh);
        $this->assertSame(self::OID, $fresh->microsoft_id);
        $this->assertSame(self::TID, $fresh->microsoft_tenant_id);
        $this->assertSame('confirm-link@example.test', $fresh->email);
    }

    public function test_a_linked_account_is_not_reassigned_to_another_microsoft_identity(): void
    {
        $user = $this->makeUser('keep-identity@example.test');
        $user->forceFill([
            'microsoft_id' => self::OID,
            'microsoft_tenant_id' => self::TID,
            'microsoft_identity_key' => MicrosoftIdentityStore::identityKey(self::TID, self::OID),
            'microsoft_account' => true,
        ])->save();
        $otherOid = '22222222-2222-4222-8222-222222222222';

        $response = $this->actingAs($user)->post(route('microsoft.link'));
        $response->assertRedirect();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->complete($query, ['oid' => $otherOid])->assertRedirect(route('dashboard.settings'));

        $this->assertSame(self::OID, $user->fresh()->microsoft_id);
        $this->assertSame(MicrosoftIdentityStore::identityKey(self::TID, self::OID), $user->fresh()->microsoft_identity_key);
    }

    public function test_a_missing_learner_role_does_not_create_a_user(): void
    {
        Role::query()->where('role_name', Role::LEARNER)->delete();
        $query = $this->startLogin();
        $this->complete($query, ['email' => 'no-role@example.test'])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(User::query()->where('email', 'no-role@example.test')->first());
    }

    public function test_a_legacy_microsoft_id_is_not_silently_attached_to_a_new_tenant(): void
    {
        $user = $this->makeUser('legacy-ms@example.test');
        $user->forceFill([
            'microsoft_id' => self::OID,
            'microsoft_account' => true,
            'microsoft_identity_key' => MicrosoftIdentityStore::legacyKey(self::OID),
        ])->save();

        $query = $this->startLogin();
        $this->complete($query, ['email' => 'legacy-other@example.test'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['msg' => 'Sign in with your password, then connect Microsoft from account settings.']);

        $this->assertGuest();
        $fresh = $user->fresh();
        $this->assertNull($fresh->microsoft_tenant_id);
        $this->assertSame(MicrosoftIdentityStore::legacyKey(self::OID), $fresh->microsoft_identity_key);
        $this->assertSame('legacy-ms@example.test', $fresh->email);

        $response = $this->actingAs($user)->post(route('microsoft.link'));
        $response->assertRedirect();
        $this->assertStringContainsString('login.microsoftonline.com', (string) $response->headers->get('Location'));
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $confirmed);
        $this->complete($confirmed, ['email' => 'legacy-other@example.test'])
            ->assertRedirect(route('dashboard.settings'))
            ->assertSessionHas('success', 'Microsoft account connected successfully.');

        $linked = $user->fresh();
        $this->assertAuthenticatedAs($linked);
        $this->assertSame(self::TID, $linked->microsoft_tenant_id);
        $this->assertSame(MicrosoftIdentityStore::identityKey(self::TID, self::OID), $linked->microsoft_identity_key);
        $this->assertSame('legacy-ms@example.test', $linked->email);
        $this->assertSame(1, User::query()->where('microsoft_id', self::OID)->count());
    }

    public function test_another_user_cannot_claim_a_legacy_microsoft_identity(): void
    {
        $owner = $this->makeUser('legacy-owner@example.test');
        $owner->forceFill([
            'microsoft_id' => self::OID,
            'microsoft_account' => true,
            'microsoft_identity_key' => MicrosoftIdentityStore::legacyKey(self::OID),
        ])->save();
        $other = $this->makeUser('legacy-claimer@example.test');

        $response = $this->actingAs($other)->post(route('microsoft.link'));
        $response->assertRedirect();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->complete($query)->assertRedirect(route('dashboard.settings'));

        $this->assertAuthenticatedAs($other);
        $this->assertNull($other->fresh()->microsoft_identity_key);
        $this->assertNull($owner->fresh()->microsoft_tenant_id);
        $this->assertSame(MicrosoftIdentityStore::legacyKey(self::OID), $owner->fresh()->microsoft_identity_key);
    }

    public function test_tokens_and_secrets_are_not_written_to_logs(): void
    {
        $logged = '';
        Log::listen(function ($message) use (&$logged) {
            $logged .= $message->message.json_encode($message->context);
        });

        $query = $this->startLogin();
        $token = $this->token();
        $verifier = session(MicrosoftAuthService::SESSION_KEY)['verifier'];
        $this->fakeMicrosoft($token);
        $this->get(route('microsoft.callback', ['code' => 'auth-code-value', 'state' => $query['state']]));

        $user = User::query()->where('email', 'new-learner@example.test')->first();
        Http::fake([
            'https://login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response([
                'error' => 'invalid_grant',
                'access_token' => 'access-secret-value',
            ], 400),
        ]);
        try {
            app(MicrosoftGraphService::class)->refreshAccessToken($user);
        } catch (\Exception) {
            // The failure itself is expected. The log content is the assertion.
        }

        foreach ([
            'auth-code-value',
            $token,
            'access-secret-value',
            'refresh-secret-value',
            'azure-client-secret',
            $verifier,
            $user->refresh_token,
        ] as $secret) {
            $this->assertStringNotContainsString($secret, $logged);
        }
    }

    private function startLogin(): array
    {
        $response = $this->get(route('microsoft.redirect'));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $tenant = (string) config('services.microsoft.tenant');
        $this->assertStringContainsString('https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/authorize', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return $query;
    }

    private function complete(array $query, array $claims = [], ?string $privateKey = null)
    {
        $this->fakeMicrosoft($this->token($claims, $privateKey));

        return $this->get(route('microsoft.callback', [
            'code' => 'auth-code-value',
            'state' => $query['state'],
        ]));
    }

    private function fakeMicrosoft(string $jwt): void
    {
        $factory = new \Illuminate\Http\Client\Factory;
        Http::swap($factory);
        $factory->preventStrayRequests();
        $factory->fake([
            'https://login.microsoftonline.com/common/discovery/v2.0/keys' => Http::response($this->jwks),
            'https://login.microsoftonline.com/99999999-9999-4999-8999-999999999999/discovery/v2.0/keys' => Http::response($this->jwks),
            'https://login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => 'access-secret-value',
                'refresh_token' => 'refresh-secret-value',
                'id_token' => $jwt,
            ]),
            'https://login.microsoftonline.com/99999999-9999-4999-8999-999999999999/oauth2/v2.0/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => 'access-secret-value',
                'refresh_token' => 'refresh-secret-value',
                'id_token' => $jwt,
            ]),
        ]);
    }

    private function token(array $overrides = [], ?string $privateKey = null): string
    {
        $pending = session(MicrosoftAuthService::SESSION_KEY);
        $payload = array_merge([
            'iss' => 'https://login.microsoftonline.com/'.self::TID.'/v2.0',
            'aud' => self::CLIENT,
            'tid' => self::TID,
            'oid' => self::OID,
            'nonce' => $pending['nonce'] ?? 'missing-nonce',
            'email' => 'new-learner@example.test',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
        ], $overrides);

        return JWT::encode($payload, $privateKey ?? $this->privateKey, 'RS256', 'test-key');
    }

    private function rsaKey(): string
    {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $opensslConfig = getenv('OPENSSL_CONF') ?: dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';
        if (is_file($opensslConfig)) {
            $config['config'] = $opensslConfig;
        }
        $key = openssl_pkey_new($config);
        openssl_pkey_export($key, $pem, null, $config);

        return $pem;
    }

    private function jwk(string $privateKey, string $kid): array
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($privateKey));

        return [
            'kty' => 'RSA',
            'kid' => $kid,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
        ];
    }

    private function makeUser(string $email): User
    {
        return User::query()->create([
            'email' => $email,
            'password' => Hash::make('secret-secret'),
            'role_id' => Role::findByName(Role::LEARNER)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
    }
}
