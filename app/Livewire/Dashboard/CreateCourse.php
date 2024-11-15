<?php

namespace App\Livewire\Dashboard;

use App\Models\Category;
use App\Models\Tag;
use App\Models\Tutor;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
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
    public $tutors = [];
    public $file;
    public $videoUrl;
    public $learnDetails;
    public $audienceDetails;
    public $requirements;
    public $selectedTutors = [];

    public $tagslist = [];
    public $categorylist = [];
    public $tutorList = [];

    protected $rules = [
        'courseTitle' => 'required|string|max:255|unique:courses,title',
        'description' => 'required|string',
        'category' => '',
        'category.*' => 'exists:categories,id',
        'tags' => 'required|array',
        'tags.*' => 'required|exists:tags,id',
        'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:1024',
        'videoUrl' => 'required|url',
        'learnDetails' => 'required|string',
        'audienceDetails' => 'required|string',
        'requirements' => 'required|string',
    ];

    public function mount()
    {
        $this->categorylist = Category::all();
        $this->tagslist = Tag::all();
        $this->tutorList = Tutor::with('user')->get();

        // Set the logged-in tutor's ID as always selected
        $authTutorId = Auth::user()->id;
        $this->tutors = [$authTutorId];
        $this->selectedTutors = Tutor::with('user')->where('id', $authTutorId)->get();
    }

    public function updatedTutors($value)
    {
        $authTutorId = Auth::user()->id;

        // Ensure the authenticated user's tutor ID is always selected
        if (!in_array($authTutorId, $this->tutors)) {
            $this->tutors[] = $authTutorId;
        }

        // Update the selected tutors list
        $this->selectedTutors = Tutor::with('user')->whereIn('id', $this->tutors)->get();
    }

    protected function uploadThumbnail()
    {
        return $this->file ? $this->file->store('uploads', 'public') : null;
    }

    public function submitCourse()
    {
        $this->validate();

        $filePath = $this->uploadThumbnail();

        $course = Course::create([
            'user_id' => Auth::user()->id,
            'title' => $this->courseTitle,
            'description' => $this->description,
            'thumbnail' => $filePath,
            'video_url' => $this->videoUrl,
            'learning_outcomes' => $this->learnDetails,
            'requirements' => $this->requirements,
            'target_audience' => $this->audienceDetails,
            'is_published' => false,
        ]);

        $course->categories()->attach($this->category);
        $course->tags()->attach($this->tags);
        $course->instructors()->attach($this->tutors);

        $this->reset();
        $this->reset('selectedTutors');

        $this->dispatch('clearSelect2');

        $this->alert('success', 'Course created successfully.');
        $this->redirect('/dashboard/mycourses');
    }

    public function render()
    {
        return view('livewire.dashboard.create-course')->layout('components.layouts.createCourse');
    }
}
