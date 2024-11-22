<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\Events;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EventCalendar extends Component
{
public $events = [];

    public function mount()
    {

    $this->dispatch('calendarInitialized');
 
        $this->events = CalendarEvent::with(['class', 'event', 'user'])
    ->get()
    ->map(function ($calendarEvent) {
        if ($calendarEvent->class) {
            return [
                'id' => $calendarEvent->id,
                'title' => $calendarEvent->class->title,
                'start' => Carbon::parse($calendarEvent->class->start_date)->toIso8601String(),
                'end' => Carbon::parse($calendarEvent->class->end_date)->toIso8601String(),
                'description' => $calendarEvent->class->description,
                'spotsLeft' => $calendarEvent->class->capacity,
                'type' => 'class',
                'class_id' => $calendarEvent->class_id,
                'course_title' => $calendarEvent->class->course->title ?? null,
                // 'user_name' => $calendarEvent->user->name ?? null,  
            ];
        }

        if ($calendarEvent->event) {
            return [
                'id' => $calendarEvent->id,
                'title' => $calendarEvent->event->title,
                'start' => Carbon::parse($calendarEvent->event->start_date)->toIso8601String(),
                'end' => Carbon::parse($calendarEvent->event->end_date)->toIso8601String(),
                'description' => $calendarEvent->event->description,
                'spotsLeft' => $calendarEvent->event->capacity,
                'type' => 'event',
                'event_id' => $calendarEvent->event_id,
                // 'user_name' => $calendarEvent->user->name ?? null,  // added null check
            ];
        }
    })->toArray();
        $this->dispatch('eventsLoaded', $this->events);


             Log::debug('Livewire events data:', ['events' => $this->events]);

    }

    public function render()
    {
        return view('livewire.event-calendar');
    }
}
