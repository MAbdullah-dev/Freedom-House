<?php

namespace App\Livewire\Dashboard;

use App\Models\Classe;
use Livewire\Component;
use Carbon\Carbon;

class ViewEnrollments extends Component
{
    public $dateRange; // Captures the selected date range
    public $classes = []; // Stores the filtered classes
    public $selectedClass; // Stores the class selected for modal display

    // Watch for changes to $dateRange and fetch classes automatically
    public function updatedDateRange()
    {
        if ($this->dateRange) {
            $dates = explode(' to ', $this->dateRange);
            $startDate = Carbon::parse($dates[0])->startOfDay();
            $endDate = isset($dates[1]) ? Carbon::parse($dates[1])->endOfDay() : $startDate->endOfDay();

            $this->classes = Classe::where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('booking_start_date', [$startDate, $endDate])
                    ->orWhereBetween('booking_end_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('booking_start_date', '<=', $startDate)
                            ->where('booking_end_date', '>=', $endDate);
                    });
            })
                ->orderBy('booking_start_date', 'asc')
                ->get();
        }
    }

    public function selectClass($classId)
    {
        $this->selectedClass = Classe::findOrFail($classId);
        // dd($this->selectedClass);
    }

    public function editClass()
    {
        $this->dispatch('close-modal');
        return redirect()->route('dashboard.create.class', ['classId' => $this->selectedClass->id]);
    }

    public function deleteClass()
    {
        if ($this->selectedClass) {
            $this->selectedClass->delete();
            $this->classes = Classe::all(); // Refresh class list
            $this->selectedClass = null;
            session()->flash('message', 'Class deleted successfully.');
        }
    }

    public function createQuiz()
    {
        if ($this->selectedClass && $this->selectedClass->class_type == 'recorded') {
            $this->dispatch('close-modal');
            return redirect()->route('quizzes.create', ['class_id' => $this->selectedClass->id]);
        }
    }


    public function render()
    {
        return view('livewire.dashboard.view-enrollments')->layout('components.layouts.dashboard');
    }
}
