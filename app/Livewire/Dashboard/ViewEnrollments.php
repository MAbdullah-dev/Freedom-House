<?php

namespace App\Livewire\Dashboard;

use App\Models\Classe;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ViewEnrollments extends Component
{
    public $dateRange;
    public $classes = [];
    public $selectedClass;

    // Watch for changes to $dateRange and fetch classes automatically

    private function convertClassToUserTimezone($class)
    {
        $userTimezone = Auth::user()->timezone;
        $class->class_date = Carbon::parse($class->class_date . ' ' . $class->class_time, 'UTC')->setTimezone($userTimezone)->toDateString();
        $class->class_time = Carbon::parse($class->class_date . ' ' . $class->class_time, 'UTC')->setTimezone($userTimezone)->toTimeString();
        $class->booking_start_date = Carbon::parse($class->booking_start_date, 'UTC')->setTimezone($userTimezone)->toDateString();
        $class->booking_end_date = Carbon::parse($class->booking_end_date, 'UTC')->setTimezone($userTimezone)->toDateString();
        return $class;
    }

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
                ->get()
                ->map(function ($class) {
                    return $this->convertClassToUserTimezone($class);
                });
        }
    }

    public function selectClass($classId)
    {
        $this->selectedClass = Classe::findOrFail($classId);
        $this->selectedClass = $this->convertClassToUserTimezone($this->selectedClass);
        $this->updatedDateRange();
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
