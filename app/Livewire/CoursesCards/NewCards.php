<?php

namespace App\Livewire\CoursesCards;

use App\Models\Course;
use Livewire\Component;

class NewCards extends Component
{
    public $newCourses;
    public function mount()
    {
        $this->newCourses = Course::with(['user', 'instructors'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();
    }
    public function render()
    {
        return view('livewire.courses-cards.new-cards');
    }
}
