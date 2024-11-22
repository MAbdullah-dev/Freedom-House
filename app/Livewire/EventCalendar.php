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
public $events = [];
public $classes = [];

   public function mount()
    {
        $user = Auth::user();

        // Fetch all events (visible to everyone: logged-in and logged-out users)
        $events = CalendarEvent::with(['event', 'user'])
            ->whereNotNull('event_id') // Ensure it's an event
            ->get()
            ->map(function ($calendarEvent) {
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
                        'created_by' => $calendarEvent->user->name ?? 'Unknown',
                    ];
                }
            })->toArray();

        // Store the events data, which are available for everyone
        $this->events = $events;

        // If the user is logged in, fetch the classes they have created or are booked for
        if ($user) {
            // Fetch classes created by the logged-in user
            $createdClasses = CalendarEvent::with(['class', 'user'])
                ->whereNotNull('class_id') // Ensure it's a class
                ->whereHas('user', function ($query) use ($user) {
                    $query->where('id', $user->id);  // Only show classes created by the logged-in user
                })
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
                            'created_by' => $calendarEvent->user->name ?? 'Unknown',
                        ];
                    }
                })->toArray();

            // Fetch classes the user is booked for (through the bookings table)
            $bookedClasses = Booking::with(['calendarEvent', 'calendarEvent.class'])
                ->where('user_id', $user->id) // The user is associated with the booking
                ->get()
                ->map(function ($booking) {
                    $calendarEvent = $booking->calendarEvent;
                    if ($calendarEvent && $calendarEvent->class) {
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
                            'created_by' => $calendarEvent->user->name ?? 'Unknown',
                        ];
                    }
                })->toArray();

            // Combine the created and booked classes
            $this->classes = array_merge($createdClasses, $bookedClasses);

            // Dispatch both events and classes data to the frontend
            $this->dispatch('eventsLoaded', $this->events);
            $this->dispatch('classesLoaded', $this->classes);

            Log::debug('Livewire events and classes data:', ['events' => $this->events, 'classes' => $this->classes]);
        } else {
            // If no user is logged in, just dispatch the events
            $this->dispatch('eventsLoaded', $this->events);

            Log::warning('No user logged in, no classes to display.');
        }
    }
    public function render()
    {
        return view('livewire.event-calendar');
    }
}
