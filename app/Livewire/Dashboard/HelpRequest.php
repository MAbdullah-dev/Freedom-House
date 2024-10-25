<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class HelpRequest extends Component
{
    public function render()
    {
        return view('livewire.dashboard.help-request')->layout('components.layouts.dashboard');
    }
}
