<?php

namespace Tests\Feature;

use App\Livewire\CourseDetails;
use App\Livewire\EventCalendar;
use App\Livewire\OnSiteCourseDetails;
use App\Models\Booking;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Events;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\CheckoutSessionFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FreeEnrollmentTest extends TestCase
{
    use DatabaseTransactions;

    private RecordingCheckout $checkout;

    private User $learner;

    private User $owner;

    private Payment $existingPayment;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        foreach ([Role::ADMIN, Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }

        $this->owner = $this->makeUser('owner-enroll@example.test', Role::TUTOR);
        $this->learner = $this->makeUser('learner-enroll@example.test', Role::LEARNER);
        $this->existingPayment = $this->makeExistingPayment();
        $this->checkout = new RecordingCheckout;
        $this->app->instance(CheckoutSessionFactory::class, $this->checkout);
    }

    public function test_a_paid_class_cannot_be_enrolled_as_free_by_manipulating_request_values(): void
    {
        $class = $this->makeClass('paid', '40.00', 8);

        Livewire::actingAs($this->learner)
            ->test(CourseDetails::class, ['id' => $class->course_id])
            ->set('isPaid', false)
            ->set('price', 0)
            ->set('slots', 99)
            ->call('enroll', $class->id, false, 0)
            ->assertRedirect('https://checkout.test/session');

        $this->assertSame([], $this->bookingStatuses('class_id', $class->id));
        $this->assertSame([
            ['type' => 'class', 'id' => $class->id, 'price' => 40.0],
        ], $this->checkout->requests);
        $this->assertExistingPaymentUnchanged();
        $this->assertSame(8, (int) $class->fresh()->capacity);
    }

    public function test_a_paid_course_cannot_be_enrolled_as_free_by_manipulating_request_values(): void
    {
        $course = $this->makeCourse('paid', '25.00');

        Livewire::actingAs($this->learner)
            ->test(OnSiteCourseDetails::class, ['id' => $course->id])
            ->set('isPaid', false)
            ->set('price', 0)
            ->set('courseId', $course->id)
            ->call('enrollCourse')
            ->assertRedirect('https://checkout.test/session');

        $this->assertSame([], $this->bookingStatuses('course_id', $course->id));
        $this->assertSame([
            ['type' => 'course', 'id' => $course->id, 'price' => 25.0],
        ], $this->checkout->requests);
        $this->assertExistingPaymentUnchanged();
    }

    public function test_a_paid_event_cannot_be_enrolled_as_free_by_manipulating_request_values(): void
    {
        $event = $this->makeEvent('paid', '15.00', 6);

        Livewire::actingAs($this->learner)
            ->test(EventCalendar::class)
            ->set('selectedEventId', $event->id)
            ->set('isPaid', 'free')
            ->set('price', 0)
            ->set('slots', 99)
            ->call('enroll')
            ->assertRedirect('https://checkout.test/session');

        $this->assertSame([], $this->bookingStatuses('event_id', $event->id));
        $this->assertSame([
            ['type' => 'event', 'id' => $event->id, 'price' => 15.0],
        ], $this->checkout->requests);
        $this->assertSame(6, (int) $event->fresh()->capacity);
        $this->assertExistingPaymentUnchanged();
    }

    public function test_a_positive_price_is_paid_even_when_the_flag_says_free(): void
    {
        $class = $this->makeClass('free', '18.00', 4);

        Livewire::actingAs($this->learner)
            ->test(CourseDetails::class, ['id' => $class->course_id])
            ->call('enroll', $class->id, false, 0)
            ->assertRedirect('https://checkout.test/session');

        $this->assertSame([], $this->bookingStatuses('class_id', $class->id));
        $this->assertSame(18.0, $this->checkout->requests[0]['price']);
    }

    public function test_a_free_class_still_enrolls_and_ignores_a_client_price(): void
    {
        $class = $this->makeClass('free', null, 3);

        Livewire::actingAs($this->learner)
            ->test(CourseDetails::class, ['id' => $class->course_id])
            ->set('price', 500)
            ->set('isPaid', true)
            ->call('enroll', $class->id, true, 500);

        $this->assertSame(['free'], $this->bookingStatuses('class_id', $class->id));
        $this->assertSame($this->learner->id, Booking::query()->where('class_id', $class->id)->value('user_id'));
        $this->assertSame(2, (int) $class->fresh()->capacity);
        $this->assertSame([], $this->checkout->requests);
        $this->assertSame(0, Payment::query()->whereIn('booking_id', Booking::query()->where('class_id', $class->id)->pluck('id'))->count());
        $this->assertExistingPaymentUnchanged();
    }

    public function test_a_free_course_still_enrolls_when_the_client_marks_it_paid(): void
    {
        $course = $this->makeCourse('free', null);

        Livewire::actingAs($this->learner)
            ->test(OnSiteCourseDetails::class, ['id' => $course->id])
            ->set('isPaid', true)
            ->set('price', 80)
            ->call('enrollCourse')
            ->assertRedirect(route('onsite.course.details', ['id' => $course->id]));

        $this->assertSame(['free'], $this->bookingStatuses('course_id', $course->id));
        $this->assertSame([], $this->checkout->requests);
    }

    public function test_a_free_event_still_enrolls_from_the_database_capacity(): void
    {
        $event = $this->makeEvent('free', '0.00', 2);

        Livewire::actingAs($this->learner)
            ->test(EventCalendar::class)
            ->set('selectedEventId', $event->id)
            ->set('isPaid', 'paid')
            ->set('price', 90)
            ->set('slots', 0)
            ->call('enroll');

        $this->assertSame(['free'], $this->bookingStatuses('event_id', $event->id));
        $this->assertSame(1, (int) $event->fresh()->capacity);
        $this->assertSame([], $this->checkout->requests);
    }

    public function test_duplicate_free_bookings_and_exhausted_capacity_are_rejected(): void
    {
        $class = $this->makeClass('free', null, 1);
        $other = $this->makeUser('second-enroll@example.test', Role::LEARNER);

        Livewire::actingAs($this->learner)
            ->test(CourseDetails::class, ['id' => $class->course_id])
            ->call('enroll', $class->id, false, 0);

        Livewire::actingAs($this->learner)
            ->test(CourseDetails::class, ['id' => $class->course_id])
            ->call('enroll', $class->id, false, 0);

        Livewire::actingAs($other)
            ->test(CourseDetails::class, ['id' => $class->course_id])
            ->call('enroll', $class->id, false, 0);

        $this->assertSame(['free'], $this->bookingStatuses('class_id', $class->id));
        $this->assertSame(0, (int) $class->fresh()->capacity);
        $this->assertSame(0, Booking::query()->where('user_id', $other->id)->where('class_id', $class->id)->count());
    }

    public function test_a_full_paid_class_does_not_start_checkout_or_create_a_booking(): void
    {
        $class = $this->makeClass('paid', '30.00', 0);

        Livewire::actingAs($this->learner)
            ->test(CourseDetails::class, ['id' => $class->course_id])
            ->call('enroll', $class->id, false, 0);

        $this->assertSame([], $this->bookingStatuses('class_id', $class->id));
        $this->assertSame([], $this->checkout->requests);
    }

    public function test_guests_cannot_enroll(): void
    {
        $class = $this->makeClass('free', null, 5);
        $course = $this->makeCourse('free', null);
        $event = $this->makeEvent('free', null, 5);

        Livewire::test(CourseDetails::class, ['id' => $class->course_id])
            ->call('enroll', $class->id, false, 0)
            ->assertForbidden();

        Livewire::test(OnSiteCourseDetails::class, ['id' => $course->id])
            ->call('enrollCourse')
            ->assertForbidden();

        Livewire::test(EventCalendar::class)
            ->set('selectedEventId', $event->id)
            ->call('enroll')
            ->assertForbidden();

        $this->assertSame(0, Booking::query()->where('class_id', $class->id)->count());
        $this->assertSame(0, Booking::query()->where('course_id', $course->id)->count());
        $this->assertSame(0, Booking::query()->where('event_id', $event->id)->count());
    }

    private function bookingStatuses(string $column, int $id): array
    {
        return Booking::query()
            ->where($column, $id)
            ->where('user_id', $this->learner->id)
            ->orderBy('id')
            ->pluck('payment_status')
            ->all();
    }

    private function assertExistingPaymentUnchanged(): void
    {
        $fresh = $this->existingPayment->fresh();

        $this->assertSame($this->existingPayment->transaction_id, $fresh->transaction_id);
        $this->assertEquals($this->existingPayment->amount, $fresh->amount);
        $this->assertSame($this->existingPayment->booking_id, $fresh->booking_id);
    }

    private function makeExistingPayment(): Payment
    {
        $course = $this->makeCourse('paid', '12.50');
        $booking = Booking::query()->create([
            'user_id' => $this->owner->id,
            'course_id' => $course->id,
            'payment_status' => 'paid',
            'order_number' => 'existing-'.bin2hex(random_bytes(4)),
        ]);

        return Payment::query()->create([
            'amount' => 12.50,
            'booking_id' => $booking->id,
            'payment_method' => 'stripe',
            'transaction_id' => 'existing-txn-'.bin2hex(random_bytes(4)),
        ]);
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

    private function makeCourse(string $isPaid, ?string $price): Course
    {
        return Course::query()->create([
            'user_id' => $this->owner->id,
            'course_type' => 'recorded',
            'is_paid' => $isPaid,
            'price' => $price,
            'title' => 'Enrollment Course '.bin2hex(random_bytes(3)),
            'description' => 'Public course description',
            'thumbnail' => 'thumb.png',
            'video_path' => 'promo.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
        ]);
    }

    private function makeClass(string $isPaid, ?string $price, int $capacity): Classe
    {
        $course = $this->makeCourse('free', null);

        return Classe::query()->create([
            'user_id' => $this->owner->id,
            'course_id' => $course->id,
            'title' => 'Enrollment Class '.bin2hex(random_bytes(3)),
            'description' => 'Class description',
            'visibility' => true,
            'class_type' => 'onsite',
            'is_paid' => $isPaid,
            'price' => $price,
            'capacity' => $capacity,
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
            'booking_start_date' => '2026-11-01',
            'booking_end_date' => '2026-12-01',
        ]);
    }

    private function makeEvent(string $isPaid, ?string $price, int $capacity): Events
    {
        return Events::query()->create([
            'user_id' => $this->owner->id,
            'title' => 'Enrollment Event '.bin2hex(random_bytes(3)),
            'description' => 'Event description',
            'capacity' => $capacity,
            'class_type' => 'onsite',
            'is_paid' => $isPaid,
            'price' => $price,
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
            'booking_start_date' => '2026-11-01',
            'booking_end_date' => '2026-12-01',
        ]);
    }
}

class RecordingCheckout extends CheckoutSessionFactory
{
    public array $requests = [];

    public function forClass(Classe $class): string
    {
        return $this->record('class', $class->id, $class->price);
    }

    public function forCourse(Course $course): string
    {
        return $this->record('course', $course->id, $course->price);
    }

    public function forEvent(Events $event): string
    {
        return $this->record('event', $event->id, $event->price);
    }

    private function record(string $type, int $id, mixed $price): string
    {
        $this->requests[] = [
            'type' => $type,
            'id' => $id,
            'price' => (float) $price,
        ];

        return 'https://checkout.test/session';
    }
}
