<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class ClassForm extends Component
{
    public $courseName;
    public $classTitle;
    public $classDescription;
    public $classType;
    public $isPaid;
    public $price;
    public $startDate;
    public $endDate;
    public $maxStudents;
    public $videoLink;
    public $quiz;
    public $assignment;
    public $teamsLink;
    public $address;

    public $currentTab = 1;

    protected $rules = [
        'courseName' => 'required',
        'classTitle' => 'required|string|max:255',
        'classDescription' => 'required|string',
        'classType' => 'required',
        'isPaid' => 'required',
        'price' => 'nullable|numeric',
        'startDate' => 'required|date',
        'endDate' => 'required|date|after:startDate',
        'maxStudents' => 'nullable|integer',
        'videoLink' => 'nullable|url',
        'teamsLink' => 'nullable|url',
        'address' => 'nullable|string|max:255',
    ];

    public function updatedIsPaid($value)
    {
        if ($value == 'free') {
            $this->price = null;
        }
    }

    public function updatedClassType($value)
    {
        // Reset fields when class type changes
        $this->videoLink = null;
        $this->teamsLink = null;
        $this->address = null;
    }

    public function nextStep()
    {
        $this->currentTab = 2;
    }

    public function prevStep()
    {
        $this->currentTab = 1;
    }

    public function submit()
    {
        $this->validate();

        // Handle form submission logic here
    }

    public function render()
    {
        return view('livewire.dashboard.class-form');
    }
}
