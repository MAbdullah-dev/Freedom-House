<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class DashboardSettings extends Component
{
    public function render()
    {
        return view('livewire.dashboard.dashboard-settings')->layout('components.layouts.dashboard');
    }
}
