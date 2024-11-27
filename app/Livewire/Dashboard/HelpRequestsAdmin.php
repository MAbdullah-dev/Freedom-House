<?php

namespace App\Livewire\Dashboard;

use App\Models\Request;
use Illuminate\Support\Facades\Mail;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class HelpRequestsAdmin extends Component
{
    use LivewireAlert;

    public $helprequests;
    public $selectedRequest;
    public $response;

    protected $rules = [
        'response' => 'required|min:5|max:1000',
    ];

    public function mount()
    {
        $this->loadRequest();
    }

    public function loadRequest()
    {
        // Ensure any request with a null status is marked as "Pending"
        Request::whereNull('status')->update(['status' => 'Pending']);

        $this->helprequests = Request::where('status', 'pending')->with('user')->get();
    }

    public function showDetails($requestId)
    {
        $this->selectedRequest = Request::with('user')->findOrFail($requestId);
        $this->response = ''; // Clear previous response
    }

    public function sendResponse()
    {
        $this->validate(); // Validate the response

        if ($this->selectedRequest) {
            $user = $this->selectedRequest->user;

            // Send email to user
            Mail::raw("Admin Response: {$this->response}", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Help Request Response');
            });

            // Update the request status
            $this->selectedRequest->status = 'approved';
            $this->selectedRequest->save();

            // Refresh the list
            $this->loadRequest();
            $this->dispatch('close-modal');

            // Show success message
            $this->alert('success', 'Response sent successfully.');
        }
    }

    public function rejectRequest()
    {
        $this->validate(); // Validate the response

        if ($this->selectedRequest) {
            $user = $this->selectedRequest->user;

            // Send email to user
            Mail::raw("Your help request has been rejected. Reason: {$this->response}", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Help Request Rejected');
            });

            // Update the request status
            $this->selectedRequest->status = 'rejected';
            $this->selectedRequest->save();

            // Refresh the list
            $this->loadRequest();
            $this->dispatch('close-modal');

            // Show success message
            $this->alert('success', 'Request rejected successfully.');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.help-requests-admin')->layout('components.layouts.dashboard');
    }
}
