<?php
namespace App\Livewire;
use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\Events;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
<<<<<<< HEAD
=======
use Stripe\Stripe;
use Jantinnerezo\LivewireAlert\LivewireAlert;

>>>>>>> 54e30ea75e34b1254788264351058c4cbe123a5a
class EventCalendar extends Component
{
    use LivewireAlert;

    public $modalType;
    public $events = [];
    public $classes = [];
    public $selectedEvent;
    public $eventCards;
<<<<<<< HEAD
=======
    public $stripeKey;
    public $selectedEventId;
    public $isPaid;
    public $price;
    public $slots;

>>>>>>> 54e30ea75e34b1254788264351058c4cbe123a5a
    public function mount()
    {
        $this->stripeKey = env('STRIPE_KEY');

        $user = Auth::user();
<<<<<<< HEAD
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
=======

        if ($user) {
            $events = CalendarEvent::with(['event', 'class', 'user'])
                ->where('user_id', $user->id)
                ->orWhere(function ($query) use ($user) {
                    $query->whereNotNull('event_id')
                        ->whereHas('event', function ($subQuery) use ($user) {
                            $subQuery->where('user_id', $user->id);
                        });
                })
                ->get()
                ->map(function ($calendarEvent) {
                    $isEvent = !is_null($calendarEvent->event_id);
                    $source = $isEvent ? $calendarEvent->event : $calendarEvent->class;

                    return [
                        'id' => $calendarEvent->id,
                        'title' => $source->title,
                        'date' => Carbon::parse($source->class_date)->toIso8601String(),
                        'start_date' => Carbon::parse($source->booking_start_date ?? $source->start_date)->toIso8601String(),
                        'end_date' => Carbon::parse($source->booking_end_date ?? $source->end_date)->toIso8601String(),
                        'description' => $source->description,
                        'spotsLeft' => $source->capacity,
                        'class_time' => $source->class_time,
                        'type' => $isEvent ? 'event' : 'class',
                        'created_by' => optional($calendarEvent->user)->first_name,
                    ];
                });

>>>>>>> 54e30ea75e34b1254788264351058c4cbe123a5a
            $this->events = $events;
        } else {
            $this->events = [];
        }
<<<<<<< HEAD
        $this->eventCards = Events::all();
=======


        $this->eventCards = Events::with('users')->get();

>>>>>>> 54e30ea75e34b1254788264351058c4cbe123a5a
        $this->dispatch('eventsLoaded', $this->events);
        $this->dispatch('eventCardsLoaded', $this->eventCards);
    }
    public function selectEvent($event, $type)
    {
        $this->selectedEvent = $event;
        $this->modalType = $type;
<<<<<<< HEAD
        $this->dispatch('openModal');
=======

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

        Log::info('Selected event is_paid value: ' . $this->isPaid);
    }

    public function enroll()
    {
        Log::info("Enroll button clicked!");
        if ($this->isPaid == 'paid') {
            $this->redirectToStripeCheckout();
        } else {
            $this->enrollFreeEvent();
        }
    }

    private function enrollFreeEvent()
    {
        if ($this->slots <= 0) {
            $this->alert('error', 'No slot available for this event!');
            return;
        }

        Booking::create([
            'event_id' => $this->selectedEventId,
            'user_id' => Auth::id(),
            'payment_status' => 'free',
            'order_number' => uniqid('FREE_'),
        ]);

        CalendarEvent::create([
            'event_id' => $this->selectedEventId,
            'user_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = Events::find($this->selectedEventId);
        if ($event && $event->capacity > 0) {
            $event->decrement('capacity');
        } else {
            $this->alert('error', 'No slots available for this event!');
            return;
        }

        $this->alert('success', 'You have successfully enrolled in the free event!');
        $this->dispatch('close-modal');
    }

    private function redirectToStripeCheckout()
    {
        if ($this->slots <= 0) {
            $this->alert('error', 'No slot available for this event!');
            return;
        }

        session()->put('stripe_payment_amount', $this->price);
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Event Enrollment',
                        ],
                        'unit_amount' => $this->price * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}&event_id=' . $this->selectedEventId,
                'cancel_url' => route('payment.cancel') . '?event_id=' . $this->selectedEventId,
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            session()->flash('error', 'Unable to create Stripe Checkout session: ' . $e->getMessage());
            return redirect()->back();
        }
>>>>>>> 54e30ea75e34b1254788264351058c4cbe123a5a
    }
    public function render()
    {
        return view('livewire.event-calendar');
    }
}
