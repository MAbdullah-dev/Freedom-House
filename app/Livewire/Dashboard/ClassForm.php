<?php

namespace App\Livewire\Dashboard;

use App\Services\MicrosoftGraphService;
use App\Models\CalendarEvent;
use Livewire\Component;
use App\Models\Course;
use App\Models\Classe;
use App\Models\Events;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;

use function Laravel\Prompts\alert;

class ClassForm extends Component
{
    use LivewireAlert;

    protected $microsoftGraphService;
    public function __construct()
    {
        $this->microsoftGraphService = app(MicrosoftGraphService::class);
    }

    public $type = 'class';
    public $course_id, $title, $description, $class_type = 'onsite', $teams_link, $onsite_address, $is_paid = 'free';
    public $price = 0, $capacity, $visibility = 1, $booking_start_date, $booking_end_date, $date_range, $class_date, $class_time;
    public $courses = [];
    public $classId;
    public $eventId;
    public $event_date;
    public $event_time;

    protected $rules = [
        'type' => 'required|in:class,event',
        'course_id' => 'nullable|exists:courses,id',
        'title' => 'required|string|min:5|max:30|unique:classes,title',
        'description' => 'required|string|min:10|max:100',
        'class_type' => 'required_if:type,class|in:onsite,virtual',
        'teams_link' => 'nullable|url',
        'onsite_address' => 'nullable|required_if:class_type,onsite|string|max:255',
        'is_paid' => 'required|in:free,paid',
        'price' => 'nullable|required_if:is_paid,paid|numeric',
        'capacity' => 'required|integer|min:1',
        'class_date' => 'required|date|date_format:Y-m-d',
        'class_time' => 'required',
        'date_range' => 'required',
        'booking_start_date' => 'required|date',
        'booking_end_date' => 'required|date',
    ];

    protected $messages = [
        'title.required' => 'Please provide a title for the class/event.',
        'booking_start_date.after_or_equal' => 'The booking start date must be today or later.',
        'booking_end_date.after' => 'The booking end date must be after the start date.',
    ];


    public function mount($classId = null, $eventId = null)
    {
        dd($eventId);
        if ($classId) {
            $this->classId = $classId;
            $this->type = 'class'; // Set type to class
            $this->loadClassData();
        } elseif ($eventId) {
            $this->eventId = $eventId;
            $this->type = 'event'; // Set type to event
            $this->loadEventData();
        }
        $this->loadcources();
    }

    public function loadclassData()
    {
        $class = Classe::find($this->classId);
        dd($class);
        if ($class) {
            $this->type = 'class';
            $this->course_id = $class->course_id;
            $this->title = $class->title;
            $this->description = $class->description;
            $this->class_type = $class->class_type;
            $this->teams_link = $class->teams_link;
            $this->onsite_address = $class->onsite_address;
            $this->is_paid = $class->is_paid;
            $this->price = $class->price;
            $this->capacity = $class->capacity;
            $this->visibility = $class->visibility;
            $this->booking_start_date = $class->booking_start_date;
            $this->booking_end_date = $class->booking_end_date;
            $this->date_range = $this->booking_start_date . ' to ' . $this->booking_end_date;
            // $this->recorded_video_url = $class->recorded_video_url;
            $this->class_date = $class->class_date;
            $this->class_time = $class->class_time;
        }
    }
    public function loadEventData()
    {
        $event = Events::find($this->eventId);
        if ($event) {
            $this->course_id = $event->course_id;
            $this->title = $event->title;
            $this->description = $event->description;
            $this->class_type = $event->event_type; // Assuming there's an event type field
            $this->teams_link = $event->teams_link;
            $this->onsite_address = $event->onsite_address;
            $this->is_paid = $event->is_paid;
            $this->price = $event->price;
            $this->capacity = $event->capacity;
            $this->visibility = $event->visibility;
            $this->booking_start_date = $event->booking_start_date;
            $this->booking_end_date = $event->booking_end_date;
            $this->date_range = $this->booking_start_date . ' to ' . $this->booking_end_date;
            $this->event_date = $event->event_date; // Assuming there's an event_date field
            $this->event_time = $event->event_time; // Assuming there's an event_time field
        }
    }

    public function updateClass()
    {
        $this->validate([
            'booking_start_date' => 'required|date|',
            // Add other fields as necessary
        ]);
        $this->validate();

        $class = Classe::findOrFail($this->classId);
        $class->update([
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description,
            'class_type' => $this->class_type,
            'teams_link' => $this->teams_link,
            'onsite_address' => $this->onsite_address,
            'is_paid' => $this->is_paid,
            'price' => $this->price,
            'capacity' => $this->capacity,
            'visibility' => $this->visibility,
            'class_date' => $this->class_date,
            'class_time' => $this->class_time,
            'booking_start_date' => $this->booking_start_date,
            'booking_end_date' => $this->booking_end_date,
            // 'recorded_video_url' => $this->recorded_video_url,
        ]);

        $this->alert('success', 'Class updated successfully!');
        $this->redirect(route("dashboard.enrollments"));
    }
    public function loadcources()
    {
        $this->courses = Course::with(['user', 'tutors'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->where('user_id', Auth::id())
            ->get();
    }
    public function updatedDateRange($value)
    {
        // Parse the date range string into start_date and end_date
        $dates = explode(' to ', $value);
        if (count($dates) === 2) {
            $this->booking_start_date = $dates[0];
            $this->booking_end_date = $dates[1];
        }
    }

    public function dispatchClassTypeChanged()
    {
        switch ($this->class_type) {
            case 'onsite':
                $this->reset(['teams_link']);
                break;
            case 'virtual':
                $this->reset(['onsite_address']);
                break;
                // case 'recorded':
                //     $this->reset(['teams_link', 'onsite_address']);
                //     break;
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

        if ($this->class_type == 'virtual' && !Auth::user()->microsoft_account) {
            $this->alert('error', 'You must have a Microsoft account to create a virtual class.');
            return;
        }

        if ($this->type === 'class') {
            $this->createClass();
            $this->loadcources();
        } else {
            $this->createEvent();
            $this->loadcources();
        }
        // if ($this->classId) {
        // }


        $this->reset();
    }

    private function createClass()
    {

        $userTimezone = Auth::user()->timezone;
        $classDateTime = Carbon::parse($this->class_date . ' ' . $this->class_time, $userTimezone);
        $utcClassDateTime = $classDateTime->setTimezone('UTC');

        $class = Classe::create([
            'title' => $this->title,
            'description' => $this->description,
            'course_id' => $this->course_id,
            'user_id' => Auth::id(),
            'class_type' => $this->class_type,
            'teams_link' => $this->teams_link,
            'onsite_address' => $this->onsite_address,
            // 'recorded_video_url' => $this->recorded_video_url,
            'is_paid' => $this->is_paid,
            'price' => $this->price,
            'capacity' => $this->capacity,
            'visibility' => $this->visibility,
            'class_date' => $utcClassDateTime->toDateString(),
            'class_time' => $utcClassDateTime->toTimeString(),
            'booking_start_date' => $this->booking_start_date,
            'booking_end_date' => $this->booking_end_date,
        ]);

        // dd($class);

        CalendarEvent::create([
            'user_id' => Auth::id(),
            'class_id' => $class->id,
        ]);

        if (Auth::user()->microsoft_account == true) {
            $attendees = [
                ['email' => Auth::user()->email, 'name' => Auth::user()->first_name . ' ' . Auth::user()->last_name],
            ];

            $graphService = new MicrosoftGraphService();

            $success = false;

            if ($class->class_type == 'virtual') {
                $success = $graphService->createCalendarEventWithTeamsMeeting($class, $attendees) ? true : false;
            } else {
                $success = $graphService->createCalendarEvent($class) ? true : false;
            }

            if ($success) {
                $this->alert('success', 'Class created successfully!');
                $this->redirect(route("dashboard.enrollments"));
            } else {
                $this->alert('error', 'Failed to create class.');
            }
        } else {
            alert('success', 'Class created successfully!');
            $this->redirect(route("dashboard.enrollments"));
        }
    }

    private function createEvent()
    {

        $userTimezone = Auth::user()->timezone;
        $classDateTime = Carbon::parse($this->class_date . ' ' . $this->class_time, $userTimezone);
        $utcClassDateTime = $classDateTime->setTimezone('UTC');
        $bookingStartDateTime = Carbon::parse($this->booking_start_date, $userTimezone)->setTimezone('UTC');
        $bookingEndDateTime = Carbon::parse($this->booking_end_date, $userTimezone)->setTimezone('UTC');


        $event = Events::create([
            'title' => $this->title,
            'description' => $this->description,
            'user_id' => Auth::id(),
            'class_type' => $this->class_type,
            'teams_link' => $this->teams_link,
            'onsite_address' => $this->onsite_address,
            'is_paid' => $this->is_paid,
            'price' => $this->price,
            'capacity' => $this->capacity,
            'class_date' => $utcClassDateTime->toDateString(),
            'class_time' => $utcClassDateTime->toTimeString(),
            'booking_start_date' => $this->booking_start_date,
            'booking_end_date' => $this->booking_end_date,
        ]);

        // dd($event);

        CalendarEvent::create([
            'user_id' => Auth::id(),
            'event_id' => $event->id,
        ]);
        if (Auth::user()->microsoft_account == true) {
            $attendees = [
                ['email' => Auth::user()->email, 'name' => Auth::user()->first_name . ' ' . Auth::user()->last_name],
            ];

            $graphService = new MicrosoftGraphService();

            $success = false;

            if ($event->class_type == 'virtual') {
                $success = $graphService->createCalendarEventWithTeamsMeeting($event, $attendees) ? true : false;
            } else {
                $success = $graphService->createCalendarEvent($event) ? true : false;
            }

            if ($success) {
                $this->alert('success', 'Event created successfully!');
                $this->redirect(route("calendar"));
            } else {
                $this->alert('error', 'Failed to create class.');
            }
        } else {
            alert('success', 'Event created successfully!');
            $this->redirect(route("dashboard.enrollments"));
        }
    }

    public function render()
    {
        return view('livewire.dashboard.class-form')->layout('components.layouts.createCourse');
    }
}
