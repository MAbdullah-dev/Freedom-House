<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class DashboardProfile extends Component
{
    public function render()
    {
        return view('livewire.dashboard.dashboard-profile')->layout('components.layouts.dashboard');
    }
}
