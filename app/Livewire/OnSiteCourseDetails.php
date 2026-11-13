<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Services\CheckoutSessionFactory;
use App\Services\ContentAccess;
use App\Services\FreeEnrollment;
use App\Models\Lecture; // Make sure to import the Lecture model
use App\Models\Course;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class OnSiteCourseDetails extends Component
{
    public $courseId;
    public $courseTitle;
    public $courseDescription;
    public $learningOutcomes;
    public $targetAudience;
    public $requirements;
    public $price;
    public $isEnrolled;
    public $isPaid;
    public $course;

    public $lectures = [];
    public $showFullDescription = false;

    public function mount($id = null)
    {
        if ($id) {
            $this->courseId = $id;
            $this->loadCourseData();
            $this->loadLectures();
            $this->checkEnrollment();
        }
        $this->course = Course::query()
            ->with([
                'lectures' => fn ($query) => $query->select('id', 'course_id', 'title', 'order', 'video_duration'),
                'bookings' => fn ($query) => $query->select('id', 'course_id'),
                'reviews',
            ])
            ->findOrFail($this->courseId);
    }

    public function loadCourseData()
    {
        $course = Course::findOrFail($this->courseId);
        // dd($course);

        $this->courseTitle = $course->title;
        $this->courseDescription = $course->description;
        $this->learningOutcomes = $course->learning_outcomes;
        $this->targetAudience = $course->target_audience;
        $this->requirements = $course->requirements;
        $this->price = (float) $course->price;
        $this->isPaid = $this->price > 0;
    }

    public function loadLectures()
    {
        $this->lectures = Lecture::query()
            ->where('course_id', $this->courseId)
            ->orderBy('order')
            ->get(['id', 'course_id', 'title', 'order', 'video_duration']);
    }

    public function enrollCourse()
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $outcome = app(FreeEnrollment::class)->enrollCourse($user, $this->courseId);

        if ($outcome->status === FreeEnrollment::PAID) {
            return $this->redirectToStripeCheckout($outcome->item);
        }

        if ($outcome->status === FreeEnrollment::ENROLLED) {
            Notification::create([
                'user_id' => $user->id,
                'message' => 'You successfully Enrolled in the free course: '.$outcome->item->title.' 😊.',
                'type' => 'booking',
            ]);
            session()->flash('success', 'You have successfully enrolled in the free course!');

            return redirect()->to(route('onsite.course.details', ['id' => $outcome->item->id]));
        }

        session()->flash('error', match ($outcome->status) {
            FreeEnrollment::DUPLICATE => 'You are already enrolled in this course.',
            FreeEnrollment::UNPRICED => 'This course is not available for enrollment.',
            default => 'Course not found.',
        });
    }

    private function redirectToStripeCheckout(Course $course)
    {
        $this->courseId = $course->id;
        $this->price = $course->price;
        $this->courseTitle = $course->title;

        try {
            $url = app(CheckoutSessionFactory::class)->forCourse($course, Auth::user());

            return redirect()->to($url);
        } catch (\Throwable $e) {
            Log::error('Unable to create Stripe Checkout session: '.$e->getMessage());
            session()->flash('error', 'There was an error processing your payment.');

            return redirect()->back();
        }
    }
    public function checkEnrollment()
    {
        $this->isEnrolled = Auth::check() && Booking::query()
            ->where('user_id', Auth::id())
            ->where('course_id', $this->courseId)
            ->whereIn('payment_status', ContentAccess::GRANTED_STATUSES)
            ->exists();
    }


    public function toggleDescription()
    {
        $this->showFullDescription = !$this->showFullDescription;
    }

    public function render()
    {
        return view('livewire.on-site-course-details');
    }
}
