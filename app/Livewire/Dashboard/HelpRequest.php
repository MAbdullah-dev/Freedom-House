<?php
namespace App\Livewire\Dashboard;

use App\Models\Request;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class HelpRequest extends Component
{
    use WithFileUploads,LivewireAlert;

    public $user_id, $subject, $request_detail, $request_img, $status, $requests;

public function createRequest()
{
    $this->validate([
        'subject' => 'required|string|max:50',
        'request_detail' => 'required|string',
        'request_img' => 'nullable|image|max:1024',
    ]);

    $path = $this->request_img ? $this->request_img->store('requests', 'public') : null;

    Request::create([
        'user_id' => auth()->id(), // Automatically get user ID from auth
        'subject' => $this->subject,
        'request_detail' => $this->request_detail,
        'request_img' => $path,
    ]);

    $this->alert('success', 'Help Request has been sent.');
    $this->resetForm();
}


    private function resetForm()
    {
        $this->reset(['subject', 'request_detail', 'request_img']);
    }
        public function mount()
    {
        $this->requests = Request::where('user_id', auth()->id())->get();
    }

    public function render()
    {
        return view('livewire.dashboard.help-request')
            ->layout('components.layouts.dashboard');
    }
}
