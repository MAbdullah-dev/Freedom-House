<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\RequiresRole;
use App\Models\Role;
use App\Models\User;
use Livewire\Component;

class StudentView extends Component
{
    use RequiresRole;

    public $studentdata;

    public $studentId;

    public function boot(): void
    {
        $this->requireRole(Role::ADMIN);
    }

    public function mount($id = null)
    {
        $this->studentId = $id;
        $this->loadStudentData();
    }

    public function loadStudentData()
    {
        if ($this->studentId === null || $this->studentId === '') {
            $this->studentdata = null;

            return;
        }

        $this->studentdata = User::withTrashed()
            ->with(['bookings.course'])
            ->find($this->studentId);
    }
    public function render()
    {
        return view('livewire.dashboard.student-view')->layout('components.layouts.dashboard');
    }
}
