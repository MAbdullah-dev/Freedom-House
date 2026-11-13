<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\RequiresRole;
use App\Models\Role;
use Livewire\Component;

class CourseAnalytics extends Component
{
    use RequiresRole;

    public $activeTab = 'overview';

    public function boot(): void
    {
        $this->requireRole(Role::TUTOR, Role::ADMIN);
    }

    public  function mount()
    {
        $this->dispatch('renderChart');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.dashboard.course-analytics')->layout('components.layouts.dashboard');
    }
}
