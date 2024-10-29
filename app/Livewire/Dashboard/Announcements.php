<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class Announcements extends Component
{
    public function render()
    {
        return view('livewire.dashboard.announcements')->layout('components.layouts.dashboard');
    }
}
