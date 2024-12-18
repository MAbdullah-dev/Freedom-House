<?php

namespace App\Livewire\Dashboard;

use App\Models\Booking;
use App\Models\CourseProgress;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardEnrolledCourses extends Component
{
    public $enrolledCourses = [];
    public $activeCourses = [];
    public $completedCourses = [];

    public function mount()
    {
        $userId = Auth::id(); // Get the logged-in user's ID

        // Fetch all courses the user has booked
        $bookedCourses = Booking::where('user_id', $userId)->where('payment_status', 'paid')->get();

        foreach ($bookedCourses as $booking) {
            // Check progress for each course in the course_progress table
            $progress = CourseProgress::where('user_id', $userId)
                ->where('course_id', $booking->course_id)
                ->value('progress') ?? 0; // Default to 0 if no progress record exists

            $courseData = [
                'course' => $booking->course,
                'progress' => $progress
            ];

            // Categorize the courses based on their progress
            if ($progress == 0) {
                $this->enrolledCourses[] = $courseData;
            } elseif ($progress > 0 && $progress < 100) {
                $this->activeCourses[] = $courseData;
            } elseif ($progress == 100) {
                $this->completedCourses[] = $courseData;
            }
        }
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-enrolled-courses')->layout('components.layouts.dashboard');
    }
}
