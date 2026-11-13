<?php

namespace App\Http\Controllers;

use App\Services\EnrollmentFulfillment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PaymentController extends Controller
{
    public function success(Request $request, EnrollmentFulfillment $fulfillment)
    {
        $sessionId = $request->query('session_id');
        $user = Auth::user();

        if (! is_string($sessionId) || $sessionId === '' || $user === null) {
            return redirect()->route('dashboard')->with('error', 'Payment could not be verified.');
        }

        try {
            $result = $fulfillment->retrieveForUser($sessionId, $user);
        } catch (Throwable) {
            return redirect()->route('dashboard')->with('error', 'Payment could not be verified.');
        }

        if ($result->reason === 'wrong_user') {
            abort(403);
        }

        return match ($result->outcome) {
            EnrollmentFulfillment::FULFILLED, EnrollmentFulfillment::PARTIALLY_REFUNDED => redirect()
                ->route('dashboard')
                ->with('success', 'Payment verified. Your enrollment is active.'),
            EnrollmentFulfillment::UNFULFILLABLE => redirect()
                ->route('dashboard')
                ->with('error', 'Payment was received but the enrollment needs administrator review.'),
            EnrollmentFulfillment::REFUNDED => redirect()
                ->route('dashboard')
                ->with('error', 'This payment was refunded before enrollment.'),
            default => redirect()
                ->route('dashboard')
                ->with('error', 'Payment could not be verified.'),
        };
    }

    public function cancel()
    {
        return redirect()->route('dashboard')->with('error', 'Payment canceled. Please try again.');
    }

    public function certifictes()
    {
        return view('certificates.dummy');
    }
}
