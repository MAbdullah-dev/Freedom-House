<?php

namespace App\Livewire\Dashboard;

use App\Models\Booking;
use App\Models\Classe;
use App\Models\Events;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ViewEnrollments extends Component
{
    public $dateRange;
    public $classes = [];
    public $selectedClass;
    public $selectedEvent;
    public $events = [];
    public $items = [];
    public $enrollments;

    // Watch for changes to $dateRange and fetch classes automatically
    public function mount()
    {
        // Load all classes and events by default
        $classes = Classe::where('user_id', Auth::id())->get();
        $events = Events::where('user_id', Auth::id())->get();

        // Merge Classes and Events
        $this->items = $classes->concat($events)->sortBy(function ($item) {
            return $item->booking_start_date;
        });

        // Map to a uniform structure
        $this->items = $this->items->map(function ($item) {
            $userTimezone = Auth::user()->timezone;

            return [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item instanceof \App\Models\Classe ? 'Class' : 'Event',
                'date' => $item->class_date
                    ? \Carbon\Carbon::parse($item->class_date)->setTimezone($userTimezone)->format('F j, Y')
                    : 'Not Set',
                'time' => $item->class_time
                    ? \Carbon\Carbon::parse($item->class_time)->setTimezone($userTimezone)->format('h:i A')
                    : 'Not Set',
                'price' => $item->price ? '$' . number_format($item->price, 2) : 'Free',
                'model_type' => $item instanceof \App\Models\Classe ? 'class' : 'event',
            ];
        });
        $this->enrollments();
    }

    public function updatedDateRange()
    {
        if ($this->dateRange) {
            // Ensure the date range is correctly formatted
            $dates = explode(' to ', $this->dateRange);
            if (count($dates) !== 2) {
                session()->flash('error', 'Invalid date range format.');
                return;
            }

            $startDate = Carbon::parse($dates[0])->startOfDay();
            $endDate = Carbon::parse($dates[1])->endOfDay();

            // Fetch Classes
            $classes = Classe::where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('booking_start_date', [$startDate, $endDate])
                    ->orWhereBetween('booking_end_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('booking_start_date', '<=', $startDate)
                            ->where('booking_end_date', '>=', $endDate)
                        ;
                    });
            })
                ->orderBy('booking_start_date', 'asc')
                ->where('user_id', Auth::id())
                ->get();

            // Fetch Events
            $events = Events::where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('booking_start_date', [$startDate, $endDate])
                    ->orWhereBetween('booking_end_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('booking_start_date', '<=', $startDate)
                            ->where('booking_end_date', '>=', $endDate)
                        ;
                    });
            })
                ->orderBy('booking_start_date', 'asc')
                ->where('user_id', Auth::id())
                ->get();

            // Merge Classes and Events using concat() and sort them by 'booking_start_date'
            $this->items = $classes->concat($events)->sortBy(function ($item) {
                return $item->booking_start_date;
            });
            // Map to a uniform structure (e.g., arrays with necessary fields)
            $this->items = $this->items->map(function ($item) {
                // dd($this->items);
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'type' => $item instanceof \App\Models\Classe ? 'Class' : 'Event',
                    'date' => $item instanceof \App\Models\Classe ? $item->class_date : $item->class_date,
                    'time' => $item instanceof \App\Models\Classe ? $item->class_time : $item->class_time,
                    'price' => $item->price ? '$' . number_format($item->price, 2) : 'Free',
                    'model_type' => $item instanceof \App\Models\Classe ? 'class' : 'event', // Added model type for wire-click
                ];
            });
        }
    }
    public function enrollments()
    {
        $this->enrollments = Booking::where('user_id', Auth::id())
            ->with('class', 'event', 'course') // Eager load related models
            ->get();
    }

    public function selectItem($itemId, $modelType)
    {
        if ($modelType === 'class') {
            $this->selectedClass = Classe::findOrFail($itemId);
            $this->selectedEvent = null; // Ensure the selected event is cleared
        } elseif ($modelType === 'event') {
            $this->selectedEvent = Events::findOrFail($itemId);
            $this->selectedClass = null; // Ensure the selected class is cleared
        } else {
            session()->flash('error', 'Invalid model type selected.');
        }
    }
    public function editItem()
    {
        $this->dispatch('close-modal'); // Close the modal after editing

        if ($this->selectedClass) {
            // Redirect to class edit route if a class is selected
            return redirect()->route('dashboard.create.class', ['classId' => $this->selectedClass->id]);
        } elseif ($this->selectedEvent) {
            // Redirect to event edit route if an event is selected
            return redirect()->route('dashboard.create.class', ['eventId' => $this->selectedEvent->id]);
        } else {
            // Optionally handle the case where no item is selected
            session()->flash('error', 'No item selected for editing.');
        }
    }

    public function deleteItem()
    {
        // Check if the selected item is a class
        if ($this->selectedClass) {
            $this->selectedClass->delete();
            $this->classes = Classe::all(); // Refresh class list
            $this->selectedClass = null;
            session()->flash('message', 'Class deleted successfully.');
        }
        // Check if the selected item is an event
        elseif ($this->selectedEvent) {
            $this->selectedEvent->delete();
            $this->events = Events::all(); // Refresh events list
            $this->selectedEvent = null;
            session()->flash('message', 'Event deleted successfully.');
        } else {
            session()->flash('error', 'No item selected for deletion.');
        }
    }

    public function createQuiz()
    {
        if ($this->selectedClass && $this->selectedClass->class_type == 'recorded') {
            $this->dispatch('close-modal');
            return redirect()->route('quizzes.create', ['class_id' => $this->selectedClass->id]);
        }
    }


    public function render()
    {
        return view('livewire.dashboard.view-enrollments')->layout('components.layouts.dashboard');
    }
}
