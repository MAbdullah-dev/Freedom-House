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

        $events = CalendarEvent::with(['event', 'user'])
            ->whereNotNull('event_id')
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
                        'created_by' => $calendarEvent->user->username ?? 'Unknown',
                    ];
                }
            })->toArray();

        $this->events = $events;

        if ($user) {
            $createdClasses = CalendarEvent::with(['class', 'user'])
                ->whereNotNull('class_id')
                ->whereHas('user', function ($query) use ($user) {
                    $query->where('id', $user->id);
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
                            'created_by' => $calendarEvent->user->username ?? 'Unknown',
                        ];
                    }
                })->toArray();

            $bookedClasses = Booking::with(['calendarEvent', 'calendarEvent.class'])
                ->where('user_id', $user->id)
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
                            'created_by' => $calendarEvent->user->username ?? 'Unknown',
                        ];
                    }
                })->toArray();

            $this->classes = array_merge($createdClasses, $bookedClasses);

            $this->dispatch('eventsLoaded', $this->events);
            $this->dispatch('classesLoaded', $this->classes);

            Log::debug('Livewire events and classes data:', ['events' => $this->events, 'classes' => $this->classes]);
        } else {
            $this->dispatch('eventsLoaded', $this->events);

            Log::warning('No user logged in, no classes to display.');
        }
    }
    public function render()
    {
        return view('livewire.event-calendar');
    }
}
