<?php

namespace App\Livewire\CoursesCards;

use App\Models\Course;
use Livewire\Component;

class TrendingCards extends Component
{
    public $trendingCourses;
    public function mount()
    {
        $this->trendingCourses = Course::with(['user', 'instructors'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();
    }
    public function render()
    {
        return view('livewire.courses-cards.trending-cards');
    }
}
