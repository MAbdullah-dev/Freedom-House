<?php

namespace App\Livewire\Dashboard;

use App\Models\Tag;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class Tags extends Component
{
    use LivewireAlert;

    public $name;
    public $tags; // Add this property to store the list of tags
    public $search = '';
    public $tagIdBeingDeleted = null;
    public $tagIdBeingUpdated = null;

    protected $rules = [
        'name' => 'required|string|max:255|unique:tags,name',
    ];

    public function mount()
    {
        $this->tags = Tag::all();
        $this->refreshTagList(); // Populate tags on component load
    }

    public function refreshTagList()
    {
        // Update the tags list based on the search input
        $this->tags = Tag::where('name', 'like', '%' . $this->search . '%')->get();
    }

    public function createTag()
    {
        $this->validate();
        Tag::create(['name' => $this->name]);
        $this->alert('success', 'Tag created successfully.');
        $this->reset('name');
        $this->refreshTagList();
    }

    public function updatedSearch()
    {
        // Refresh tags list whenever search input is updated
        $this->refreshTagList();
    }

    public function confirmDelete($tagId)
    {
        $this->tagIdBeingDeleted = $tagId;
    }

    public function destroyTag()
    {
        Tag::find($this->tagIdBeingDeleted)->delete();
        $this->alert('success', 'Tag deleted successfully.');
        $this->reset('tagIdBeingDeleted');
        $this->refreshTagList();

        $this->dispatch('close-modal'); // Close modal after deletion
    }

    public function confirmUpdate($tagId)
    {
        $tag = Tag::findOrFail($tagId);
        $this->tagIdBeingUpdated = $tag->id;
        $this->name = $tag->name;
    }

    public function updateTag()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $this->tagIdBeingUpdated,
        ]);

        $tag = Tag::findOrFail($this->tagIdBeingUpdated);
        $tag->update(['name' => $this->name]);
        $this->alert('success', 'Tag updated successfully.');
        $this->reset(['tagIdBeingUpdated', 'name']);
        $this->refreshTagList();

        $this->dispatch('close-modal'); // Close modal after update
    }

    public function closeModal()
    {
        $this->reset(['tagIdBeingDeleted', 'tagIdBeingUpdated', 'name']);
    }
    public function render()
    {
        return view('livewire.dashboard.tags')->layout('components.layouts.dashboard');
    }
}
