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
use Stripe\Stripe;
use Jantinnerezo\LivewireAlert\LivewireAlert;




class EventCalendar extends Component
{
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
                        'start_date' => Carbon::parse($calendarEvent->event ? $calendarEvent->event->booking_start_date : $calendarEvent->class->start_date)->toIso8601String(),
                        'end_date' => Carbon::parse($calendarEvent->event ? $calendarEvent->event->booking_end_date : $calendarEvent->class->end_date)->toIso8601String(),
                        'description' => $calendarEvent->event ? $calendarEvent->event->description : $calendarEvent->class->description,
                        'spotsLeft' => $calendarEvent->event ? $calendarEvent->event->capacity : $calendarEvent->class->capacity,
                        'class_time' => $calendarEvent->event ? $calendarEvent->event->class_time : $calendarEvent->class->class_time,
                        'type' => 'event',
                        'created_by' => optional($calendarEvent->user)->first_name,
                    ];
                });
            $this->events = $events;
        } else {
            $this->events = [];
        }

        $this->eventCards = Events::with('users')->get();


        $this->dispatch('eventsLoaded', $this->events);
        $this->dispatch('eventCardsLoaded', $this->eventCards);
    }


    public function selectEvent($event, $type)
    {
        $this->selectedEvent = $event;
        // dd($this->selectedEvent);
        $this->modalType = $type;

        if ($type === 'eventCard') {
            $this->dispatch('eventCardSelected', $this->selectedEvent);
        } elseif ($type === 'calendarEvent') {
            $this->dispatch('calendarEventSelected', $this->selectedEvent);
        }

        $this->selectedEventId = $event['id'];
        $this->isPaid = $event['is_paid'];
        $this->price = $event['price'];
        // dd($this->price);

        $this->slots = $event['capacity'];
        $this->eventCards = Events::with('users')->get();

        Log::info('Selected event is_paid value: ' . $this->isPaid);
    }


    public function enroll()
    {

        Log::info("Enroll button clicked!");
        // dd($this->isPaid);
        if ($this->isPaid == 'paid') {
            $this->redirectToStripeCheckout();
            // dd("go to stripe booking");
        } else {
            $this->enrollFreeEvent();
            // dd("go to free booking");
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
            // @dd('gg');

            $session = Session::create([
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
    }


    public function render()
    {
        return view('livewire.event-calendar');
    }
}
