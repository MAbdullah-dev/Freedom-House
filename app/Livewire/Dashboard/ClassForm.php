<?php

namespace App\Livewire\Dashboard;

use App\Models\CalendarEvent;
use Livewire\Component;
use App\Models\Course;
use App\Models\Classe;
use App\Models\Events;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ClassForm extends Component
{
    use LivewireAlert;

    public $type = 'class';
    public $course_id, $title, $description, $class_type = 'onsite', $teams_link, $onsite_address, $is_paid = 'free';
    public $price = 0, $capacity, $visibility = 1, $start_date, $end_date, $recorded_video_url;
    public $courses = [];

    protected $rules = [
        'type' => 'required|in:class,event',
        'course_id' => 'nullable|exists:courses,id',
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'class_type' => 'required_if:type,class|in:onsite,virtual,recorded',
        'teams_link' => 'nullable|url',
        'onsite_address' => 'nullable|required_if:class_type,onsite|string|max:255',
        'recorded_video_url' => 'nullable|required_if:class_type,recorded|url',
        'is_paid' => 'required|in:free,paid',
        'price' => 'nullable|required_if:is_paid,paid|numeric',
        'capacity' => 'required|integer|min:1',
        'start_date' => 'required|date|after_or_equal:today',
        'end_date' => 'required|date|after:start_date',
    ];

    public function mount()
    {
        $this->courses = Course::with(['user', 'tutors'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();
    }

    public function dispatchClassTypeChanged()
    {
        if ($this->class_type === 'onsite') {
            $this->reset(['teams_link', 'recorded_video_url']);
        } elseif ($this->class_type === 'virtual') {
            $this->reset(['onsite_address', 'recorded_video_url']);
        } elseif ($this->class_type === 'recorded') {
            $this->reset(['teams_link', 'onsite_address']);
        }
    }

    public function dispatchTypeChanged()
    {
        $this->dispatch('refreshComponent');
    }

    public function dispatchIsPaidChanged()
    {
        $this->dispatch('refreshComponent');
    }

    public function submit()
    {
        $this->validate();

        if ($this->type === 'class') {
            $this->createClass();
        } else {
            $this->createEvent();
        }

        $this->reset();
    }

    private function createClass()
{
    $class = Classe::create([
        'title' => $this->title,
        'description' => $this->description,
        'course_id' => $this->course_id,
        'user_id' => Auth::id(),
        'class_type' => $this->class_type,
        'teams_link' => $this->teams_link,
        'onsite_address' => $this->onsite_address,
        'recorded_video_url' => $this->recorded_video_url,
        'is_paid' => $this->is_paid,
        'price' => $this->price,
        'capacity' => $this->capacity,
        'start_date' => $this->start_date,
        'end_date' => $this->end_date,
        'visibility' => $this->visibility,
    ]);


    CalendarEvent::create([
        'user_id' => Auth::id(),
        'class_id' => $class->id,
    ]);

    $this->dispatch('closeModal');
    $this->alert('success', 'Class created successfully!');
}

private function createEvent()
{
    $event = Events::create([
        'title' => $this->title,
        'description' => $this->description,
        'user_id' => Auth::id(),
        'class_type' => $this->class_type,
        'teams_link' => $this->teams_link,
        'onsite_address' => $this->onsite_address,
        'recorded_video_url' => $this->recorded_video_url,
        'is_paid' => $this->is_paid,
        'price' => $this->price,
        'capacity' => $this->capacity,
        'start_date' => $this->start_date,
        'end_date' => $this->end_date,
    ]);

    CalendarEvent::create([
        'user_id' => Auth::id(),
        'event_id' => $event->id,
    ]);

    $this->dispatch('closeModal');
    $this->alert('success', 'Event created successfully!');
}

    public function render()
    {
        return view('livewire.dashboard.class-form');
    }
}
