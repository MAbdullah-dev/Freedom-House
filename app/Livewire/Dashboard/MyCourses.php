<?php

namespace App\Livewire\Dashboard;

use App\Models\Course;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class MyCourses extends Component
{
    public $publishedCourses;
    public $pendingCourses;
    public $draftedCourses;
    public $loggedInUser;

    public function mount()
    {
        // Get the currently logged-in user
        $this->loggedInUser = Auth::user();

        // Get courses based on the conditions for the logged-in user
        $this->publishedCourses = Course::where('user_id', Auth::id())
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();

        $this->pendingCourses = Course::where('user_id', Auth::id())
            ->where('is_published', false)
            ->where('is_drafted', false)
            ->get();

        $this->draftedCourses = Course::where('user_id', Auth::id())
            ->where('is_published', false)
            ->where('is_drafted', true)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.my-courses', [
            'publishedCourses' => $this->publishedCourses,
            'pendingCourses' => $this->pendingCourses,
            'draftedCourses' => $this->draftedCourses,
            'loggedInUser' => $this->loggedInUser, // Pass logged-in user to the view
        ])->layout('components.layouts.dashboard');
    }
}
