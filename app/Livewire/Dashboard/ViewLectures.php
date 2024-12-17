<?php

namespace App\Livewire\Dashboard;

use App\Models\Course;
use Livewire\Component;

class ViewLectures extends Component
{
    public $courseId;
    public $course;

    public function mount($courseId)
    {
        $this->courseId = $courseId;
        $this->course = Course::with('lectures')->findOrFail($this->courseId);
    }

    public function render()
    {
        return view('livewire.dashboard.view-lectures', [
            'lectures' => $this->course->lectures,
        ]);
    }
}
