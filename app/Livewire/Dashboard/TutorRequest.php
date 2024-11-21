<?php

namespace App\Livewire\Dashboard;

use App\Models\Tutor;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class TutorRequest extends Component
{
    public $tutors;

    public function mount()
    {
        $this->loadTutors();
    }

    public function loadTutors()
    {
        // Eager load the related models to minimize database queries
        $this->tutors = Tutor::with(['user', 'tutorFiles'])->get();
    }

    public function approveTutor($tutorId)
    {
        $tutor = Tutor::find($tutorId);

        if ($tutor) {
            $tutor->update(['is_verified' => 1]); // Update the `is_verified` column to 1
            session()->flash('success', 'Tutor approved successfully.');
        } else {
            session()->flash('error', 'Tutor not found.');
        }

        $this->loadTutors(); // Refresh the tutor list
    }

    public function rejectTutor($tutorId)
    {
        $tutor = Tutor::find($tutorId);

        if ($tutor) {
            $tutor->update(['is_verified' => 3]); // Update the `is_verified` column to 3
            session()->flash('success', 'Tutor rejected successfully.');
        } else {
            session()->flash('error', 'Tutor not found.');
        }

        $this->loadTutors(); // Refresh the tutor list
    }

    public function downloadFile($filePath)
    {
        // Ensure the correct path is used based on storage location
        $fullPath = "private/$filePath";  // This assumes the file path stored in the database starts from 'tutor_files/'
        // dd($fullPath);

        // Check if the file exists in storage
        if (Storage::exists($fullPath)) {
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
