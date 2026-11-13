<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    public const ACCESS_STATUSES = ['paid', 'free'];

    protected $fillable = [
        'event_id',
        'class_id',
        'user_id',
        'order_number',
        'course_id',
        'type',
        'email',
        'payment_status',
        'created_by',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function class()
    {
        return $this->belongsTo(Classe::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function event()
    {
        return $this->belongsTo(Events::class);
    }
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public static function enrollmentKey(mixed $userId, mixed $courseId, mixed $classId, mixed $eventId): ?string
    {
        if (! is_numeric($userId)) {
            return null;
        }

        $parts = [];
        if (is_numeric($courseId)) {
            $parts[] = 'course:'.(int) $courseId;
        }
        if (is_numeric($classId)) {
            $parts[] = 'class:'.(int) $classId;
        }
        if (is_numeric($eventId)) {
            $parts[] = 'event:'.(int) $eventId;
        }

        if (count($parts) !== 1) {
            return null;
        }

        return 'user:'.(int) $userId.':'.$parts[0];
    }

    protected static function booted(): void
    {
        static::saving(function (Booking $booking) {
            if (in_array($booking->payment_status, self::ACCESS_STATUSES, true)) {
                $booking->active_enrollment_key = self::enrollmentKey(
                    $booking->user_id,
                    $booking->course_id,
                    $booking->class_id,
                    $booking->event_id
                );
            } else {
                $booking->active_enrollment_key = null;
            }
        });
    }
}
