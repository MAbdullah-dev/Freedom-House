<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class DashboardSettings extends Component
{
    use WithFileUploads, LivewireAlert;

    public $user;
    public $coverPhoto;
    public $profilePhoto;
    public $firstName;
    public $lastName;
    public $userName;
    public $phone;
    public $bio;

    public function mount()
    {
        $this->user = Auth::user();
        $this->firstName = $this->user->first_name;
        $this->lastName = $this->user->last_name;
        $this->userName = $this->user->username;
        $this->phone = $this->user->phone;
        $this->bio = $this->user->bio;
    }

    public function updateProfile()
    {
        $this->validate([
            'firstName' => 'required|string|max:20',
            'lastName' => 'required|string|max:20',
            'userName' => 'required|string|max:20|unique:users,column,except,id',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string',
            'coverPhoto' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'profilePhoto' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
        ]);

        if ($this->coverPhoto) {
            $coverPhotoPath = $this->coverPhoto->store('cover_photos', 'public');
            $this->user->cover_photo = $coverPhotoPath;
        }

        if ($this->profilePhoto) {
            $profilePhotoPath = $this->profilePhoto->store('pfps', 'public');
            $this->user->pfp = $profilePhotoPath;
        }

        $this->user->first_name = $this->firstName;
        $this->user->last_name = $this->lastName;
        $this->user->username = $this->userName;
        $this->user->phone = $this->phone;
        $this->user->bio = $this->bio;

        $this->user->save();

        $this->alert('success', 'Profile updated successfully!');
    }

    public function deleteCoverPhoto()
    {
        if ($this->user->cover_photo) {
            Storage::disk('public')->delete($this->user->cover_photo);
            $this->user->cover_photo = null;
            $this->user->save();

            $this->alert('success', 'Cover photo deleted successfully!');
        }
    }

    public function deleteProfilePhoto()
    {
        if ($this->user->pfp) {
            Storage::disk('public')->delete($this->user->pfp);
            $this->user->pfp = null;
            $this->user->save();

            $this->alert('success', 'Profile photo deleted successfully!');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-settings')->layout('components.layouts.dashboard');
    }
}
