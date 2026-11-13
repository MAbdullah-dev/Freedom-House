<?php

namespace App\Livewire\CourseAnalytics;

use App\Livewire\Concerns\RequiresRole;
use App\Models\Role;
use Livewire\Component;

class CourseDetail extends Component
{
    use RequiresRole;

    public function boot(): void
    {
        $this->requireRole(Role::TUTOR, Role::ADMIN);
    }

    public function render()
    {
        return view('livewire.course-analytics.course-detail')->layout('components.layouts.dashboard');
    }
}
