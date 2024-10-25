<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class DashboardEnrolledCourses extends Component
{
    public function render()
    {
        return view('livewire.dashboard.dashboard-enrolled-courses')->layout('components.layouts.dashboard');
    }
}
