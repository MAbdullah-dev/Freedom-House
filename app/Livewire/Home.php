<?php

namespace App\Livewire;

use App\Models\Course;
use Livewire\Component;

class Home extends Component
{
    public $courses;
    public function mount()
    {
        $this->courses = Course::with(['user', 'tutors'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();
    }
    public function render()
    {
        return view('livewire.home');
    }
}
