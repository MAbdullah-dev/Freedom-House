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
    use WithFileUploads, LivewireAlert;

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
        'category' => ' array|min:1',
        'category.*' => 'exists:categories,id',
        'tags' => 'required|array|min:1',
        'tags.*' => 'exists:tags,id',
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

        // Ensure logged-in tutor is preselected
        $authTutor = Tutor::where('user_id', Auth::id())->first();
        if ($authTutor) {
            $this->tutors = [$authTutor->user_id];
            $this->selectedTutors = [$authTutor];
        }
        // dd(Auth::user()->first_name);
    }

    public function updatedTutors($value)
    {
        $authTutor = Tutor::where('user_id', Auth::id())->first();

        // Ensure authenticated tutor remains selected
        if ($authTutor && !in_array($authTutor->user_id, $this->tutors)) {
            $this->tutors[] = $authTutor->user_id;
        }

        // Update selected tutors list
        $this->selectedTutors = Tutor::with('user')
            ->whereIn('id', array_unique($this->tutors))
            ->get();
    }

    protected function uploadThumbnail()
    {
        try {
            return $this->file ? $this->file->store('uploads', 'public') : null;
        } catch (\Exception $e) {
            $this->alert('error', 'Failed to upload file: ' . $e->getMessage());
            return null;
        }
    }

    public function submitCourse()
    {
        $this->validate();

        // Upload the thumbnail
        $filePath = $this->uploadThumbnail();

        // Create the course
        $course = Course::create([
            'user_id' => Auth::id(),
            'title' => $this->courseTitle,
            'description' => $this->description,
            'thumbnail' => $filePath,
            'video_url' => $this->videoUrl,
            'learning_outcomes' => $this->learnDetails,
            'requirements' => $this->requirements,
            'target_audience' => $this->audienceDetails,
            'is_drafted' => false,
            'is_published' => false,
        ]);

        // Attach related data
        $course->categories()->attach($this->category);
        $course->tags()->attach($this->tags);
        $course->instructors()->attach($this->tutors);

        // Reset form and notify user
        $this->reset();
        $this->reset('selectedTutors');
        $this->dispatch('clearSelect2');
        $this->alert('success', 'Course created successfully.');

        // Redirect to my courses page
        $this->redirect(route('dashboard.mycourses'));
    }
    public function saveAsDraft()
    {
        $this->validate();

        // Upload the thumbnail
        $filePath = $this->uploadThumbnail();

        // Create the course
        $course = Course::create([
            'user_id' => Auth::id(),
            'title' => $this->courseTitle,
            'description' => $this->description,
            'thumbnail' => $filePath,
            'video_url' => $this->videoUrl,
            'learning_outcomes' => $this->learnDetails,
            'requirements' => $this->requirements,
            'target_audience' => $this->audienceDetails,
            'is_drafted' => true,
            'is_published' => false,
        ]);

        // Attach related data
        $course->categories()->attach($this->category);
        $course->tags()->attach($this->tags);
        $course->instructors()->attach($this->tutors);

        // Reset form and notify user
        $this->reset();
        $this->reset('selectedTutors');
        $this->dispatch('clearSelect2');
        $this->alert('success', 'Course created successfully.');

        // Redirect to my courses page
        $this->redirect(route('dashboard.mycourses'));
    }

    public function render()
    {
        return view('livewire.dashboard.create-course')->layout('components.layouts.createCourse');
    }
}
