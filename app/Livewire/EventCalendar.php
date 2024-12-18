<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\Events;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EventCalendar extends Component
{
    public $modalType;
    public $events = [];
    public $classes = [];
    public $selectedEvent;
    public $eventCards;

    public function mount()
    {
        $user = Auth::user();

        // Fetch events created by the user or events the user has booked from calendar_events
        $events = CalendarEvent::with(['event', 'user'])
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)  // Created by the user
                    ->orWhereNotNull('event_id');    // Events that are related to the user
            })
            ->get()
            ->map(function ($calendarEvent) {
                return [
                    'id' => $calendarEvent->id,
                    'title' => $calendarEvent->event ? $calendarEvent->event->title : $calendarEvent->class->title, // Assuming class_id will also exist in some cases
                    'date' => Carbon::parse($calendarEvent->event ? $calendarEvent->event->class_date : $calendarEvent->class->class_date)->toIso8601String(),
                    'start_date' => Carbon::parse($calendarEvent->event ? $calendarEvent->event->start_date : $calendarEvent->class->start_date)->toIso8601String(),
                    'end_date' => Carbon::parse($calendarEvent->event ? $calendarEvent->event->end_date : $calendarEvent->class->end_date)->toIso8601String(),
                    'description' => $calendarEvent->event ? $calendarEvent->event->description : $calendarEvent->class->description,
                    'spotsLeft' => $calendarEvent->event ? $calendarEvent->event->capacity : $calendarEvent->class->capacity,
                    'type' => 'event', // You can differentiate between class and event by the type here if needed
                    'created_by' => $calendarEvent->user->username ?? 'Unknown',
                ];
            });

        $this->events = $events;

        // Fetch all created events for the right section (Event Cards)
        $allEvents = CalendarEvent::with('event')
            ->whereNotNull('event_id')
            ->get()
            ->map(function ($calendarEvent) {
                return [
                    'id' => $calendarEvent->id,
                    'title' => $calendarEvent->event->title,
                    'date' => Carbon::parse($calendarEvent->event->class_date)->toIso8601String(),
                    'start_date' => Carbon::parse($calendarEvent->event->start_date)->toIso8601String(),
                    'end_date' => Carbon::parse($calendarEvent->event->end_date)->toIso8601String(),
                    'description' => $calendarEvent->event->description,
                    'spotsLeft' => $calendarEvent->event->capacity,
                    'type' => 'event',
                    'created_by' => $calendarEvent->user->username ?? 'Unknown',
                ];
            });

        $this->eventCards = $allEvents;

        $this->dispatch('eventsLoaded', $this->events);
        $this->dispatch('eventCardsLoaded', $this->eventCards);
    }

    public function selectEvent($event, $type)
    {
        $this->selectedEvent = $event;
        $this->modalType = $type;
        $this->dispatch('openModal'); // Dispatch event to trigger the modal
    }

    public function render()
    {
        return view('livewire.event-calendar');
    }
}
