<?php

namespace App\Livewire\CoursesCards;

use App\Models\Course;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MostPopularCards extends Component
{
    public $mostPopularCourses;

    // Fetch courses and their wishlist state
    public function mount()
    {
        $this->mostPopularCourses = Course::with(['user', 'instructors'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();

        // Attach wishlist status to each course
        foreach ($this->mostPopularCourses as $course) {
            $course->wishlist = Wishlist::where('user_id', Auth::id())
                ->where('course_id', $course->id)
                ->exists();
        }
    }

    // Function to toggle course wishlist
    public function toggleWishlist($courseId)
    {
        $userId = Auth::id();

        // Check if the course is already in the wishlist
        $wishlist = Wishlist::where('user_id', $userId)->where('course_id', $courseId)->first();

        if ($wishlist) {
            // Remove the course from the wishlist
            $wishlist->delete();
        } else {
            // Add the course to the wishlist
            Wishlist::create([
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);
        }

        // Update the wishlist state for the course
        $this->mostPopularCourses = Course::with(['user', 'instructors'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();
    }

    public function render()
    {
        return view('livewire.courses-cards.most-popular-cards');
    }
}
