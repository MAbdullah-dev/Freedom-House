<?php

namespace App\Livewire;

use App\Models\Course;
use Livewire\Component;

class CourseCatelogue extends Component

{
public $allCourses;

    public function mount()
{
    $this->allCourses = Course::with(['user', 'instructors'])->get();

}

    public function render()
    {
        return view('livewire.course-catelogue');
    }
}
