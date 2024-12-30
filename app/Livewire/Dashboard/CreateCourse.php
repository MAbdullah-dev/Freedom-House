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

    public $courseId;
    public $courseTitle;
    public $description;
    public $category = [];
    public $tags = [];
    public $tutors = [];
    public $file;
    // public $videoUrl;
    public $videoFile; // New variable for video file upload
    public $learnDetails;
    public $audienceDetails;
    public $requirements;

    public $courseType; // New variable
    public $is_paid; // New variable
    public $price; // New variable

    public $selectedTutors = [];
    public $tagslist = [];
    public $categorylist = [];
    public $tutorList = [];

    public $existingThumbnail;

    protected function rules()
    {
        return [
            'courseTitle' => 'required|string|max:35|unique:courses,title,' . $this->courseId,
            'description' => 'required|string',
            'category' => 'array|min:1',
            'category.*' => 'exists:categories,id',
            'tags' => 'required|array|min:1',
            'tags.*' => 'exists:tags,id',
            'file' => 'required|image|mimes:jpeg,png,jpg|max:1024',
            'videoFile' => 'required|file|mimes:mp4,mov,avi|max:10240', // New validation rule
            'learnDetails' => 'required|string',
            'audienceDetails' => 'required|string',
            'requirements' => 'required|string',
            'courseType' => 'required',
            'is_paid' => 'nullable|required_if:courseType,recorded',
            'price' => 'required_if:is_paid,Paid|min:0',
        ];
    }

    public function mount($courseId = null)
    {
        $this->tagslist = Tag::all();
        $this->categorylist = Category::all();
        $this->tutorList = Tutor::with('user')->get();

        if ($courseId) {
            $this->courseId = $courseId;
            $this->loadCourseData();
        }

        $authTutor = Tutor::where('user_id', Auth::id())->first();
        if ($authTutor) {
            $this->tutors = [$authTutor->id];
            $this->selectedTutors = [$authTutor];
        }
    }
    public function uploadVideo()
    {
        if ($this->videoFile) {
            return $this->videoFile->store('videos', 'public'); // Save video to the "public/videos" directory
        }
        return null;
    }

    public function loadCourseData()
    {
        $course = Course::with(['categories', 'tags', 'tutors.user'])->findOrFail($this->courseId);

        $this->courseTitle = $course->title;
        $this->description = $course->description;
        $this->category = $course->categories->pluck('id')->toArray();
        $this->tags = $course->tags->pluck('id')->toArray();
        $this->tutors = $course->tutors->pluck('user_id')->toArray();
        $this->learnDetails = $course->learning_outcomes;
        $this->audienceDetails = $course->target_audience;
        $this->requirements = $course->requirements;
        $this->videoFile = $course->video_path;
        $this->existingThumbnail = $course->thumbnail;
        $this->selectedTutors = $course->tutors;
        $this->is_paid = $course->is_paid;
        $this->price = $course->price;
        $this->courseType = $course->course_type;
    }

    public function updatedTutors($value)
    {
        $authTutor = Tutor::where('user_id', Auth::id())->first();

        // Ensure authenticated tutor remains selected
        if ($authTutor && !in_array($authTutor->id, $this->tutors)) {
            $this->tutors[] = $authTutor->id;
        }

        // Update selected tutors list
        $this->selectedTutors = Tutor::with('user')
            ->whereIn('id', array_unique($this->tutors))
            ->get();
    }

    public function uploadThumbnail()
    {
        if ($this->file) {
            return $this->file->store('uploads', 'public');
        }

        return $this->existingThumbnail;
    }

    public function submitCourse()
    {
        if ($this->courseId) {
            $this->updateCourse();
        } else {
            $this->createCourse();
        }
    }

    public function createCourse()
    {
        $this->validate();

        $filePath = $this->uploadThumbnail();
        $videoPath = $this->uploadVideo(); // Upload video file and get the path

        $course = Course::create([
            'user_id' => Auth::id(),
            'title' => $this->courseTitle,
            'description' => $this->description,
            'thumbnail' => $filePath,
            'video_path' => $videoPath, // Save video path in the database
            'learning_outcomes' => $this->learnDetails,
            'requirements' => $this->requirements,
            'target_audience' => $this->audienceDetails,
            'is_drafted' => false,
            'is_published' => false,
            'is_paid' => $this->is_paid,
            'price' => $this->price,
            'course_type' => $this->courseType,
            'is_completed' => $this->courseType === 'recorded' ? false : true, // Set based on course_type
        ]);

        $course->categories()->attach($this->category);
        $course->tags()->attach($this->tags);
        $course->tutors()->attach($this->tutors);

        $this->resetForm();
        $this->alert('success', 'Course created successfully.');
        $this->redirect(route('dashboard.mycourses'));
    }


    public function updateCourse()
    {
        $this->validate();

        $course = Course::findOrFail($this->courseId);

        // Upload new thumbnail (if any)
        $filePath = $this->uploadThumbnail();

        // Upload new video file (if any)
        $videoPath = $this->uploadVideo();

        $course->update([
            'title' => $this->courseTitle,
            'description' => $this->description,
            'thumbnail' => $filePath,
            'video_path' => $videoPath ?: $course->video_path, // Keep existing video if no new upload
            'learning_outcomes' => $this->learnDetails,
            'requirements' => $this->requirements,
            'target_audience' => $this->audienceDetails,
            'is_paid' => $this->is_paid,
            'price' => $this->price,
            'course_type' => $this->courseType,
        ]);

        $course->categories()->sync($this->category);
        $course->tags()->sync($this->tags);
        $course->tutors()->sync($this->tutors);

        $this->alert('success', 'Course updated successfully.');
    }

    public function saveAsDraft()
    {
        $this->validate();

        $filePath = $this->uploadThumbnail();
        $videoPath = $this->uploadVideo(); // Upload video file and get the path


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
            'is_paid' => $this->is_paid,
            'price' => $this->price,
            'course_type' => $this->courseType,
            'is_completed' => $this->courseType === 'recorded' ? false : true, // Set based on course_type
        ]);

        $course->categories()->attach($this->category);
        $course->tags()->attach($this->tags);
        $course->tutors()->attach($this->tutors);

        $this->resetForm();
        $this->alert('success', 'Course saved as draft.');
        $this->redirect(route('dashboard.mycourses'));
    }

    public function resetForm()
    {
        $this->reset();
        $this->dispatch('clearSelect2');
        $this->reset('selectedTutors');
    }
    public function updateCourseType()
    {
        $this->dispatch('refreshComponent');
    }
    public function updateIsPaid()
    {
        $this->dispatch('refreshComponent');
    }

    public function render()
    {
        return view('livewire.dashboard.create-course')->layout('components.layouts.createCourse');
    }
}
