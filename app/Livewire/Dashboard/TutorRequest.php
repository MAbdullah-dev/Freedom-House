<?php

namespace App\Livewire\Dashboard;

use App\Models\Notification;
use App\Models\Tutor;
use Illuminate\Support\Facades\Mail;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TutorRequest extends Component
{
    public $tutors;
    public $rejectionReason = ''; // For the rejection reason
    public $approvalMessage = ''; // For the approval message
    public $tutorId = null; // Store tutor ID for rejection or approval

    use LivewireAlert;

    public function mount()
    {
        $this->loadTutors();
    }

    public function loadTutors()
    {
        $this->tutors = Tutor::with(['user', 'tutorFiles'])->get();
    }

    public function openRejectModal($tutorId)
    {
        $this->tutorId = $tutorId;
        $this->dispatch('showRejectModal');
    }

    public function openApproveModal($tutorId)
    {
        $this->tutorId = $tutorId;
        $this->dispatch('showApproveModal');
    }

    public function submitRejection()
    {
        $tutor = Tutor::find($this->tutorId);

        if ($tutor) {
            // Reject the tutor request
            $tutor->update(['is_verified' => 3]);

            // Send rejection email
            Mail::raw("Your tutor request has been rejected ❌. Reason: {$this->rejectionReason}", function ($message) use ($tutor) {
                $message->to($tutor->user->email)  // Send to tutor's email from user table
                    ->subject('Your Tutor Request Has Been Rejected');
            });

            // Create a rejection notification
            Notification::create([
                'user_id' => $tutor->user_id,
                'message' => 'Your tutor request has been rejected ❌. Please check your email for details.',
                'type' => 'cancellation',
            ]);

            $this->alert('success', 'Tutor rejected successfully and email sent.');

            // Reset modal fields
            $this->rejectionReason = '';
        } else {
            $this->alert('error', 'Tutor not found.');
        }

        // Reload tutors
        $this->loadTutors();
        // Close modal
        $this->dispatch('hideRejectModal');
    }

    public function submitApproval()
    {
        $tutor = Tutor::find($this->tutorId);

        if ($tutor) {
            // Approve the tutor request
            $tutor->update(['is_verified' => 1]);

            // Send approval email
            Mail::raw($this->approvalMessage ?: "Congratulations! Your tutor request has been approved 🎉", function ($message) use ($tutor) {
                $message->to($tutor->user->email)  // Send to tutor's email from user table
                    ->subject('Your Tutor Request Has Been Approved');
            });

            // Create approval notification
            Notification::create([
                'user_id' => $tutor->user_id,
                'message' => "congratulations ! Your tutor request has been approved 🎉 . Please check your email for details.",
                'type' => 'congratulation',
            ]);

            $this->alert('success', 'Tutor approved successfully and email sent.');

            $this->approvalMessage = '';
        } else {
            $this->alert('error', 'Tutor not found.');
        }

        $this->loadTutors();
        $this->dispatch('hideApproveModal');
    }

    public function render()
    {
        return view('livewire.dashboard.tutor-request')
            ->layout('components.layouts.dashboard');
    }
}
