<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Events;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FreeEnrollment
{
    public const ENROLLED = 'enrolled';

    public const PAID = 'paid';

    public const FULL = 'full';

    public const DUPLICATE = 'duplicate';

    public const MISSING = 'missing';

    public const UNPRICED = 'unpriced';

    /**
     * Statuses that already occupy the learner's seat for an item.
     */
    public const ACTIVE_STATUSES = ['free', 'paid', 'pending', 'unpaid'];

    public function enrollClass(User $user, mixed $classId): EnrollmentResult
    {
        return $this->enroll($user, $classId, Classe::class, 'class_id');
    }

    public function enrollCourse(User $user, mixed $courseId): EnrollmentResult
    {
        return $this->enroll($user, $courseId, Course::class, 'course_id');
    }

    public function enrollEvent(User $user, mixed $eventId): EnrollmentResult
    {
        return $this->enroll($user, $eventId, Events::class, 'event_id');
    }

    /**
     * Remove only the automated free booking created by this service.
     * Paid bookings and payment rows are left untouched.
     */
    public function release(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $locked = Booking::query()->lockForUpdate()->find($booking->id);

            if ($locked === null
                || $locked->payment_status !== 'free'
                || ($locked->type ?? 'automated') !== 'automated'
                || $locked->payments()->exists()
            ) {
                return;
            }

            $this->restoreCapacity($locked);
            $locked->delete();
        });
    }

    /**
     * A row is free only when it is not marked paid and has no positive price.
     */
    public function itemIsFree(Model $item): bool
    {
        if (($item->is_paid ?? null) === 'paid') {
            return false;
        }

        return (float) ($item->price ?? 0) <= 0;
    }

    private function enroll(User $user, mixed $id, string $modelClass, string $foreignKey): EnrollmentResult
    {
        if (! is_numeric($id)) {
            return new EnrollmentResult(self::MISSING);
        }

        return DB::transaction(function () use ($user, $id, $modelClass, $foreignKey) {
            $item = $modelClass::query()->lockForUpdate()->find((int) $id);

            if ($item === null) {
                return new EnrollmentResult(self::MISSING);
            }

            if (! $this->itemIsFree($item)) {
                if ($this->isFull($item)) {
                    return new EnrollmentResult(self::FULL, null, $item);
                }

                if ((float) ($item->price ?? 0) <= 0) {
                    return new EnrollmentResult(self::UNPRICED, null, $item);
                }

                if ($this->hasActiveBooking($user, $foreignKey, $item->id)) {
                    return new EnrollmentResult(self::DUPLICATE, null, $item);
                }

                return new EnrollmentResult(self::PAID, null, $item);
            }

            if ($this->isFull($item)) {
                return new EnrollmentResult(self::FULL, null, $item);
            }

            if ($this->hasActiveBooking($user, $foreignKey, $item->id)) {
                return new EnrollmentResult(self::DUPLICATE, null, $item);
            }

            $booking = Booking::query()->create([
                $foreignKey => $item->id,
                'user_id' => $user->id,
                'phone' => $user->phone,
                'payment_status' => 'free',
                'type' => 'automated',
                'order_number' => uniqid('FREE_', true),
            ]);

            if ($foreignKey !== 'course_id') {
                CalendarEvent::query()->create([
                    'user_id' => $user->id,
                    $foreignKey => $item->id,
                ]);
            }

            if ($this->tracksCapacity($item) && $item->capacity !== null) {
                $item->decrement('capacity');
            }

            return new EnrollmentResult(self::ENROLLED, $booking, $item);
        });
    }

    private function hasActiveBooking(User $user, string $foreignKey, int $itemId): bool
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->where($foreignKey, $itemId)
            ->whereIn('payment_status', self::ACTIVE_STATUSES)
            ->lockForUpdate()
            ->exists();
    }

    private function isFull(Model $item): bool
    {
        if (! $this->tracksCapacity($item) || $item->capacity === null) {
            return false;
        }

        return (int) $item->capacity <= 0;
    }

    private function tracksCapacity(Model $item): bool
    {
        return $item instanceof Classe || $item instanceof Events;
    }

    private function restoreCapacity(Booking $booking): void
    {
        if ($booking->class_id) {
            $class = Classe::query()->lockForUpdate()->find($booking->class_id);
            if ($class && $class->capacity !== null) {
                $class->increment('capacity');
            }
            CalendarEvent::query()
                ->where('user_id', $booking->user_id)
                ->where('class_id', $booking->class_id)
                ->delete();
        }

        if ($booking->event_id) {
            $event = Events::query()->lockForUpdate()->find($booking->event_id);
            if ($event && $event->capacity !== null) {
                $event->increment('capacity');
            }
            CalendarEvent::query()
                ->where('user_id', $booking->user_id)
                ->where('event_id', $booking->event_id)
                ->delete();
        }
    }
}
