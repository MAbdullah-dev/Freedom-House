<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Tutor;
use Livewire\Component;

class CourseCatelogue extends Component

{
public $allCourses,$featuredTutors;

    public function mount()
{
    $this->allCourses = Course::with(['user', 'instructors'])->where('is_published', true)
            ->where('is_drafted', false)
            ->get();

    $this->featuredTutors = Tutor::with('user')->get();

}

    public function render()
    {
        return view('livewire.course-catelogue');
    }
}
