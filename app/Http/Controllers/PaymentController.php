<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Notification;
use App\Models\Events;
use App\Models\Payment;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    public function success(Request $request)
    {
        $userId = Auth::id();
        $user = Auth::user();
        $amount = session('stripe_payment_amount'); // retrieve the session value
        $sessionId = $request->query('session_id');
        $classId = $request->query('class_id'); // class payments
        $courseId = $request->query('course_id'); // course payments
        $eventId = $request->query('event_id'); // event payments

        $courseName = Course::where('id', $courseId)->value('title');
        $className = Classe::where('id', $classId)->value('title');
        $eventName = Events::where('id', $eventId)->value('title');
        // dd($courseName);

        if ($courseId) {
            // Handle Course Enrollment
            $booking = Booking::create([
                'course_id' => $courseId,
                'user_id' => $userId,
                'class_id' => null,
                'payment_status' => 'paid',
                'order_number' => uniqid('PAID_COURSE_'),
            ]);

            Payment::create([
                'amount' => $amount,
                'booking_id' => $booking->id,
                'payment_method' => 'stripe',
                'transaction_id' => $sessionId,
            ]);
            Notification::create([
                'user_id' => $userId,
                'message' => 'You successfully purchased the course: ' . $courseName . ' 😊.',
                'type' => 'purchase',
            ]);
            // Optionally reduce available slots in the course if needed
            // Course::where('id', $courseId)->decrement('capacity');

            return redirect()->route("onsite.course.details", $courseId)
                ->with('success', 'Payment successful! You are enrolled in the course.');
        } elseif ($classId) {
            $class = Classe::findOrFail($classId);
            // Handle Class Enrollment
            $booking = Booking::create([
                'class_id' => $classId,
                'course_id' => null,
                'user_id' => $userId,
                'payment_status' => 'paid',
                'order_number' => uniqid('PAID_CLASS_'),
            ]);

            Payment::create([
                'amount' => $amount,
                'booking_id' => $booking->id,
                'payment_method' => 'stripe',
                'transaction_id' => $sessionId,
            ]);
            Notification::create([
                'user_id' => $userId,
                'message' => 'You successfully purchased the course: ' . $className . ' 😊.',
                'type' => 'purchase',
            ]);

            // Reduce class capacity
            Classe::where('id', $classId)->decrement('capacity');

            CalendarEvent::create([
                'user_id' => $userId,
                'class_id' => $classId,
            ]);

            $this->googleCalendarService->sendInvite($user, $class, 'class');

            return redirect()->route("class.details", $classId)
                ->with('success', 'Payment successful! You are enrolled in the class.');
        } elseif ($eventId) {
            // Handle Event Enrollment
            $event = Events::findOrFail($eventId);

            $booking = Booking::create([
                'event_id' => $eventId,
                'user_id' => $userId,
                'payment_status' => 'paid',
                'order_number' => uniqid('PAID_EVENT_'),
            ]);

            Payment::create([
                'amount' => $amount,
                'booking_id' => $booking->id,
                'payment_method' => 'stripe',
                'transaction_id' => $sessionId,
            ]);
            Notification::create([
                'user_id' => $userId,
                'message' => 'You successfully purchased the course: ' . $eventName . ' 😊.',
                'type' => 'purchase',
            ]);
            // Optionally reduce available slots in the event if needed
            Events::where('id', $eventId)->decrement('capacity');

            CalendarEvent::create([
                'user_id' => $userId,
                'event_id' => $eventId,
            ]);

            $this->googleCalendarService->sendInvite($user, $event, 'event');

            return redirect()->route("calendar")
                ->with('success', 'Payment successful! You are enrolled in the event.');
        }

        // Default case - something went wrong
        return redirect()->route('dashboard')
            ->with('error', 'Payment failed. Please try again.');
    }

    public function cancel()
    {
        return redirect()->route('dashboard')->with('error', 'Payment canceled. Please try again.');
    }
}
