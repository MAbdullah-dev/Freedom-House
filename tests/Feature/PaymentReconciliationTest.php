<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([Role::ADMIN, Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }
    }

    public function test_only_admins_can_view_payment_reconciliation(): void
    {
        $this->get('/dashboard/payments/reconciliation')->assertRedirect(route('login'));

        $this->actingAs($this->makeUser('recon-learner@example.test', Role::LEARNER))
            ->get('/dashboard/payments/reconciliation')
            ->assertForbidden();

        $this->actingAs($this->makeUser('recon-tutor@example.test', Role::TUTOR))
            ->get('/dashboard/payments/reconciliation')
            ->assertForbidden();

        $this->actingAs($this->makeUser('recon-admin@example.test', Role::ADMIN))
            ->get('/dashboard/payments/reconciliation')
            ->assertOk()
            ->assertSee('Payment reconciliation')
            ->assertSee('Unresolved payments:');
    }

    public function test_reconciliation_filters_payments_without_changing_them(): void
    {
        $admin = $this->makeUser('recon-filter-admin@example.test', Role::ADMIN);
        $admin->forceFill(['refresh_token' => 'refresh-token-must-not-render'])->save();

        $unresolved = $this->makePayment([
            'status' => Payment::STATUS_UNFULFILLABLE,
            'amount_cents' => 2500,
            'currency' => 'usd',
            'refunded_cents' => 0,
            'resolution_required' => true,
            'resolution_reason' => 'capacity_exhausted',
            'booking_id' => null,
            'stripe_session_id' => 'cs_unresolved_visible',
            'stripe_payment_intent_id' => 'pi_unresolved_visible',
        ]);
        $booking = \App\Models\Booking::query()->create([
            'payment_status' => 'paid',
            'order_number' => 'recon-booking-'.bin2hex(random_bytes(4)),
        ]);
        $clear = $this->makePayment([
            'status' => Payment::STATUS_PAID,
            'amount_cents' => 1000,
            'currency' => 'usd',
            'refunded_cents' => 150,
            'resolution_required' => false,
            'resolution_reason' => null,
            'booking_id' => $booking->id,
            'stripe_session_id' => 'cs_clear_visible',
            'stripe_payment_intent_id' => 'pi_clear_visible',
        ]);
        $this->makePayment([
            'status' => Payment::STATUS_REFUNDED,
            'resolution_required' => true,
            'resolution_reason' => 'refunded_before_completion',
            'stripe_session_id' => 'cs_refunded_visible',
            'stripe_payment_intent_id' => 'pi_refunded_visible',
        ]);
        $this->makePayment([
            'status' => null,
            'resolution_required' => true,
            'resolution_reason' => 'capacity_exhausted',
            'stripe_session_id' => 'cs_legacy_hidden',
            'stripe_payment_intent_id' => 'pi_legacy_hidden',
        ]);

        $unresolved->refresh();
        $before = $unresolved->only([
            'status', 'amount', 'amount_cents', 'currency', 'refunded_cents',
            'resolution_required', 'resolution_reason', 'booking_id',
        ]);

        $page = $this->actingAs($admin)->get('/dashboard/payments/reconciliation');
        $page->assertOk()
            ->assertSee('Unresolved payments:')
            ->assertSee('cs_unresolved_visible')
            ->assertSee('pi_unresolved_visible')
            ->assertSee('25.00')
            ->assertSee('capacity_exhausted')
            ->assertSee('cs_clear_visible')
            ->assertSee('1.50')
            ->assertSee((string) $booking->id)
            ->assertSee('cs_refunded_visible')
            ->assertDontSee('cs_legacy_hidden')
            ->assertDontSee('refresh-token-must-not-render')
            ->assertDontSee('sk_live')
            ->assertDontSee('client_secret');

        $this->assertSame($before, $unresolved->fresh()->only(array_keys($before)));

        $this->actingAs($admin)
            ->get('/dashboard/payments/reconciliation?resolution_required=yes&resolution_reason=capacity_exhausted')
            ->assertOk()
            ->assertSee('cs_unresolved_visible')
            ->assertDontSee('cs_clear_visible')
            ->assertDontSee('cs_refunded_visible');

        $this->actingAs($admin)
            ->get('/dashboard/payments/reconciliation?resolution_required=no')
            ->assertOk()
            ->assertSee('cs_clear_visible')
            ->assertDontSee('cs_unresolved_visible');

        $this->actingAs($admin)
            ->post('/dashboard/payments/reconciliation')
            ->assertStatus(405);

        $this->assertNull($unresolved->fresh()->booking_id);
        $this->assertSame($booking->id, $clear->fresh()->booking_id);
    }

    public function test_reconciliation_is_paginated(): void
    {
        $admin = $this->makeUser('recon-page-admin@example.test', Role::ADMIN);

        foreach (range(1, 21) as $index) {
            $this->makePayment([
                'status' => Payment::STATUS_PARTIALLY_REFUNDED,
                'stripe_session_id' => 'cs_page_'.$index,
                'stripe_payment_intent_id' => 'pi_page_'.$index,
                'resolution_required' => false,
                'resolution_reason' => 'page_batch',
            ]);
        }

        $this->actingAs($admin)
            ->get('/dashboard/payments/reconciliation?resolution_reason=page_batch&page=2')
            ->assertOk()
            ->assertSee('cs_page_1')
            ->assertDontSee('cs_page_21');
    }

    private function makePayment(array $overrides): Payment
    {
        return Payment::query()->create(array_merge([
            'amount' => 10,
            'booking_id' => null,
            'payment_method' => 'stripe',
            'transaction_id' => 'txn-'.bin2hex(random_bytes(6)),
            'status' => Payment::STATUS_PAID,
            'amount_cents' => 1000,
            'currency' => 'usd',
            'refunded_cents' => 0,
            'resolution_required' => false,
        ], $overrides));
    }

    private function makeUser(string $email, string $roleName): User
    {
        return User::query()->create([
            'email' => str_replace('@', '-'.bin2hex(random_bytes(3)).'@', $email),
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'role_id' => Role::findByName($roleName)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
    }
}
