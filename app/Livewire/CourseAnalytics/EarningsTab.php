<?php

namespace App\Livewire\CourseAnalytics;

use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EarningsTab extends Component
{
    public $totalEarnings;
    public $totalSales;
    public $totalWithdrawals;
    public $totalCommissions;
    public $totalFees;
    public $monthlyEarnings = []; // Data for the chart

    public function mount()
    {
        $user = Auth::user();

        // Get the courses for the authenticated user that are published and not drafted
        $courses = Course::where('user_id', $user->id)
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();

        $totalEarnings = 0;
        $totalSales = 0;
        $totalWithdrawals = 0;
        $totalCommissions = 0;
        $totalFees = 0;

        $earningsByMonth = array_fill(1, 12, 0); // Initialize earnings for each month

        // Loop through the courses and calculate the totals
        foreach ($courses as $course) {
            $bookings = $course->bookings; // Get the bookings for the course

            foreach ($bookings as $booking) {
                // Get the payments for the current booking
                $payments = $booking->payments; // This assumes payments are related to the booking via booking_id

                // Get the sum of payments for the booking
                $paymentSum = $payments->sum('amount');

                // Update the total earnings, sales, and withdrawals
                $totalEarnings += $paymentSum;
                $totalSales += $paymentSum;
                $totalWithdrawals += $paymentSum;

                // Loop through each payment to access its created_at date and month
                foreach ($payments as $payment) {
                    // dd($payment);
                    $month = $payment->created_at->month; // Get the month of the payment
                    // Accumulate earnings by month
                    $earningsByMonth[$month] = ($earningsByMonth[$month]) + $payment->amount;
                }
            }
        }

        // Assign the calculated values to the public properties
        $this->totalEarnings = $totalEarnings;
        $this->totalSales = $totalSales;
        $this->totalWithdrawals = $totalWithdrawals;
        $this->totalCommissions = $totalCommissions;
        $this->totalFees = $totalFees;

        $this->monthlyEarnings = array_values($earningsByMonth); // Convert array to a simple indexed array
    }

    public function render()
    {
        return view('livewire.course-analytics.earnings-tab', [
            'chartLabels' => json_encode(range(1, 12)), // Months (1 to 12)
            'chartData' => json_encode($this->monthlyEarnings),
        ]);
    }
}
