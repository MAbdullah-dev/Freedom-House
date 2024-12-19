<?php

namespace App\Livewire\Dashboard;

use App\Models\Notification;
use App\Models\Tutor;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TutorRequest extends Component
{
    public $tutors;
    use LivewireAlert;

    public function mount()
    {
        $this->loadTutors();
    }

    public function loadTutors()
    {
        $this->tutors = Tutor::with(['user', 'tutorFiles'])->get();
    }

    public function approveTutor($tutorId)
    {
        $tutor = Tutor::find($tutorId);

        if ($tutor) {
            $tutor->update(['is_verified' => 1]);
            $this->alert('success', 'Tutor approved successfully.');
            $tutor->user->update(['role_id' => 2]);
            // session()->flash('success', 'Tutor approved successfully.');
        } else {
            $this->alert('error', 'Tutor not found.');
            // session()->flash('error', 'Tutor not found.');
        }
        Notification::create([
            'user_id' => $tutor->user_id,
            'message' => 'Congratulations ! Your tutor request has been approved 🎉.',
            'type' => 'congratulation',
        ]);

        $this->loadTutors();
    }

    public function rejectTutor($tutorId)
    {
        $tutor = Tutor::find($tutorId);

        if ($tutor) {
            $tutor->update(['is_verified' => 3]); // Update the `is_verified` column to 3
            $this->alert('success', 'Tutor rejected successfully.');
            // session()->flash('success', 'Tutor rejected successfully.');
        } else {
            $this->alert('error', 'Tutor not found.');
            // session()->flash('error', 'Tutor not found.');
        }
        Notification::create([
            'user_id' => $tutor->user_id,
            'message' => 'Your tutor request has been rejected ❌. Please check your email for details.',
            'type' => 'congratulation',
        ]);

        $this->loadTutors();
    }

    public function downloadFile($filePath)
    {
        $fullPath = "private/$filePath";

        if (Storage::exists($fullPath)) {
            $this->alert('success', 'File downloaded successfully.');
            session()->flash('error', 'File not found.');
            return;
        }

        // Return the file as a download
        return response()->download(storage_path("app/$fullPath"));
    }


    public function render()
    {
        return view('livewire.dashboard.tutor-request')
            ->layout('components.layouts.dashboard');
    }
}
