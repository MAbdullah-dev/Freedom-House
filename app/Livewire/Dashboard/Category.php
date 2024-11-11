<?php

namespace App\Livewire\Dashboard;

use App\Models\Category as ModelsCategory;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class Category extends Component
{
    use LivewireAlert;

    public $category_name;
    public $category_list;
    public $search = ''; // Search property
    public $categoryIdBeingDeleted; // Holds the ID of the category being deleted
    public $categoryIdBeingUpdated; // For update functionality
    public $updatedCategoryName; // Temporary name for updating category

    public function mount()
    {
        // Initially fetch all categories
        $this->category_list = ModelsCategory::all();
    }

    protected $rules = [
        'category_name' => 'required|string|max:255|unique:categories', // Unique validation rule
    ];

    public function createCategory()
    {
        $this->validate();

        // Create a new category in the database using the Category model
        ModelsCategory::create(['category_name' => $this->category_name]);

        $this->alert('success', 'Category created successfully.');

        $this->category_name = ''; // Reset the input field

        // Refresh the category list after creating a category
        $this->category_list = ModelsCategory::all();
    }

    // Search categories based on the search query
    public function updatedSearch()
    {
        $this->category_list = ModelsCategory::where('category_name', 'like', '%' . $this->search . '%')->get();
    }

    public function confirmDelete($categoryId)
    {
        $this->categoryIdBeingDeleted = $categoryId;
    }

    public function destroyCategory()
    {
        ModelsCategory::find($this->categoryIdBeingDeleted)->delete();

        $this->alert('success', 'Category deleted successfully.');

        // Refresh the category list
        $this->category_list = ModelsCategory::all();

        // Close the modal
        $this->reset('categoryIdBeingDeleted');

        $this->dispatch('close-modal');
    }

    public function editCategory($categoryId)
    {
        $category = ModelsCategory::find($categoryId);
        $this->categoryIdBeingUpdated = $category->id;
        $this->updatedCategoryName = $category->category_name;
    }
    public function updateCategory()
    {
        $this->validate(['updatedCategoryName' => 'required|string|max:255|unique:categories,category_name,' . $this->categoryIdBeingUpdated]);
        $category = ModelsCategory::find($this->categoryIdBeingUpdated);
        $category->update(['category_name' => $this->updatedCategoryName]);

        $this->alert('success', 'Category updated successfully.');
        $this->category_list = ModelsCategory::all();
        $this->reset(['categoryIdBeingUpdated', 'updatedCategoryName']);

        $this->dispatch('close-modal');
    }
    public function closeModal()
    {
        $this->reset(['categoryIdBeingDeleted', 'categoryIdBeingUpdated', 'updatedCategoryName']);
    }

    public function render()
    {
        return view('livewire.dashboard.category')->layout('components.layouts.dashboard');
    }
}
