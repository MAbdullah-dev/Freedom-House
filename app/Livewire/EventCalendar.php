<?php

namespace App\Livewire;

use Livewire\Component;

class EventCalendar extends Component
{
    public function mount()
    {
        $this->dispatch('calendarInitialized');
    }
    public function render()
    {
        return view('livewire.event-calendar');
    }
}
