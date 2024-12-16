<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Course;
use App\Models\Tutor;
use Livewire\Component;
use Livewire\WithPagination;

class CourseCatelogue extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedCategories = [];
    public $featuredTutors;
    public $categories;

    public function mount()
    {
        $this->selectedCategories = [];
        $this->categories = Category::all();
        $this->featuredTutors = Tutor::with('user')->get();
    }
    public function selectedCategorieschanged($id)
    {
        if (in_array($id, $this->selectedCategories)) {
            $key = array_search($id, $this->selectedCategories);
            unset($this->selectedCategories[$key]);
        } else {
            $this->selectedCategories[] = $id;
        }
        $this->dispatch('refreshComponent');
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }


    public function clearFilters()
    {
        $this->search = '';
        $this->selectedCategories = [];
        $this->resetPage();
    }

    public function render()
    {
        $courses = Course::with(['user', 'categories'])
            ->where('is_published', true)
            ->where('is_drafted', false)
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->when($this->selectedCategories, function ($query) {
                $query->whereHas('categories', function ($categoryQuery) {
                    $categoryQuery->whereIn('categories.id', $this->selectedCategories);
                });
            })
            ->paginate(10);


        return view('livewire.course-catelogue', [
            'courses' => $courses,
            'featuredTutors' => $this->featuredTutors,
            'categories' => $this->categories,
        ]);
    }
}
