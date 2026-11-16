<?php

namespace App\Livewire;

use App\Models\Classe;
use App\Models\Course;
use App\Models\Booking;
use App\Models\Notification;
use App\Services\CheckoutSessionFactory;
use App\Services\FreeEnrollment;
use App\Services\MicrosoftGraphService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Livewire\Concerns\LivewireAlert;
use Livewire\Component;

class CourseDetails extends Component
{

    protected $microsoftGraphService;
    public function __construct()
    {
        $this->microsoftGraphService = app(MicrosoftGraphService::class);
    }


    use LivewireAlert;
    public $courseId;
    public $courseTitle;
    public $courseDescription;
    public $learningOutcomes;
    public $targetAudience;
    public $requirements;
    public $classes;
    public $searchKeyword = '';
    public $selectedLocation = '';
    public $selectedDate = '';

    // Modal data
    public $selectedClassId;
    public $selectedClassTitle;
    public $isPaid;
    public $price;
    public $seatCapacity;
    public $userTimezone;
    public $coursePhoto;
    public $courseVideo;

    public function mount($id = null)
    {
        if ($id) {
            $this->courseId = $id;
            $this->loadCourseData();
            $this->loadClasses();
        }
        $userTimezone = Auth::check() ? Auth::user()->timezone : 'UTC';
        // dd($this->userTimezone);
    }
    public function fetch()
    {
        $this->loadClasses();
    }
    public function prepareEnrollment($classId)
    {
        $class = Classe::find($classId);

        if ($class) {
            $this->selectedClassId = $class->id;
            $this->selectedClassTitle = $class->title;
            $this->isPaid = $class->is_paid === 'paid';
            $this->price = $class->price;
            $this->seatCapacity = $class->capacity;
        }
    }
    private function loadCourseData()
    {
        $course = Course::findOrFail($this->courseId);

        $this->courseTitle = $course->title;
        $this->coursePhoto = $course->thumbnail;
        $this->courseDescription = $course->description;
        $this->learningOutcomes = $course->learning_outcomes;
        $this->targetAudience = $course->target_audience;
        $this->requirements = $course->requirements;
        $this->courseVideo = $course->video_path;
    }

    public function loadClasses()
    {

        $query = Classe::where('course_id', $this->courseId)->where('visibility', true);

        if ($this->searchKeyword) {
            $query->where('title', 'like', '%' . $this->searchKeyword . '%');
        }

        if ($this->selectedLocation) {
            $query->where('class_type', $this->selectedLocation);
        }

        if ($this->selectedDate) {
            $selectedDateUTC = Carbon::parse($this->selectedDate)->utc();
            $query->whereDate('class_date', $selectedDateUTC->format('Y-m-d'));
        }

        $this->classes = $query->get()->map(function ($class) {
            $userTimezone = Auth::check() && Auth::user()->timezone ? Auth::user()->timezone : 'UTC';
            $class->class_date = Carbon::parse($class->class_date . ' ' . $class->class_time, 'UTC')->setTimezone($userTimezone)->toDateString();
            $class->class_time = Carbon::parse($class->class_date . ' ' . $class->class_time, 'UTC')->setTimezone($userTimezone)->toTimeString();
            return $class;
        });
    }


    public function updated($propertyName)
    {
        Log::info("Updated property: $propertyName, Value: " . $this->{$propertyName});

        if (in_array($propertyName, ['selectedLocation', 'selectedDate', 'searchKeyword'])) {
            $this->loadClasses();
        }
    }

    public function enroll($classId, $isPaid = null, $price = null)
    {
        unset($isPaid, $price);

        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $outcome = app(FreeEnrollment::class)->enrollClass($user, $classId);

        if ($outcome->status === FreeEnrollment::PAID) {
            return $this->redirectToStripeCheckout($outcome->item);
        }

        if ($outcome->status === FreeEnrollment::ENROLLED) {
            try {
                $this->completeFreeClassEnrollment($outcome->item, $user);
            } catch (\Throwable $e) {
                app(FreeEnrollment::class)->release($outcome->booking);
                Log::error('Failed to enroll user in class: '.$e->getMessage());
                $this->alert('error', 'Failed to enroll in the class.');

                return;
            }

            $this->dispatch('close-modal');
            $this->alert('success', 'Successfully enrolled in the class!');

            return;
        }

        $this->alert('error', $this->classEnrollmentMessage($outcome->status));
    }

    private function completeFreeClassEnrollment(Classe $class, $user): void
    {
        if ($class->microsoft_event_id) {
            $attendees = [
                [
                    'email' => $user->email,
                    'name' => $user->first_name.' '.$user->last_name,
                ],
            ];

            $organizerId = $class->user->microsoft_id;
            $this->microsoftGraphService->addDynamicAttendees($class, $attendees, $organizerId);
        } elseif ($user->microsoft_account && ! $class->microsoft_event_id) {
            $this->createCalendarEventForUser($class, $user);
        }

        Notification::create([
            'user_id' => $user->id,
            'message' => 'You successfully purchased the event: '.$class->title.' 😊.',
            'type' => 'booking',
        ]);
    }

    private function classEnrollmentMessage(string $status): string
    {
        return match ($status) {
            FreeEnrollment::FULL => 'No slots available for this class!',
            FreeEnrollment::DUPLICATE => 'You are already enrolled in this class!',
            FreeEnrollment::UNPRICED => 'This class is not available for enrollment.',
            default => 'Class not found!',
        };
    }

    private function createCalendarEventForUser($class, $user)
    {
        try {
            $attendees = [
                [
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name,
                ]
            ];

            $this->microsoftGraphService->createUserCalendarEvent(
                $class->title,
                $class->description,
                $class->class_date . ' ' . $class->class_time,
                Carbon::parse($class->class_date . ' ' . $class->class_time)->addHour()->toDateTimeString(),
                $class->onsite_address,
                $attendees
            );
            Log::info('Calendar event created for user successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating calendar event for user: ' . $e->getMessage());
            throw $e;
        }
    }

    private function redirectToStripeCheckout(Classe $class)
    {
        $this->selectedClassId = $class->id;
        $this->price = $class->price;

        try {
            $url = app(CheckoutSessionFactory::class)->forClass($class, Auth::user());

            return redirect()->to($url);
        } catch (\Throwable $e) {
            Log::error('Unable to create Stripe Checkout session: '.$e->getMessage());
            session()->flash('error', 'Unable to create Stripe Checkout session.');

            return redirect()->back();
        }
    }
    public function isEnrolled($classId)
    {
        return Booking::where('class_id', $classId)
            ->where('user_id', Auth::id())
            ->exists();
    }
    public function render()
    {
        $classes = Classe::all();
        return view('livewire.course-details');
    }
}
