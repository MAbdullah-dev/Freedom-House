<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\Events;
use App\Models\Notification;
use App\Services\CheckoutSessionFactory;
use App\Services\FreeEnrollment;
use App\Services\MicrosoftGraphService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use App\Livewire\Concerns\LivewireAlert;

class EventCalendar extends Component
{

    protected $microsoftGraphService;

    public function __construct()
    {
        $this->microsoftGraphService = app(MicrosoftGraphService::class);
    }

    use LivewireAlert;

    public $modalType;
    public $events = [];
    public $classes = [];
    public $selectedEvent;
    public $eventCards;
    public $stripeKey;
    public $selectedEventId;
    public $isPaid;
    public $price;
    public $slots;

    public function mount()
    {
        $this->stripeKey = env('STRIPE_KEY');

        $user = Auth::user();

        $this->eventCards = Events::with('users')->get();

        if ($user) {
            $userTimezone = $user->timezone;
            $events = CalendarEvent::with(['event', 'class', 'user'])
                ->where('user_id', $user->id)
                ->orWhere(function ($query) use ($user) {
                    $query->whereNotNull('event_id')
                        ->whereHas('event', function ($subQuery) use ($user) {
                            $subQuery->where('user_id', $user->id);
                        });
                })
                ->get()
                ->map(function ($calendarEvent) use ($userTimezone) {
                    $isEvent = !is_null($calendarEvent->event_id);
                    $source = $isEvent ? $calendarEvent->event : $calendarEvent->class;


                    return [
                        'id' => $calendarEvent->id,
                        'title' => $source->title,
                        'date' => Carbon::parse($source->class_date, 'UTC')->setTimezone($userTimezone)->toIso8601String(),
                        'start_date' => Carbon::parse($source->booking_start_date ?? $source->start_date, 'UTC')->setTimezone($userTimezone)->toIso8601String(),
                        'end_date' => Carbon::parse($source->booking_end_date ?? $source->end_date, 'UTC')->setTimezone($userTimezone)->toIso8601String(),
                        'description' => $source->description,
                        'spotsLeft' => $source->capacity,
                        'class_time' => Carbon::parse($source->class_time, 'UTC')->setTimezone($userTimezone)->toTimeString(),
                        'onsite' => $source->onsite_address,
                        'online' => $source->teams_link,
                        'type' => $isEvent ? 'event' : 'class',
                        'created_by' => optional($calendarEvent->user)->first_name,
                    ];
                });

            $this->events = $events;
        } else {
            $this->events = [];  // No user-specific events for guest users
        }

        // Dispatch data to frontend
        $this->dispatch('eventsLoaded', $this->events);
        $this->dispatch('eventCardsLoaded', $this->eventCards);
    }

    public function selectEvent($event, $type)
    {
        $this->selectedEvent = null;

        $user = Auth::user();
        if ($user) {
            $userTimezone = $user->timezone;
            $event['date'] = Carbon::parse($event['class_date'], 'UTC')->setTimezone($userTimezone)->toIso8601String();
            $event['class_time'] = Carbon::parse($event['class_time'], 'UTC')->setTimezone($userTimezone)->toTimeString();
            $event['booking_start_date'] = Carbon::parse($event['booking_start_date'], 'UTC')->setTimezone($userTimezone)->toDateString();
            $event['booking_end_date'] = Carbon::parse($event['booking_end_date'], 'UTC')->setTimezone($userTimezone)->toDateString();
            $event['timezone'] = $userTimezone;
        } else {
            $event['timezone'] = 'UTC';
        }

        $this->selectedEvent = $event;
        $this->modalType = $type;

        if ($type === 'eventCard') {
            $this->dispatch('eventCardSelected', $this->selectedEvent);
        } elseif ($type === 'calendarEvent') {
            $this->dispatch('calendarEventSelected', $this->selectedEvent);
        }

        $this->selectedEventId = $event['id'];
        $this->isPaid = $event['is_paid'];
        $this->price = $event['price'];
        $this->slots = $event['capacity'];
        $this->eventCards = Events::with('users')->get();
    }

    public function enroll()
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $outcome = app(FreeEnrollment::class)->enrollEvent($user, $this->selectedEventId);

        if ($outcome->status === FreeEnrollment::PAID) {
            return $this->redirectToStripeCheckout($outcome->item);
        }

        if ($outcome->status === FreeEnrollment::ENROLLED) {
            try {
                $this->completeFreeEventEnrollment($outcome->item, $user);
            } catch (\Throwable $e) {
                app(FreeEnrollment::class)->release($outcome->booking);
                Log::error('Failed to enroll user in event: '.$e->getMessage());
                $this->alert('error', 'Failed to enroll in the event.');

                return;
            }

            $this->alert('success', 'Successfully enrolled in the event!');
            $this->dispatch('close-modal');

            return;
        }

        $this->alert('error', match ($outcome->status) {
            FreeEnrollment::FULL => 'No slot available for this event!',
            FreeEnrollment::DUPLICATE => 'You are already enrolled in this event!',
            FreeEnrollment::UNPRICED => 'This event is not available for enrollment.',
            default => 'Event not found!',
        });
    }

    private function completeFreeEventEnrollment(Events $event, $user): void
    {
        if ($event->microsoft_event_id) {
            $attendees = [
                [
                    'email' => $user->email,
                    'name' => $user->first_name.' '.$user->last_name,
                ],
            ];

            $organizerId = $event->users->microsoft_id ?? null;
            if ($organizerId) {
                $this->microsoftGraphService->addDynamicAttendees($event, $attendees, $organizerId);
            } else {
                throw new \Exception('Organizer Microsoft ID is missing.');
            }
        } elseif ($user->microsoft_account && ! $event->microsoft_event_id) {
            $this->createCalendarEventForUser($event, $user);
        }

        Notification::create([
            'user_id' => $user->id,
            'message' => 'You successfully enrolled in the event: '.$event->title.' 😊.',
            'type' => 'booking',
        ]);
    }

    private function createCalendarEventForUser($event, $user)
    {
        try {
            $attendees = [
                [
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name,
                ]
            ];

            $this->microsoftGraphService->createUserCalendarEvent(
                $event->title,
                $event->description,
                $event->event_date . ' ' . $event->event_time,
                Carbon::parse($event->event_date . ' ' . $event->event_time)->addHour()->toDateTimeString(),
                $event->location,
                $attendees
            );
            Log::info('Calendar event created for user successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating calendar event for user: ' . $e->getMessage());
            throw $e;
        }
    }

    private function redirectToStripeCheckout(Events $event)
    {
        $this->selectedEventId = $event->id;
        $this->price = $event->price;

        try {
            $url = app(CheckoutSessionFactory::class)->forEvent($event, Auth::user());

            return redirect()->to($url);
        } catch (\Throwable $e) {
            Log::error('Unable to create Stripe Checkout session: '.$e->getMessage());
            session()->flash('error', 'Unable to create Stripe Checkout session.');

            return redirect()->back();
        }
    }
    public function render()
    {
        return view('livewire.event-calendar');
    }
}
