<?php

namespace App\Livewire\Dashboard;

use App\Models\Category;
use App\Models\Tag;
use App\Models\Tutor;
use App\Models\Course;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateCourse extends Component
{
    use WithFileUploads;
    use LivewireAlert;

    public $courseTitle;
    public $description;
    public $category = [];
    public $tags = [];
    public $file;
    public $videoUrl;
    public $learnDetails;
    public $audienceDetails;
    public $requirements;

    public $tagslist;
    public $categorylist;
    public $tutorList;

    protected $rules = [
        'courseTitle' => 'required|string|max:255',
        'description' => 'required|string',
        'category' => 'required|array',
        'category.*' => 'exists:categories,id',
        'tags' => 'nullable|array',
        'tags.*' => 'exists:tags,id',
        'file' => 'required|image|max:1024',
        'videoUrl' => 'required|url',
        'learnDetails' => 'required|string',
        'audienceDetails' => 'nullable|string',
        'requirements' => 'nullable|string',
    ];

    public function mount()
    {
        $this->categorylist = Category::all();
        $this->tagslist = Tag::all();
        $this->tutorList = Tutor::all();
    }

    protected function uploadThumbnail()
    {
        return $this->file ? $this->file->store('uploads', 'public') : null;
    }

    public function submitCourse()
    {
        // Validate input
        $this->validate();

        // Handle file upload
        $filePath = $this->uploadThumbnail();

        // Create a new course record
        $course = Course::create([
            'user_id' => auth()->id(), // Set to the logged-in user
            'title' => $this->courseTitle,
            'description' => $this->description,
            'thumbnail' => $filePath,
            'video_url' => $this->videoUrl,
            'learning_outcomes' => $this->learnDetails,
            'requirements' => $this->requirements,
            'target_audience' => $this->audienceDetails,
            'is_published' => false,
        ]);

        // Attach categories and tags to the course
        $course->categories()->attach($this->category);
        $course->tags()->sync($this->tags);

        // Reset form fields after submission
        $this->reset();

        // Show a success message
        $this->alert('success', 'Course created successfully!');
    }

    public function render()
    {
        return view('livewire.dashboard.create-course', [
            'categorylist' => $this->categorylist,
            'tagslist' => $this->tagslist,
        ])->layout('components.layouts.createCourse');
    }
}
