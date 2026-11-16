<?php

namespace Tests\Feature;

use App\Livewire\Inc\DashbordSidebar;
use App\Livewire\Login;
use App\Livewire\Register;
use App\Mail\PasswordResetMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        RateLimiter::clear('password-reset:127.0.0.1');
        RateLimiter::clear('register:127.0.0.1');
        parent::tearDown();
    }

    public function test_a_verified_user_can_log_in(): void
    {
        $user = $this->verifiedUser('login-ok@example.test');

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secret-secret')
            ->call('login')
            ->assertRedirect(route('dashboard.settings'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_does_not_authenticate(): void
    {
        $user = $this->verifiedUser('login-bad@example.test');

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_login_attempts_are_throttled(): void
    {
        $user = $this->verifiedUser('login-throttle@example.test');
        RateLimiter::clear($this->loginKey($user->email));

        $component = Livewire::test(Login::class);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $component->set('email', $user->email)->set('password', 'wrong-password')->call('login');
        }

        $component->set('email', $user->email)->set('password', 'secret-secret')->call('login')->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_registration_creates_a_learner(): void
    {
        Mail::fake();
        Role::query()->firstOrCreate(['role_name' => Role::LEARNER]);

        Livewire::test(Register::class)
            ->set('first_name', 'New')
            ->set('last_name', 'Learner')
            ->set('email', 'new-learner@example.test')
            ->set('phone', '5550001111')
            ->set('password', 'secret-secret')
            ->set('confirm_password', 'secret-secret')
            ->call('register');

        $user = User::query()->where('email', 'new-learner@example.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRoleName(Role::LEARNER));
        $this->assertNull($user->microsoft_identity_key);
    }

    public function test_registration_never_creates_admin_or_tutor_privileges(): void
    {
        Mail::fake();
        $admin = Role::query()->firstOrCreate(['role_name' => Role::ADMIN]);
        $tutor = Role::query()->firstOrCreate(['role_name' => Role::TUTOR]);
        Role::query()->firstOrCreate(['role_name' => Role::LEARNER]);

        Livewire::test(Register::class)
            ->set('first_name', 'Not')
            ->set('last_name', 'Staff')
            ->set('email', 'not-staff@example.test')
            ->set('phone', '5550002222')
            ->set('password', 'secret-secret')
            ->set('confirm_password', 'secret-secret')
            ->call('register');

        $user = User::query()->where('email', 'not-staff@example.test')->first();
        $this->assertNotNull($user);
        $this->assertNotSame($admin->id, $user->role_id);
        $this->assertNotSame($tutor->id, $user->role_id);
        $this->assertTrue($user->hasRoleName(Role::LEARNER));
    }

    public function test_a_reset_token_is_stored_hashed(): void
    {
        Mail::fake();
        $user = $this->verifiedUser('reset-hash@example.test');
        $user->forceFill(['password_reset_token' => 'legacy-plaintext-token'])->save();

        $token = $this->requestReset($user->email);

        $stored = \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $this->assertNotSame($token, $stored);
        $this->assertTrue(Hash::check($token, $stored));
        $this->assertNull($user->fresh()->password_reset_token);
    }

    public function test_a_reset_token_expires(): void
    {
        $user = $this->verifiedUser('reset-expire@example.test');
        $token = Password::broker()->createToken($user);
        $before = $user->password;

        $this->travel(61)->minutes();

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');

        $this->assertSame($before, $user->fresh()->password);
        $this->travelBack();
    }

    public function test_a_reset_token_cannot_be_reused(): void
    {
        $user = $this->verifiedUser('reset-reuse@example.test');
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNull(\Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $user->email)->value('token'));
    }

    public function test_an_unknown_email_produces_a_safe_reset_response(): void
    {
        Mail::fake();

        $response = Livewire::test(Login::class)
            ->set('reset_email', 'missing@example.test')
            ->call('sendPasswordResetLink');
        $payload = json_encode($response->effects);
        $this->assertStringContainsString('If that email is registered, a reset link has been sent.', $payload);
        $this->assertStringNotContainsString('No user found', $payload);

        Mail::assertNothingSent();
        $this->assertNull(\Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', 'missing@example.test')->value('token'));
    }

    public function test_reset_requests_are_throttled(): void
    {
        Mail::fake();
        $user = $this->verifiedUser('reset-throttle@example.test');
        RateLimiter::clear('password-reset:127.0.0.1');

        $this->requestReset($user->email);
        Livewire::test(Login::class)->set('reset_email', $user->email)->call('sendPasswordResetLink');

        Mail::assertSent(PasswordResetMail::class, 1);
    }

    public function test_a_password_changes_only_with_a_valid_token(): void
    {
        $user = $this->verifiedUser('reset-valid@example.test');
        $before = $user->password;

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => 'not-a-real-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');

        $this->assertSame($before, $user->fresh()->password);
        $this->assertStringNotContainsString('new-password', session('errors')->first('email'));
        $this->assertStringNotContainsString('not-a-real-token', session('errors')->first('email'));
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = $this->verifiedUser('logout@example.test');
        $this->actingAs($user);
        $sessionId = session()->getId();
        $csrf = session()->token();

        Livewire::test(DashbordSidebar::class)->call('logout');

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($csrf, session()->token());
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_login_regenerates_the_session(): void
    {
        $user = $this->verifiedUser('session-regen@example.test');
        $this->get(route('login'));
        $sessionId = session()->getId();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secret-secret')
            ->call('login');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
    }

    public function test_a_password_reset_leaves_a_microsoft_identity_intact(): void
    {
        $user = $this->verifiedUser('ms-reset@example.test');
        $user->forceFill([
            'microsoft_id' => '11111111-2222-4333-8444-555555555555',
            'microsoft_tenant_id' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            'microsoft_identity_key' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee:11111111-2222-4333-8444-555555555555',
            'microsoft_account' => true,
        ])->save();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $fresh = $user->fresh();
        $this->assertSame('aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee:11111111-2222-4333-8444-555555555555', $fresh->microsoft_identity_key);
        $this->assertSame('11111111-2222-4333-8444-555555555555', $fresh->microsoft_id);
        $this->assertTrue(Hash::check('new-password', $fresh->password));
    }

    public function test_another_session_is_rejected_after_a_password_reset(): void
    {
        $user = $this->verifiedUser('other-session@example.test');
        $this->actingAs($user)->get(route('dashboard.settings'));
        $previousHash = session('password_hash_web');
        $this->assertNotEmpty($previousHash);

        Auth::logout();
        $token = Password::broker()->createToken($user->fresh());
        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->actingAs($user->fresh());
        session(['password_hash_web' => $previousHash]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authentication_errors_do_not_expose_tokens_or_passwords(): void
    {
        $logged = '';
        Log::listen(function ($message) use (&$logged) {
            $logged .= $message->message.json_encode($message->context);
        });

        $user = $this->verifiedUser('hidden-secret@example.test');
        $response = Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'plain-password-value')
            ->call('login');

        $response->assertNoRedirect();
        $this->assertStringNotContainsString('plain-password-value', $response->effects['html'] ?? json_encode($response->effects));
        $this->assertStringNotContainsString('plain-password-value', $logged);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => 'reset-token-value',
            'password' => 'plain-password-value',
            'password_confirmation' => 'plain-password-value',
        ]);

        $this->assertStringNotContainsString('plain-password-value', session('errors')->first('email'));
        $this->assertStringNotContainsString('reset-token-value', session('errors')->first('email'));
        $this->assertStringNotContainsString('plain-password-value', $logged);
        $this->assertStringNotContainsString('reset-token-value', $logged);
    }

    private function verifiedUser(string $email): User
    {
        $role = Role::query()->firstOrCreate(['role_name' => Role::LEARNER]);

        return User::query()->create([
            'email' => $email,
            'password' => Hash::make('secret-secret'),
            'role_id' => $role->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
            'phone' => substr(md5($email), 0, 12),
        ]);
    }

    private function requestReset(string $email): string
    {
        Livewire::test(Login::class)->set('reset_email', $email)->call('sendPasswordResetLink');

        $token = null;
        Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use (&$token) {
            $path = (string) parse_url($mail->resetUrl, PHP_URL_PATH);
            $token = basename($path);

            return $token !== '';
        });

        return (string) $token;
    }

    private function loginKey(string $email): string
    {
        return 'login:'.strtolower($email).'|127.0.0.1';
    }
}
