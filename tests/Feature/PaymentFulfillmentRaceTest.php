<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\CheckoutSnapshot;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentFulfillmentRaceTest extends TestCase
{
    private array $cleanupIds = [];

    protected function tearDown(): void
    {
        if (config('database.connections.mysql.database') === 'lms_testing') {
            Payment::query()->whereIn('user_id', $this->cleanupIds['users'] ?? [])->delete();
            Booking::query()->whereIn('user_id', $this->cleanupIds['users'] ?? [])->delete();
            CheckoutSnapshot::query()->whereIn('user_id', $this->cleanupIds['users'] ?? [])->delete();
            if (! empty($this->cleanupIds['classes'])) {
                Classe::query()->whereIn('id', $this->cleanupIds['classes'])->delete();
            }
            if (! empty($this->cleanupIds['courses'])) {
                Course::query()->whereIn('id', $this->cleanupIds['courses'])->delete();
            }
            if (! empty($this->cleanupIds['users'])) {
                CalendarEvent::query()->whereIn('user_id', $this->cleanupIds['users'])->delete();
                User::query()->whereIn('id', $this->cleanupIds['users'])->forceDelete();
            }
            Role::query()
                ->whereIn('role_name', [Role::ADMIN, Role::TUTOR, Role::LEARNER])
                ->whereDoesntHave('users')
                ->delete();
        }

        parent::tearDown();
    }

    public function test_concurrent_fulfillment_does_not_double_enroll_or_drive_capacity_negative(): void
    {
        foreach ([Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }

        $owner = User::query()->create([
            'email' => 'race-owner-'.bin2hex(random_bytes(4)).'@example.test',
            'password' => Hash::make('secret-secret'),
            'role_id' => Role::findByName(Role::TUTOR)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
        $learner = User::query()->create([
            'email' => 'race-learner-'.bin2hex(random_bytes(4)).'@example.test',
            'password' => Hash::make('secret-secret'),
            'role_id' => Role::findByName(Role::LEARNER)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
        $course = Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'paid',
            'price' => 40,
            'title' => 'Race Course '.bin2hex(random_bytes(3)),
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
            'title' => 'Race Class',
            'description' => 'Class description',
            'visibility' => true,
            'class_type' => 'onsite',
            'is_paid' => 'paid',
            'price' => 40,
            'capacity' => 1,
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
        ]);
        $this->cleanupIds = [
            'users' => [$owner->id, $learner->id],
            'courses' => [$course->id],
            'classes' => [$class->id],
        ];

        $sessions = [];
        foreach (['cs_race_a_'.bin2hex(random_bytes(3)), 'cs_race_b_'.bin2hex(random_bytes(3))] as $index => $sessionId) {
            $snapshot = CheckoutSnapshot::query()->create([
                'user_id' => $learner->id,
                'item_type' => 'class',
                'item_id' => $class->id,
                'amount_cents' => 4000,
                'currency' => 'usd',
                'stripe_session_id' => $sessionId,
                'state' => CheckoutSnapshot::OPEN,
            ]);
            $sessions[] = [
                'id' => $sessionId,
                'payment_status' => 'paid',
                'status' => 'complete',
                'amount_total' => 4000,
                'currency' => 'usd',
                'payment_intent' => 'pi_race_'.$index.'_'.bin2hex(random_bytes(3)),
                'metadata' => [
                    'user_id' => (string) $learner->id,
                    'item_type' => 'class',
                    'item_id' => (string) $class->id,
                    'snapshot_id' => (string) $snapshot->id,
                ],
            ];
        }

        $output = $this->runTogether($sessions);

        $this->assertSame(1, Booking::query()->where('class_id', $class->id)->where('payment_status', 'paid')->count(), $output);
        $this->assertSame(1, Payment::query()->where('booking_id', Booking::query()->where('class_id', $class->id)->value('id'))->count());
        $this->assertSame(2, Payment::query()->where('user_id', $learner->id)->count(), $output."\n".Payment::query()->where('user_id', $learner->id)->get(['id', 'stripe_session_id', 'status', 'resolution_reason'])->toJson());
        $this->assertSame(0, (int) $class->fresh()->capacity);
        $this->assertSame(
            1,
            Payment::query()->where('user_id', $learner->id)->where('status', Payment::STATUS_UNFULFILLABLE)->count()
        );
    }

    public function test_concurrent_success_and_webhook_do_not_double_enroll_one_session(): void
    {
        foreach ([Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }

        $owner = User::query()->create([
            'email' => 'race-owner-'.bin2hex(random_bytes(4)).'@example.test',
            'password' => Hash::make('secret-secret'),
            'role_id' => Role::findByName(Role::TUTOR)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
        $learner = User::query()->create([
            'email' => 'race-learner-'.bin2hex(random_bytes(4)).'@example.test',
            'password' => Hash::make('secret-secret'),
            'role_id' => Role::findByName(Role::LEARNER)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
        $course = Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'paid',
            'price' => 40,
            'title' => 'Race Course '.bin2hex(random_bytes(3)),
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
            'title' => 'Race Class',
            'description' => 'Class description',
            'visibility' => true,
            'class_type' => 'onsite',
            'is_paid' => 'paid',
            'price' => 40,
            'capacity' => 5,
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
        ]);
        $this->cleanupIds = [
            'users' => [$owner->id, $learner->id],
            'courses' => [$course->id],
            'classes' => [$class->id],
        ];
        $sessionId = 'cs_same_'.bin2hex(random_bytes(4));
        $snapshot = CheckoutSnapshot::query()->create([
            'user_id' => $learner->id,
            'item_type' => 'class',
            'item_id' => $class->id,
            'amount_cents' => 4000,
            'currency' => 'usd',
            'stripe_session_id' => $sessionId,
            'state' => CheckoutSnapshot::OPEN,
        ]);
        $session = [
            'id' => $sessionId,
            'payment_status' => 'paid',
            'status' => 'complete',
            'amount_total' => 4000,
            'currency' => 'usd',
            'payment_intent' => 'pi_same_'.bin2hex(random_bytes(3)),
            'metadata' => [
                'user_id' => (string) $learner->id,
                'item_type' => 'class',
                'item_id' => (string) $class->id,
                'snapshot_id' => (string) $snapshot->id,
            ],
        ];

        $this->runTogether([$session, $session]);

        $this->assertSame(1, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(1, Payment::query()->where('stripe_session_id', $sessionId)->count());
        $this->assertSame(4, (int) $class->fresh()->capacity);
    }

    private function runTogether(array $sessions): string
    {
        $processes = [];
        $output = '';
        foreach ($sessions as $session) {
            $json = storage_path('framework/race-'.bin2hex(random_bytes(4)).'.json');
            file_put_contents($json, json_encode(['session' => $session]));
            $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('tests/Support/fulfill_race.php')).' '.escapeshellarg($json);
            $pipes = [];
            $handle = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
            $processes[] = ['handle' => $handle, 'pipes' => $pipes, 'json' => $json];
        }

        foreach ($processes as $process) {
            $stdout = stream_get_contents($process['pipes'][1]);
            $stderr = stream_get_contents($process['pipes'][2]);
            fclose($process['pipes'][1]);
            fclose($process['pipes'][2]);
            $exit = proc_close($process['handle']);
            @unlink($process['json']);
            $output .= $stdout."\n".$stderr."\n";
            $this->assertSame(0, $exit, $stderr."\n".$stdout);
            $this->assertStringNotContainsString('SQLSTATE', $stderr, $stdout);
        }

        return $output;
    }
}
