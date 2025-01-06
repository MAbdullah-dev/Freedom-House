<?php

namespace App\Livewire\Dashboard;

use App\Models\Booking;
use App\Models\Classe;
use App\Models\Events;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ViewEnrollments extends Component
{
    public $dateRange;
    public $classes = [];
    public $selectedClass;
    public $selectedEvent;
    public $events = [];
    public $items = [];
    public $enrollments;

    use LivewireAlert;

    public function mount()
    {
        $classes = Classe::where('user_id', Auth::id())->get();
        $events = Events::where('user_id', Auth::id())->get();

        $this->items = $classes->concat($events)->sortBy(function ($item) {
            return $item->booking_start_date;
        });

        $userTimezone = Auth::user()->timezone;

        $this->items = $this->items->map(function ($item) use ($userTimezone) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item instanceof \App\Models\Classe ? 'Class' : 'Event',
                'date' => $item->class_date
                    ? \Carbon\Carbon::parse($item->class_date, 'UTC')->setTimezone($userTimezone)->format('F j, Y')
                    : 'Not Set',
                'time' => $item->class_time
                    ? \Carbon\Carbon::parse($item->class_time, 'UTC')->setTimezone($userTimezone)->format('h:i A')
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
            $dates = explode(' to ', $this->dateRange);
            if (count($dates) !== 2) {
                session()->flash('error', 'Invalid date range format.');
                return;
            }

            $startDate = Carbon::parse($dates[0])->startOfDay();
            $endDate = Carbon::parse($dates[1])->endOfDay();

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

            $this->items = $classes->concat($events)->sortBy(function ($item) {
                return $item->booking_start_date;
            });
            $this->items = $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'type' => $item instanceof \App\Models\Classe ? 'Class' : 'Event',
                    'date' => $item instanceof \App\Models\Classe ? $item->class_date : $item->class_date,
                    'time' => $item instanceof \App\Models\Classe ? $item->class_time : $item->class_time,
                    'price' => $item->price ? '$' . number_format($item->price, 2) : 'Free',
                    'model_type' => $item instanceof \App\Models\Classe ? 'class' : 'event',
                ];
            });
        }
    }
    public function enrollments()
    {
        $userTimezone = Auth::user()->timezone;

        $this->enrollments = Booking::where('user_id', Auth::id())
            ->where(function ($query) {
                $query->whereNotNull('class_id')
                    ->orWhereNotNull('event_id');
            })
            ->with(['class', 'event'])
            ->get()
            ->map(function ($enrollment) use ($userTimezone) {
                $enrollment->order_number = $enrollment->order_number;
                $enrollment->title = $enrollment->class_id
                    ? $enrollment->class->title
                    : ($enrollment->event_id ? $enrollment->event->title : 'No Enrollment');
                $enrollment->payment_status = $enrollment->payment_status === 'paid' ? 'Paid' : 'Free';
                $enrollment->time = $enrollment->class_id
                    ? \Carbon\Carbon::parse($enrollment->class->class_time, 'UTC')->setTimezone($userTimezone)->format('h:i A')
                    : ($enrollment->event_id
                        ? \Carbon\Carbon::parse($enrollment->event->class_time, 'UTC')->setTimezone($userTimezone)->format('h:i A')
                        : 'N/A');
                $enrollment->date = $enrollment->class_id
                    ? \Carbon\Carbon::parse($enrollment->class->class_date, 'UTC')->setTimezone($userTimezone)->format('F j, Y')
                    : ($enrollment->event_id
                        ? \Carbon\Carbon::parse($enrollment->event->class_date, 'UTC')->setTimezone($userTimezone)->format('F j, Y')
                        : 'N/A');
                return $enrollment;
            });
    }


    public function selectItem($itemId, $modelType)
    {
        if ($modelType === 'class') {
            $this->selectedClass = Classe::findOrFail($itemId);
            $this->selectedEvent = null;
        } elseif ($modelType === 'event') {
            $this->selectedEvent = Events::findOrFail($itemId);
            $this->selectedClass = null;
        } else {
            session()->flash('error', 'Invalid model type selected.');
        }
    }
    public function editItem()
    {

        if ($this->selectedClass) {

            return redirect()->route('dashboard.create.class', ['classId' => $this->selectedClass->id]);
        } elseif ($this->selectedEvent) {

            return redirect()->route('dashboard.create.event', ['eventId' => $this->selectedEvent->id]);
        } else {

            session()->flash('error', 'No item selected for editing.');
        }
        $this->dispatch('close-modal');
    }

    public function deleteItem()
    {

        if ($this->selectedClass) {
            $this->selectedClass->delete();
            $this->classes = Classe::all();
            $this->selectedClass = null;
            $this->alert('success', 'Class deleted successfully.');
            $this->dispatch('close-modal');
        } elseif ($this->selectedEvent) {
            $this->selectedEvent->delete();
            $this->events = Events::all();
            $this->selectedEvent = null;
            $this->alert('success', 'Event deleted successfully.');
            $this->dispatch('close-modal');
        } else {
            $this->alert('error', 'No item selected for deletion.');
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
