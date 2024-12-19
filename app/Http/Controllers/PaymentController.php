<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Notification;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function success(Request $request)
    {
        $userId = Auth::id();
        $amount = session('stripe_payment_amount'); // Retrieve the session value
        $sessionId = $request->query('session_id');

        // Check if payment is for a course or a class
        $classId = $request->query('class_id'); // For class payments
        $courseId = $request->query('course_id'); // For course payments

        $courseName = Course::where('id', $courseId)->value('title');
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
                'message' => 'You successfully purchased the course: ' . $courseName . ' 😊.',
                'type' => 'purchase',
            ]);

            // Reduce class capacity
            Classe::where('id', $classId)->decrement('capacity');

            return redirect()->route("class.details", $classId)
                ->with('success', 'Payment successful! You are enrolled in the class.');
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
