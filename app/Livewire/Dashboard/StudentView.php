<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\RequiresRole;
use App\Models\Role;
use Livewire\Component;

class StudentView extends Component
{
    use RequiresRole;

    public $studentdata;

    public function boot(): void
    {
        $this->requireRole(Role::ADMIN);
    }
    public function mount($id)
    {
        // dd($id);
        $this->loadStudentData();
    }
    public function loadStudentData() {}
    public function render()
    {
        return view('livewire.dashboard.student-view')->layout('components.layouts.dashboard');
    }
}
