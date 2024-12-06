<?php

namespace App\Livewire;

use App\Models\Lecture; // Make sure to import the Lecture model
use App\Models\Course;
use Livewire\Component;

class OnSiteCourseDetails extends Component
{
    public $courseId;
    public $courseTitle;
    public $courseDescription;
    public $learningOutcomes;
    public $targetAudience;
    public $requirements;

    public $lectures = []; // Initialize as an empty array

    public $showFullDescription = false; // Tracks the state of the description

    public function mount($id = null)
    {
        if ($id) {
            $this->courseId = $id;
            $this->loadCourseData();
            $this->loadLectures(); // Ensure lectures are loaded
        }
    }

    public function loadCourseData()
    {
        $course = Course::findOrFail($this->courseId);

        $this->courseTitle = $course->title;
        $this->courseDescription = $course->description;
        $this->learningOutcomes = $course->learning_outcomes;
        $this->targetAudience = $course->target_audience;
        $this->requirements = $course->requirements;
    }

    public function loadLectures()
    {
        $this->lectures = Lecture::where('course_id', $this->courseId)->get();
    }

    public function toggleDescription()
    {
        $this->showFullDescription = !$this->showFullDescription;
    }

    public function render()
    {
        return view('livewire.on-site-course-details');
    }
}
