<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\RequiresRole;
use App\Models\Role;
use App\Models\User;
use Livewire\Component;

class Student extends Component
{
    use RequiresRole;

    public $studentsWithEnrollments = [];
    public $selectedStudentId;
    public $selectedStudentData = [];
    public $selectedStudentCourses = [];
    public $newPassword = '';
    public $originalStudentData = [];
    public $hasChanges = false;

    public function boot(): void
    {
        $this->requireRole(Role::ADMIN);
    }

    public function mount()
    {
        $this->loadStudentData();
    }

    public function loadStudentData()
    {
        $learnerRole = Role::findByName(Role::LEARNER);

        $students = User::withTrashed()
            ->with(['role', 'bookings.course'])
            ->when(
                $learnerRole,
                fn ($query) => $query->where('role_id', $learnerRole->id),
                fn ($query) => $query->whereRaw('0 = 1')
            )
            ->get();

        $this->studentsWithEnrollments = [];

        foreach ($students as $student) {
            $enrolledCourses = $student->bookings->pluck('course')->filter();

            $this->studentsWithEnrollments[] = [
                'student' => $student,
                'courses' => $enrolledCourses,
                'count' => $enrolledCourses->count(),
            ];
        }
    }

    public function blockUser($userId)
    {
        $user = User::withTrashed()->find($userId);

        if ($user && is_null($user->deleted_at)) {
            $user->delete(); // soft delete
        }

        $this->loadStudentData();
    }

    public function unblockUser($userId)
    {
        $user = User::withTrashed()->find($userId);

        if ($user && $user->trashed()) {
            $user->restore();
        }

        $this->loadStudentData();
    }

    public function promoteUser($userId, $roleName)
    {
        $role = is_string($roleName) ? Role::findByName($roleName) : null;

        if ($role === null || ! in_array($role->role_name, [Role::ADMIN, Role::TUTOR], true)) {
            abort(403);
        }

        $user = User::withTrashed()->find($userId);

        if ($user) {
            $user->role_id = $role->id;
            $user->save();
        }

        $this->loadStudentData();
    }

    public function showDetails($userId)
    {
        $user = User::withTrashed()->with('bookings.course')->findOrFail($userId);

        $this->selectedStudentId = $user->id;
        $this->selectedStudentData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'bio' => $user->bio,
            'timezone' => $user->timezone,
            'facebook' => $user->facebook,
            'twitter' => $user->twitter,
            'linkedin' => $user->linkedin,
            'website' => $user->website,
            'github' => $user->github,
            'microsoft_account' => $user->microsoft_account,

        ];
        // dd($this->selectedStudentData['microsoft_account']);

        $this->selectedStudentCourses = $user->bookings->pluck('course')->filter();
        $this->originalStudentData = $this->selectedStudentData;
        $this->newPassword = '';
        $this->hasChanges = false;
    }

    public function updateStudent()
    {
        $user = User::withTrashed()->findOrFail($this->selectedStudentId);

        $user->update(collect($this->selectedStudentData)->except([
            'role_id',
            'password',
            'remember_token',
        ])->all());

        if (!empty($this->newPassword)) {
            $user->password = bcrypt($this->newPassword);
            $user->save();
            $this->newPassword = '';
        }

        session()->flash('message', 'Student profile updated successfully.');
        $this->loadStudentData();
        $this->hasChanges = false;
        $this->originalStudentData = $this->selectedStudentData;
        $this->dispatch('close-modal'); // Close modal after deletion
    }

    public function updated($propertyName)
    {
        $this->hasChanges = $this->selectedStudentData !== $this->originalStudentData || !empty($this->newPassword);
    }

    public function render()
    {
        return view('livewire.dashboard.student')->layout('components.layouts.dashboard');
    }
}
