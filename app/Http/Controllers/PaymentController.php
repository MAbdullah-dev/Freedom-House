<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Classe;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function success(Request $request)
    {
        $classId = $request->query('class_id');
        $amount = session('stripe_payment_amount'); // Retrieve the session value
        $courseId = $request->query('course_id');
        $booking = Booking::create([
            'class_id' => $classId,
            'user_id' => Auth::id(),
            'payment_status' => 'paid',
            'order_number' => uniqid('PAID_'),
        ]);

        Payment::create([
            'amount' => $amount,
            'booking_id' => $booking->id,
            'payment_method' => 'stripe',
            'transaction_id' => $request->query('session_id'),
        ]);
        Classe::where('id', $classId)->update([
            'capacity' => Classe::where('id', $classId)->value('capacity') - 1
        ]);

        return redirect()->route("course.details", $courseId)->with('success', 'Payment successful and enrollment completed!');
    }

    public function cancel()
    {
        return redirect()->route('dashboard')->with('error', 'Payment canceled. Please try again.');
    }
}
