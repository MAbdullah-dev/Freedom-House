<?php

namespace App\Livewire;

use App\Models\Classe;
use App\Models\Course;
use Livewire\Component;

class CourseDetails extends Component
{
    public $id;
    public $courseTitle;
    public $courseDescription;
    public $learningOutcomes;
    public $target_audience;
    public $requirements;
    public $classes;



    public function mount($id = null)
    {
        if ($id) {
            $this->id = $id;
            $this->loadcoursedata();
            $this->loadClasses();
        }
    }

    public function loadcoursedata()
    {
        $course = Course::find($this->id);

        $this->courseTitle = $course->title;
        $this->courseDescription = $course->description;
        $this->learningOutcomes = $course->learning_outcomes;
        $this->target_audience = $course->target_audience;
        $this->requirements = $course->requirements;
    }
    public function loadClasses()
    {
        $classes = Classe::where('course_id', $this->id)->get();
        $this->classes = $classes;
    }

    public function render()
    {
        return view('livewire.course-details');
    }
}
