<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class CourseAnalytics extends Component
{
    public function render()
    {
        return view('livewire.dashboard.course-analytics')->layout('components.layouts.dashboard');
    }
}
