<?php

namespace App\Livewire\Inc;

use App\Models\Role;
use App\Models\Tutor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class DashboardHead extends Component
{
    use WithFileUploads, LivewireAlert;

    public $username, $specialization, $preferred_teaching_method, $experience, $file_path;

    public function createTutor()
    {
        $this->validate([
            'specialization' => 'required|string|max:50',
            'preferred_teaching_method' => 'required|in:online,in-person',
            'experience' => 'required|string|max:1000',
            'file_path' => 'required|file|mimes:pdf,doc,docx,txt|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $user->roles()->syncWithoutDetaching([Role::TUTOR_ROLE]);

            $tutor = Tutor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'specialization' => $this->specialization,
                    'preferred_teaching_method' => $this->preferred_teaching_method,
                    'is_verified' => false,
                ]
            );

            $filePath = $this->file_path->store('tutor_files');
            $tutor->tutorFiles()->create(['file_path' => $filePath]);

            DB::commit();

            $this->alert('success', 'Tutor profile created successfully.');
            $this->resetForm();
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tutor Creation Failed', ['error' => $e->getMessage()]);
            $this->alert('error', 'Something went wrong. Please try again.');
        }
    }

    private function resetForm()
    {
        $this->reset(['specialization', 'preferred_teaching_method', 'experience', 'file_path']);
    }

    public function mount()
    {
        $user = auth()->user();
        $this->username = $user->first_name . ' ' . $user->last_name;
    }

    public function render()
    {
        return view('livewire.inc.dashboard-head');
    }
}
