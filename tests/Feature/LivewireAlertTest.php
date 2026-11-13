<?php

namespace Tests\Feature;

use App\Livewire\Login;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireAlertTest extends TestCase
{
    public function test_invalid_login_keeps_the_existing_alert_call(): void
    {
        $this->assertSame('php', config('session.serialization'));

        $component = Livewire::test(Login::class)
            ->set('email', 'nobody@example.com')
            ->set('password', 'not-the-password')
            ->call('login');

        $this->assertGuest();

        $script = json_encode($component->effects['xjs'] ?? []);

        $this->assertStringContainsString('Swal.fire', $script);
        $this->assertStringContainsString('Invalid email or password. Please try again.', $script);
        $this->assertStringContainsString('warning', $script);
    }
}
