<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class CreateCourse extends Component
{
    public function render()
    {
        return view('livewire.dashboard.create-course')->layout('components.layouts.createCourse');
    }
}
