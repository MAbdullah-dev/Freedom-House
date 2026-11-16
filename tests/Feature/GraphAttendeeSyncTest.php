<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Classe;
use App\Models\Course;
use App\Models\GraphSyncOutbox;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\EnrollmentFulfillment;
use App\Services\Microsoft\GraphAttendeeSync;
use App\Services\MicrosoftGraphService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class GraphAttendeeSyncTest extends TestCase
{
    use DatabaseTransactions;

    private array $patched = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->replaceHttp();
        config([
            'services.microsoft.tenant' => 'tenant-test',
            'services.microsoft.client_id' => 'client-test',
            'services.microsoft.client_secret' => 'client-secret-test',
        ]);

        foreach ([Role::ADMIN, Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }
    }

    public function test_append_preserves_existing_attendees_and_skips_duplicates(): void
    {
        $this->fakeGraph([
            $this->attendees(['kept@example.test']),
        ]);

        $result = app(GraphAttendeeSync::class)->append(
            'organizer-1',
            'event-1',
            [['email' => 'Kept@Example.test', 'name' => 'Kept']],
            'access-token'
        );

        $this->assertTrue($result->ok);
        $this->assertSame('already_present', $result->code);
        $this->assertSame([], $this->patched);

        $this->fakeGraph([
            $this->attendees(['kept@example.test']),
        ]);
        $added = app(GraphAttendeeSync::class)->append(
            'organizer-1',
            'event-1',
            [['email' => 'new@example.test', 'name' => 'New']],
            'access-token'
        );

        $this->assertSame('updated', $added->code);
        $this->assertSame(['kept@example.test', 'new@example.test'], $this->addresses($this->patched[0]));
        $this->assertSame(['W/"1"'], $this->matchHeaders());
    }

    public function test_conflict_rereads_and_keeps_the_concurrent_attendee(): void
    {
        $gets = 0;
        $this->patched = [];
        Http::fake(function (Request $request) use (&$gets) {
            if ($request->method() === 'GET') {
                $gets++;
                $emails = $gets === 1
                    ? ['kept@example.test']
                    : ['kept@example.test', 'concurrent@example.test'];

                return Http::response([
                    '@odata.etag' => 'W/"'.$gets.'"',
                    'attendees' => $this->attendeePayload($emails),
                ], 200);
            }

            $this->patched[] = $request->data()['attendees'] ?? [];

            return Http::response([], $this->headerValue($request, 'If-Match') === 'W/"1"' ? 412 : 200);
        });

        $result = app(GraphAttendeeSync::class)->append(
            'organizer-1',
            'event-1',
            [['email' => 'new@example.test', 'name' => 'New']],
            'access-token'
        );

        $this->assertFalse($result->ok);
        $this->assertSame('conflict', $result->code);

        $owner = $this->makeUser('graph-owner@example.test', Role::TUTOR);
        $owner->forceFill(['microsoft_id' => 'organizer-1'])->save();
        $learner = $this->makeUser('graph-learner@example.test', Role::LEARNER);
        $class = $this->makeClass($owner, 'event-1');
        $booking = Booking::query()->create([
            'user_id' => $learner->id,
            'class_id' => $class->id,
            'payment_status' => 'paid',
            'order_number' => 'graph-'.bin2hex(random_bytes(4)),
        ]);
        $payment = Payment::query()->create([
            'amount' => 10,
            'booking_id' => $booking->id,
            'payment_method' => 'stripe',
            'transaction_id' => 'txn-'.bin2hex(random_bytes(4)),
            'status' => Payment::STATUS_PAID,
            'user_id' => $learner->id,
        ]);

        $this->assertCount(1, $this->patched);
        $this->assertSame(['kept@example.test', 'new@example.test'], $this->addresses($this->patched[0]));

        $reads = 0;
        $this->patched = [];
        $this->replaceHttp();
        Http::fake(function (Request $request) use (&$reads) {
            if (str_contains($request->url(), 'oauth2/v2.0/token')) {
                return Http::response(['access_token' => 'access-token'], 200);
            }

            if ($request->method() === 'GET') {
                $reads++;
                $emails = $reads === 1
                    ? ['kept@example.test']
                    : ['kept@example.test', 'concurrent@example.test'];

                return Http::response([
                    '@odata.etag' => $reads === 1 ? 'W/"9"' : 'W/"10"',
                    'attendees' => $this->attendeePayload($emails),
                ], 200);
            }

            $this->patched[] = $request->data()['attendees'] ?? [];

            return Http::response([], $this->headerValue($request, 'If-Match') === 'W/"9"' ? 412 : 200);
        });

        app(\App\Services\Microsoft\GraphSyncProcessor::class)->enqueueAttendee($learner, $booking);

        $this->assertCount(2, $this->patched);
        $expected = ['concurrent@example.test', 'kept@example.test', strtolower($learner->email)];
        sort($expected);
        $this->assertSame($expected, $this->addresses($this->patched[1]));
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(GraphSyncOutbox::STATUS_SUCCEEDED, GraphSyncOutbox::query()->first()->status);

        app(\App\Services\Microsoft\GraphSyncProcessor::class)->process(GraphSyncOutbox::query()->first());
        $this->assertCount(2, $this->patched);
    }

    public function test_missing_etag_does_not_patch_and_a_failed_sync_can_be_retried(): void
    {
        $entries = [];
        \Illuminate\Support\Facades\Log::listen(function ($event) use (&$entries) {
            $entries[] = $event->message.' '.json_encode($event->context);
        });
        $this->replaceHttp();
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth2/v2.0/token')) {
                return Http::response(['access_token' => 'token-value-should-stay-out-of-logs'], 200);
            }

            if ($request->method() === 'PATCH') {
                throw new RuntimeException('A missing etag must not update the event.');
            }

            return Http::response(['attendees' => $this->attendeePayload(['kept@example.test'])], 200);
        });

        $result = app(GraphAttendeeSync::class)->append('organizer-1', 'event-1', [
            ['email' => 'new@example.test', 'name' => 'New'],
        ], 'access-token');
        $this->assertSame('missing_etag', $result->code);

        $owner = $this->makeUser('graph-retry-owner@example.test', Role::TUTOR);
        $owner->forceFill(['microsoft_id' => 'organizer-1'])->save();
        $learner = $this->makeUser('graph-retry-learner@example.test', Role::LEARNER);
        $class = $this->makeClass($owner, 'event-1');
        $booking = Booking::query()->create([
            'user_id' => $learner->id,
            'class_id' => $class->id,
            'payment_status' => 'paid',
            'order_number' => 'graph-retry-'.bin2hex(random_bytes(4)),
        ]);

        $method = new ReflectionMethod(EnrollmentFulfillment::class, 'syncMicrosoft');
        $method->invoke(app(EnrollmentFulfillment::class), $learner, $booking);

        $row = GraphSyncOutbox::query()->first();
        $this->assertSame(GraphSyncOutbox::STATUS_FAILED, $row->status);
        $this->assertSame('missing_etag', $row->last_error);
        $this->assertSame('paid', $booking->fresh()->payment_status);

        $row->forceFill(['available_at' => now()->subMinute()])->save();
        $this->fakeGraph([
            $this->attendees(['kept@example.test']),
            $this->attendees(['kept@example.test', $learner->email]),
        ], true);

        Artisan::call('graph:sync-outbox');
        $this->assertSame(GraphSyncOutbox::STATUS_SUCCEEDED, $row->fresh()->status);
        $this->assertNull($row->fresh()->last_error);

        $patches = count($this->patched);
        Artisan::call('graph:sync-outbox');
        $this->assertCount($patches, $this->patched);

        $encoded = implode("\n", $entries);
        $this->assertStringNotContainsString('token-value-should-stay-out-of-logs', $encoded);
        $this->assertStringNotContainsString(strtolower($learner->email), $encoded);
    }

    public function test_remove_keeps_unrelated_attendees(): void
    {
        $this->fakeGraph([
            $this->attendees(['kept@example.test', 'leave@example.test']),
        ]);

        $result = app(GraphAttendeeSync::class)->remove('organizer-1', 'event-1', [
            ['email' => 'leave@example.test', 'name' => 'Leave'],
        ], 'access-token');

        $this->assertSame('updated', $result->code);
        $this->assertSame(['kept@example.test'], $this->addresses($this->patched[0]));
    }

    public function test_calendar_creation_failure_does_not_expose_the_response(): void
    {
        $entries = [];
        \Illuminate\Support\Facades\Log::listen(function ($event) use (&$entries) {
            $entries[] = $event->message.' '.json_encode($event->context);
        });
        $this->replaceHttp();
        $user = $this->makeUser('graph-create@example.test', Role::TUTOR);
        $user->forceFill(['refresh_token' => 'refresh-token-should-stay-out-of-logs'])->save();
        Auth::login($user);

        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth2/v2.0/token')) {
                return Http::response(['access_token' => 'access-token-should-stay-out-of-logs', 'expires_in' => 3600], 200);
            }

            return Http::response('graph-response-secret', 500);
        });

        $class = new Classe([
            'title' => 'Calendar class',
            'description' => 'Description',
            'class_date' => '2026-12-20',
            'class_time' => '10:00',
            'onsite_address' => 'Room 1',
        ]);

        try {
            app(MicrosoftGraphService::class)->createCalendarEvent($class);
            $this->fail('Calendar creation should fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The calendar event could not be created.', $exception->getMessage());
        }

        $encoded = implode("\n", $entries);
        $this->assertStringContainsString('Microsoft calendar request failed.', $encoded);
        $this->assertStringNotContainsString('graph-response-secret', $encoded);
        $this->assertStringNotContainsString('refresh-token-should-stay-out-of-logs', $encoded);
        $this->assertStringNotContainsString('access-token-should-stay-out-of-logs', $encoded);
    }

    private function replaceHttp(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
    }

    private function fakeGraph(array $gets, bool $withToken = false): void
    {
        $this->patched = [];
        $this->patchedHeaders = [];
        $this->replaceHttp();
        Http::fake(function (Request $request) use (&$gets, $withToken) {
            if ($withToken && str_contains($request->url(), 'oauth2/v2.0/token')) {
                return Http::response(['access_token' => 'access-token'], 200);
            }

            if ($request->method() === 'GET') {
                $payload = array_shift($gets) ?? ['@odata.etag' => 'W/"1"', 'attendees' => []];

                return Http::response($payload, 200);
            }

            $this->patched[] = $request->data()['attendees'] ?? [];
            $this->patchedHeaders[] = $request->headers();

            return Http::response([], 200);
        });
    }

    private array $patchedHeaders = [];

    private function attendees(array $emails): array
    {
        return [
            '@odata.etag' => 'W/"1"',
            'attendees' => $this->attendeePayload($emails),
        ];
    }

    private function attendeePayload(array $emails): array
    {
        return array_map(fn (string $email) => [
            'emailAddress' => ['address' => $email, 'name' => $email],
            'type' => 'required',
        ], $emails);
    }

    private function addresses(array $attendees): array
    {
        $emails = array_map(
            fn (array $attendee) => strtolower($attendee['emailAddress']['address']),
            $attendees
        );
        sort($emails);

        return $emails;
    }

    private function matchHeaders(): array
    {
        return array_map(function (array $headers) {
            $value = $headers['If-Match'] ?? $headers['if-match'] ?? [];

            return is_array($value) ? ($value[0] ?? null) : $value;
        }, $this->patchedHeaders);
    }

    private function headerValue(Request $request, string $name): string
    {
        $value = $request->header($name);

        return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
    }

    private function makeClass(User $owner, string $eventId): Classe
    {
        $course = Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'paid',
            'title' => 'Graph course '.bin2hex(random_bytes(3)),
            'description' => 'Description',
            'thumbnail' => 'thumb.png',
            'video_path' => 'promo.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
        ]);

        return Classe::query()->create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'microsoft_event_id' => $eventId,
            'title' => 'Graph class',
            'description' => 'Class description',
            'visibility' => true,
            'class_type' => 'onsite',
            'is_paid' => 'paid',
            'price' => 10,
            'capacity' => 5,
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
            'booking_start_date' => '2026-11-01',
            'booking_end_date' => '2026-12-01',
        ]);
    }

    private function makeUser(string $email, string $roleName): User
    {
        return User::query()->create([
            'email' => str_replace('@', '-'.bin2hex(random_bytes(3)).'@', $email),
            'first_name' => 'Graph',
            'last_name' => 'Learner',
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'role_id' => Role::findByName($roleName)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
    }
}
