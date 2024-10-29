<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class MyCourses extends Component
{
    public function render()
    {
        return view('livewire.dashboard.my-courses')->layout('components.layouts.dashboard');
    }
}
