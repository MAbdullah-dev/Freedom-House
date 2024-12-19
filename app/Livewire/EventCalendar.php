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
        if ($user) {
            $events = CalendarEvent::with(['event', 'user'])
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereNotNull('event_id');
                })
                ->get()
                ->map(function ($calendarEvent) {
                    return [
                        'id' => $calendarEvent->id,
                        'title' => $calendarEvent->event ? $calendarEvent->event->title : $calendarEvent->class->title,
                        'date' => Carbon::parse($calendarEvent->event ? $calendarEvent->event->class_date : $calendarEvent->class->class_date)->toIso8601String(),
                        'start_date' => Carbon::parse($calendarEvent->event ? $calendarEvent->event->start_date : $calendarEvent->class->start_date)->toIso8601String(),
                        'end_date' => Carbon::parse($calendarEvent->event ? $calendarEvent->event->end_date : $calendarEvent->class->end_date)->toIso8601String(),
                        'description' => $calendarEvent->event ? $calendarEvent->event->description : $calendarEvent->class->description,
                        'spotsLeft' => $calendarEvent->event ? $calendarEvent->event->capacity : $calendarEvent->class->capacity,
                        'type' => 'event',
                        'created_by' => $calendarEvent->user->username ?? 'Unknown',
                    ];
                });
            $this->events = $events;
        } else {
            $this->events = [];
        }
        $this->eventCards = Events::all();
        $this->dispatch('eventsLoaded', $this->events);
        $this->dispatch('eventCardsLoaded', $this->eventCards);
    }
    public function selectEvent($event, $type)
    {
        $this->selectedEvent = $event;
        $this->modalType = $type;
        $this->dispatch('openModal');
    }
    public function render()
    {
        return view('livewire.event-calendar');
    }
}
