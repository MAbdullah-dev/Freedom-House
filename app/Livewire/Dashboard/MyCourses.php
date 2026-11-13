<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\RequiresRole;
use App\Models\Course;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Livewire\Concerns\LivewireAlert;

class MyCourses extends Component
{
    use RequiresRole;
    use LivewireAlert;

    public function boot(): void
    {
        $this->requireRole(Role::TUTOR, Role::ADMIN);
    }

    public $publishedCourses;
    public $pendingCourses;
    public $draftedCourses;
    public $loggedInUser;
    public $courseId;
    public $activeTab = 'published'; // Default tab

    public function mount()
    {
        $this->loggedInUser = Auth::user();
        $this->refreshCoursedata();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab; // Change the active tab
    }

    public function refreshCoursedata()
    {
        $this->publishedCourses = Course::where('user_id', Auth::id())
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->get();

        $this->pendingCourses = Course::where('user_id', Auth::id())
            ->where('is_published', false)
            ->where('is_drafted', false)
            ->get();

        $this->draftedCourses = Course::where('user_id', Auth::id())
            ->where('is_published', false)
            ->where('is_drafted', true)
            ->get();
    }

    public function confirmDelete($id)
    {
        $this->courseId = $id;
    }

    public function destroyCourse()
    {
        Course::findOrFail($this->courseId)->delete();
        $this->alert('success', 'Course deleted successfully.');
        $this->reset(['courseId']);
        $this->dispatch('close-modal'); // Close modal
        $this->refreshCoursedata();
    }

    public function render()
    {
        return view('livewire.dashboard.my-courses', [
            'publishedCourses' => $this->publishedCourses,
            'pendingCourses' => $this->pendingCourses,
            'draftedCourses' => $this->draftedCourses,
            'activeTab' => $this->activeTab,
        ])->layout('components.layouts.dashboard');
    }
}
