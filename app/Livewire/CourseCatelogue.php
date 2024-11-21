<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Tutor;
use Livewire\Component;

class CourseCatelogue extends Component
{
    public $search = '';
    public $featuredTutors;

    public function mount()
    {
        $this->featuredTutors = Tutor::with('user')->get();
    }

    public function render()
    {
        $courses = Course::with(['user', 'instructors'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->get();

        return view('livewire.course-catelogue', [
            'courses' => $courses,
            'featuredTutors' => $this->featuredTutors,
        ]);
    }
}
