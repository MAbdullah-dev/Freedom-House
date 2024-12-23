<?php

namespace App\Livewire\Dashboard;

use App\Jobs\FetchVimeoVideoDetailsJob;
use App\Models\Course;
use App\Models\Lecture as ModelsLecture;
use Illuminate\Support\Facades\Log;
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
        'lectures.*.video_file' => 'required|file|mimetypes:video/mp4,video/quicktime', // Max 50MB
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
        try {
            $this->validate(); // Perform the validation

            // Log each video file's size for debugging
            foreach ($this->lectures as $index => $lecture) {
                Log::info('Uploaded file size: ' . $lecture['video_file']->getSize() . ' bytes');

                // Store the video file and link it to the lecture
                $path = $lecture['video_file']->store('videos', 'public');

                // Add logic to store lecture details to the database
                Lecture::create([
                    'title' => $lecture['title'],
                    'description' => $lecture['description'],
                    'video_file' => $path,
                    'order' => $index + 1, // Example of setting order (you can customize it)
                ]);
            }

            // Show success message (you can integrate SweetAlert2 here)
            // $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Lectures successfully uploaded!']);

            // Reset fields after submission
            $this->reset('lectures');
        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            // $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'An error occurred during upload. Please try again.']);
        }
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
