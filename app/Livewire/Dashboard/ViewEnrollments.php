<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class ViewEnrollments extends Component
{
    public function render()
    {
        return view('livewire.dashboard.view-enrollments')->layout('components.layouts.dashboard');
    }
}
