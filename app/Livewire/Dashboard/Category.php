<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class Category extends Component
{
    public function render()
    {
        return view('livewire.dashboard.category')->layout('components.layouts.dashboard');
    }
}
