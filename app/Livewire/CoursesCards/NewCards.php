<?php

namespace App\Livewire\CoursesCards;

use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NewCards extends Component
{
    public $newCourses;
    public function mount()
    {
        $this->loadNewCourses();
    }
    public function loadNewCourses()
    {
        $this->newCourses = Course::with(['user', 'instructors'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();
    }
    public function toggleWishlist($courseId)
    {
        $user = Auth::user();

        if (!$user) {
            session()->flash('error', 'You need to log in to manage your wishlist.');
            return;
        }

        // Toggle wishlist status
        if ($user->wishlist()->where('course_id', $courseId)->exists()) {
            $user->wishlist()->detach($courseId); // Remove from wishlist
        } else {
            $user->wishlist()->attach($courseId); // Add to wishlist
        }

        // Reload the most popular courses to reflect changes
        $this->loadNewCourses();
    }
    public function render()
    {
        return view('livewire.courses-cards.new-cards');
    }
}
