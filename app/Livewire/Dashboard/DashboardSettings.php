<?php
namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class DashboardSettings extends Component
{
    use WithFileUploads;

    public $user;
    public $coverPhoto;
    public $profilePhoto;
    public $firstName;
    public $lastName;
    public $userName;
    public $phone;
    public $bio;
    public $displayName;

    public function mount()
    {
        $this->user = Auth::user();
        $this->firstName = $this->user->first_name;
        $this->lastName = $this->user->last_name;
        $this->userName = $this->user->username;
        $this->phone = $this->user->phone;
        $this->bio = $this->user->bio;
        $this->displayName = $this->user->display_name;
    }

    // Update the profile information
    public function updateProfile()
    {
        $this->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'userName' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string',
            'displayName' => 'nullable|string',
            'coverPhoto' => 'nullable|image|max:1024', // 1MB max for cover photo
            'profilePhoto' => 'nullable|image|max:1024', // 1MB max for profile photo
        ]);

        // If cover photo is uploaded, save it
        if ($this->coverPhoto) {
            $coverPhotoPath = $this->coverPhoto->store('cover_photos', 'public');
            $this->user->cover_photo = $coverPhotoPath;
        }

        // If profile photo is uploaded, save it
        if ($this->profilePhoto) {
            $profilePhotoPath = $this->profilePhoto->store('profile_photos', 'public');
            $this->user->profile_photo = $profilePhotoPath;
        }

        // Update the user's profile information
        $this->user->first_name = $this->firstName;
        $this->user->last_name = $this->lastName;
        $this->user->username = $this->userName;
        $this->user->phone_number = $this->phoneNumber;
        $this->user->bio = $this->bio;
        $this->user->display_name = $this->displayName;

        // Save the updated user data
        $this->user->save();

        session()->flash('message', 'Profile updated successfully!');
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-settings')->layout('components.layouts.dashboard');
    }
}
