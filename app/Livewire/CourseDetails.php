<?php

namespace App\Livewire;

use App\Models\Classe;
use App\Models\Course;
use App\Models\Booking;
use App\Models\Notification;
use App\Services\MicrosoftGraphService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Stripe\Stripe;
use Stripe\Checkout\Session;

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
    public $slots;

    public function mount($id = null)
    {
        if ($id) {
            $this->courseId = $id;
            $this->loadCourseData();
            $this->loadClasses();
        }
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
            $this->slots = $class->capacity;
        }
    }
    private function loadCourseData()
    {
        $course = Course::findOrFail($this->courseId);

        $this->courseTitle = $course->title;
        $this->courseDescription = $course->description;
        $this->learningOutcomes = $course->learning_outcomes;
        $this->targetAudience = $course->target_audience;
        $this->requirements = $course->requirements;
    }

    public function loadClasses()
    {
        Log::info("Filters - Keyword: $this->searchKeyword, Location: $this->selectedLocation, Date: $this->selectedDate");

        $query = Classe::where('course_id', $this->courseId)->where('visibility', true);

        if ($this->searchKeyword) {
            $query->where('title', 'like', '%' . $this->searchKeyword . '%');
        }

        if ($this->selectedLocation) {
            $query->where('class_type', $this->selectedLocation);
        }

        if ($this->selectedDate) {
            $query->whereDate('class_date', Carbon::parse($this->selectedDate)->format('Y-m-d'));
        }

        Log::info('Query: ' . $query->toSql());
        Log::info('Bindings: ' . json_encode($query->getBindings()));

        $this->classes = $query->get();

        Log::info($query->toSql());
        Log::info($query->getBindings());


        Log::info('Classes loaded: ' . $this->classes->count());
    }


    public function updated($propertyName)
    {
        Log::info("Updated property: $propertyName, Value: " . $this->{$propertyName});

        if (in_array($propertyName, ['selectedLocation', 'selectedDate', 'searchKeyword'])) {
            $this->loadClasses();
        }
    }

    public function enroll($classId, $isPaid, $price)
    {
        $this->selectedClassId = $classId;
        $this->isPaid = $isPaid;
        $this->price = $price;

        if ($isPaid) {
            $this->redirectToStripeCheckout();
        } else {
            $this->enrollFreeClass();
        }
    }

    private function enrollFreeClass()
    {
        if ($this->slots <= 0) {
            $this->alert('error', 'No slots available for this class!');
            return;
        }

        $class = Classe::find($this->selectedClassId);

        if (!$class || !$class->microsoft_event_id) {
            $this->alert('error', 'Class not found or missing Microsoft Event ID!');
            return;
        }

        $user = Auth::user();
        if (!$user->microsoft_id) {
            $this->alert('error', 'User Microsoft ID not found!');
            return;
        }

        $attendees = [
            [
                'email' => $user->email,
                'name' => $user->first_name . ' ' . $user->last_name,
            ]
        ];

        try {
            // Use the user's Microsoft ID from Auth::user()->microsoft_id
            // $organizerId = $class->user->microsoft_id;


            // Add attendee to the event using the organizer's Microsoft ID
            // $this->microsoftGraphService->addDynamicAttendees($class, $attendees, $organizerId);

            // Create a booking record
            Booking::create([
                'class_id' => $this->selectedClassId,
                'course_id' => null,
                'user_id' => $user->id,
                'payment_status' => 'free',
                'order_number' => uniqid('FREE_'),
            ]);

            // Update class capacity
            $class->decrement('capacity');

            $this->alert('success', 'Successfully enrolled in the class!');
        } catch (\Exception $e) {
            Log::error('Failed to enroll user in class: ' . $e->getMessage());
            $this->alert('error', 'Failed to enroll in the class.');
        }
    }



    private function redirectToStripeCheckout()
    {
        if ($this->slots <= 0) {
            $this->alert('error', 'No slot available for this class!');
            return;
        }


        session()->put('stripe_payment_amount', $this->price);
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // Create a new Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Class Enrollment', // Name of the product
                        ],
                        'unit_amount' => $this->price * 100, // Convert price to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}&class_id=' . $this->selectedClassId . '&course_id=' . $this->courseId,
                'cancel_url' => route('payment.cancel') . '?class_id=' . $this->selectedClassId,
            ]);

            // Redirect the user to the Stripe Checkout URL
            return redirect($session->url);
        } catch (\Exception $e) {
            // Handle any errors during session creation
            session()->flash('error', 'Unable to create Stripe Checkout session: ' . $e->getMessage());
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
