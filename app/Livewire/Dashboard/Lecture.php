<?php

namespace App\Livewire\Dashboard;

use App\Jobs\FetchVimeoVideoDetailsJob;
use App\Models\Course;
use App\Models\Lecture as ModelsLecture;
use Vimeo\Laravel\Facades\Vimeo;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class Lecture extends Component
{
    use LivewireAlert, WithFileUploads;

    public $courses = [];
    public $course_id; // Course selected once for all lectures
    public $lectures = [];
    public $alllectures;
    public $totalLectures = 0;
    public $usedOrders = [];

    protected $rules = [
        'course_id' => 'required|exists:courses,id',
        'lectures.*.title' => 'required|string|max:255',
        'lectures.*.description' => 'nullable|string',
        'lectures.*.video_file' => 'required|file|mimetypes:video/mp4',
        'lectures.*.order' => 'required|integer|distinct|min:1',
    ];

    public function mount()
    {
        $this->courses = Course::where('is_published', true)
            ->where('is_drafted', false)
            ->where('course_type', 'recorded')
            ->get();

        // Initialize with one lecture input block
        $this->initializeLectureBlock();
    }
    public function fetchLectures()
    {
        $this->dispatch('refreshComponent');
    }
    public function updatedCourseId()
    {
        if ($this->course_id) {
            $this->alllectures = ModelsLecture::where('course_id', $this->course_id)->get();
            $this->totalLectures = $this->alllectures->count() + count($this->lectures);
            $this->usedOrders = $this->alllectures->pluck('order')->toArray();
        }
    }

    private function initializeLectureBlock()
    {
        $this->lectures[] = [
            'title' => '',
            'description' => '',
            'video_file' => '',
            'order' => '',
        ];
    }

    public function addLectureBlock()
    {
        $this->initializeLectureBlock();
        $this->totalLectures += 1;
        $this->dispatch('refreshComponent');
    }

    public function removeLectureBlock($index)
    {
        unset($this->lectures[$index]);
        $this->lectures = array_values($this->lectures); // Reindex the array
    }

    public function submit()
    {
        $this->validate();

        foreach ($this->lectures as $index => $lecture) {
            // Upload video to Vimeo
            $videoPath = $lecture['video_file']->store('temp_videos', 'local');
            $videoFullPath = Storage::disk('local')->path($videoPath);

            $response = Vimeo::upload($videoFullPath, [
                'name' => $lecture['title'],
                'description' => $lecture['description'],
            ]);

            // Get the video ID from the Vimeo response
            $videoId = $response; // Vimeo returns the video ID or URL

            // Delete temporary file
            Storage::disk('local')->delete($videoPath);

            // Create lecture record in the database (without duration initially)
            $lectureRecord = ModelsLecture::create([
                'course_id' => $this->course_id,
                'title' => $lecture['title'],
                'description' => $lecture['description'],
                'video_file' => $videoId, // Vimeo video ID
                'video_duration' => null, // Initially null
                'order' => $lecture['order'],
            ]);

            // Dispatch a job to fetch video details later
            FetchVimeoVideoDetailsJob::dispatch($lectureRecord->id, $videoId);
        }

        $this->alert('success', 'Lectures successfully uploaded to Vimeo! Video details will update soon.');
        $this->resetFields();
    }



    private function resetFields()
    {
        $this->course_id = '';
        $this->lectures = [];
        $this->initializeLectureBlock();
        $this->totalLectures = 0;
        $this->usedOrders = [];
    }
    public function updateorder()
    {
        $this->dispatch('refreshComponent');
    }
    public function render()
    {
        return view('livewire.dashboard.lecture')->layout('components.layouts.dashboard');
    }
}
