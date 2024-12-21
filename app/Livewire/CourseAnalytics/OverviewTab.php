<?php

namespace App\Livewire\CourseAnalytics;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OverviewTab extends Component
{
    public $courseCount;
    public $studentCount;
    public $reviewCount;
    public $highestRatedCourse;
    public $recentReviews;

    public function mount()
    {
        // Get all published courses by the logged-in tutor
        $courses = Course::where('is_published', true)
            ->where('is_drafted', false)
            ->where('user_id', Auth::id())
            ->pluck('id');

        $this->courseCount = $courses->count();
        $this->studentCount = Booking::whereIn('course_id', $courses)->count();
        $this->reviewCount = Review::whereIn('course_id', $courses)->count();

        $this->highestRatedCourse = Course::with(['reviews', 'bookings'])
            ->whereIn('id', $courses)
            ->withAvg('reviews', 'rating')
            ->withCount('bookings')
            ->orderByDesc('reviews_avg_rating')
            ->take(5)
            ->get();

        // Get the recent reviews
        $this->recentReviews = Review::whereIn('course_id', $courses)
            ->with('user', 'course')
            ->latest()
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.course-analytics.overview-tab');
    }
}
