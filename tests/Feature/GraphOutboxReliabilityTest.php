<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CheckoutSnapshot;
use App\Models\Classe;
use App\Models\Course;
use App\Models\GraphSyncOutbox;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\EnrollmentFulfillment;
use App\Services\Microsoft\GraphSyncProcessor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GraphOutboxReliabilityTest extends TestCase
{
    use DatabaseTransactions;

    private array $patches = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'services.microsoft.tenant' => 'tenant-test',
            'services.microsoft.client_id' => 'client-test',
            'services.microsoft.client_secret' => 'client-secret-test',
        ]);

        foreach ([Role::ADMIN, Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }
    }

    public function test_an_outbox_failure_rolls_back_the_paid_enrollment(): void
    {
        [$class, $session] = $this->paidClassSession();
        GraphSyncOutbox::created(function () {
            throw new RuntimeException('outbox insert interrupted');
        });

        try {
            app(EnrollmentFulfillment::class)->fulfillFromSession($session);
            $this->fail('The enrollment transaction should roll back with the outbox record.');
        } catch (RuntimeException $exception) {
            $this->assertSame('outbox insert interrupted', $exception->getMessage());
        } finally {
            GraphSyncOutbox::flushEventListeners();
        }

        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(0, Payment::query()->where('stripe_session_id', $session['id'])->count());
        $this->assertSame(0, GraphSyncOutbox::query()->count());
        $this->assertSame(CheckoutSnapshot::OPEN, CheckoutSnapshot::query()->where('stripe_session_id', $session['id'])->value('state'));
    }

    public function test_a_worker_crash_leaves_a_pending_outbox_that_can_be_retried_once(): void
    {
        $this->app->bind(GraphSyncProcessor::class, function () {
            return new class extends GraphSyncProcessor
            {
                public function process(GraphSyncOutbox $row): void
                {
                    throw new RuntimeException('worker crashed before completion');
                }
            };
        });

        [$class, $session] = $this->paidClassSession();

        try {
            app(EnrollmentFulfillment::class)->fulfillFromSession($session);
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage());
        }

        $booking = Booking::query()->where('class_id', $class->id)->first();
        $row = GraphSyncOutbox::query()->where('booking_id', $booking->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame(GraphSyncOutbox::STATUS_PENDING, $row->status);
        $this->assertSame(0, $row->attempts);

        $this->app->instance(GraphSyncProcessor::class, new GraphSyncProcessor);
        $this->fakeSuccessfulGraph();
        Artisan::call('graph:sync-outbox');

        $this->assertSame(GraphSyncOutbox::STATUS_SUCCEEDED, $row->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertCount(1, $this->patches);
        $this->assertStringNotContainsString('sendUpdates=all', $this->patches[0]);

        Artisan::call('graph:sync-outbox');
        app(GraphSyncProcessor::class)->process($row->fresh());
        $this->assertCount(1, $this->patches);
    }

    public function test_graph_failure_stays_visible_and_a_retry_does_not_send_a_second_update(): void
    {
        $attempts = 0;
        Http::fake(function (Request $request) use (&$attempts) {
            if (str_contains($request->url(), 'oauth2/v2.0/token')) {
                return Http::response(['access_token' => 'access-token'], 200);
            }

            if ($request->method() === 'PATCH') {
                $attempts++;
                if ($attempts === 1) {
                    return Http::response('', 500);
                }

                $this->patches[] = $request->url();

                return Http::response(['@odata.etag' => 'W/"2"'], 200);
            }

            return Http::response([
                '@odata.etag' => 'W/"1"',
                'attendees' => [[
                    'emailAddress' => ['address' => 'kept@example.test', 'name' => 'Kept'],
                    'type' => 'required',
                ]],
            ], 200);
        });

        [$class, $session] = $this->paidClassSession();
        app(EnrollmentFulfillment::class)->fulfillFromSession($session);

        $booking = Booking::query()->where('class_id', $class->id)->first();
        $row = GraphSyncOutbox::query()->first();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame(Payment::STATUS_PAID, Payment::query()->where('booking_id', $booking->id)->value('status'));
        $this->assertSame(GraphSyncOutbox::STATUS_FAILED, $row->status);
        $this->assertSame('update_failed', $row->last_error);
        $this->assertSame(1, GraphSyncOutbox::query()->count());

        $row->forceFill(['available_at' => now()->subMinute()])->save();
        Artisan::call('graph:sync-outbox');
        $this->assertSame(GraphSyncOutbox::STATUS_SUCCEEDED, $row->fresh()->status);
        $this->assertCount(1, $this->patches);

        app(EnrollmentFulfillment::class)->fulfillFromSession($session);
        Artisan::call('graph:sync-outbox');
        $this->assertSame(1, GraphSyncOutbox::query()->count());
        $this->assertSame(1, Booking::query()->where('class_id', $class->id)->count());
        $this->assertCount(1, $this->patches);
    }

    private function fakeSuccessfulGraph(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth2/v2.0/token')) {
                return Http::response(['access_token' => 'access-token'], 200);
            }

            if ($request->method() === 'PATCH') {
                $this->patches[] = $request->url();

                return Http::response(['@odata.etag' => 'W/"2"'], 200);
            }

            return Http::response([
                '@odata.etag' => 'W/"1"',
                'attendees' => [],
            ], 200);
        });
    }

    private function paidClassSession(): array
    {
        $owner = User::query()->create([
            'email' => 'owner-'.bin2hex(random_bytes(3)).'@example.test',
            'password' => Hash::make(bin2hex(random_bytes(8))),
            'role_id' => Role::findByName(Role::TUTOR)->id,
            'email_verified_at' => now(),
            'microsoft_account' => true,
            'microsoft_id' => 'organizer-'.bin2hex(random_bytes(3)),
            'timezone' => 'UTC',
        ]);
        $learner = User::query()->create([
            'email' => 'learner-'.bin2hex(random_bytes(3)).'@example.test',
            'first_name' => 'Paid',
            'last_name' => 'Learner',
            'password' => Hash::make(bin2hex(random_bytes(8))),
            'role_id' => Role::findByName(Role::LEARNER)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
        $course = Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'paid',
            'price' => 25,
            'title' => 'Outbox course '.bin2hex(random_bytes(3)),
            'description' => 'Description',
            'thumbnail' => 'thumb.png',
            'video_path' => 'promo.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
        ]);
        $class = Classe::query()->create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'microsoft_event_id' => 'event-'.bin2hex(random_bytes(3)),
            'title' => 'Outbox class',
            'description' => 'Class description',
            'visibility' => true,
            'class_type' => 'onsite',
            'is_paid' => 'paid',
            'price' => 25,
            'capacity' => 5,
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
            'booking_start_date' => '2026-11-01',
            'booking_end_date' => '2026-12-01',
        ]);
        $sessionId = 'cs_outbox_'.bin2hex(random_bytes(4));
        $snapshot = CheckoutSnapshot::query()->create([
            'user_id' => $learner->id,
            'item_type' => 'class',
            'item_id' => $class->id,
            'amount_cents' => 2500,
            'currency' => 'usd',
            'stripe_session_id' => $sessionId,
            'state' => CheckoutSnapshot::OPEN,
        ]);

        return [$class, [
            'id' => $sessionId,
            'payment_status' => 'paid',
            'status' => 'complete',
            'amount_total' => 2500,
            'currency' => 'usd',
            'payment_intent' => 'pi_outbox_'.bin2hex(random_bytes(4)),
            'metadata' => [
                'user_id' => (string) $learner->id,
                'item_type' => 'class',
                'item_id' => (string) $class->id,
                'snapshot_id' => (string) $snapshot->id,
            ],
        ]];
    }
}
