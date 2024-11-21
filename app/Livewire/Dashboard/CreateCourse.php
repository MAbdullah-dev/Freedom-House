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
    public $videoUrl;
    public $learnDetails;
    public $audienceDetails;
    public $requirements;

    public $selectedTutors = [];
    public $tagslist = [];
    public $categorylist = [];
    public $tutorList = [];

    public $existingThumbnail;

    protected function rules()
    {
        return [
            'courseTitle' => 'required|string|max:255|unique:courses,title,' . $this->courseId,
            'description' => 'required|string',
            'category' => 'array|min:1',
            'category.*' => 'exists:categories,id',
            'tags' => 'required|array|min:1',
            'tags.*' => 'exists:tags,id',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1024',
            'videoUrl' => 'required|url',
            'learnDetails' => 'required|string',
            'audienceDetails' => 'required|string',
            'requirements' => 'required|string',
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

    public function loadCourseData()
    {
        $course = Course::with(['categories', 'tags', 'instructors.user'])->findOrFail($this->courseId);
        // dd($course->toArray());

        $this->courseTitle = $course->title;
        $this->description = $course->description;
        $this->category = $course->categories->pluck('id')->toArray();
        $this->tags = $course->tags->pluck('id')->toArray();
        $this->tutors = $course->instructors->pluck('user_id')->toArray();
        $this->learnDetails = $course->learning_outcomes;
        $this->audienceDetails = $course->target_audience;
        $this->requirements = $course->requirements;
        $this->videoUrl = $course->video_url;
        $this->existingThumbnail = $course->thumbnail;
        $this->selectedTutors = $course->instructors;
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

        $course->categories()->attach($this->category);
        $course->tags()->attach($this->tags);
        $course->instructors()->attach($this->tutors);

        $this->resetForm();
        $this->alert('success', 'Course created successfully.');
        $this->redirect(route('dashboard.mycourses'));

        // return redirect()->route('dashboard.course.details', ['courseId' => $course->id]);
    }

    public function updateCourse()
    {
        $this->validate();

        $course = Course::findOrFail($this->courseId);
        $filePath = $this->uploadThumbnail();

        $course->update([
            'title' => $this->courseTitle,
            'description' => $this->description,
            'thumbnail' => $filePath,
            'video_url' => $this->videoUrl,
            'learning_outcomes' => $this->learnDetails,
            'requirements' => $this->requirements,
            'target_audience' => $this->audienceDetails,
        ]);

        $course->categories()->sync($this->category);
        $course->tags()->sync($this->tags);
        $course->instructors()->sync($this->tutors);

        $this->alert('success', 'Course updated successfully.');
    }

    public function resetForm()
    {
        $this->reset();
        $this->dispatch('clearSelect2');
        $this->reset('selectedTutors');
    }

    public function render()
    {
        return view('livewire.dashboard.create-course')->layout('components.layouts.createCourse');
    }
}
