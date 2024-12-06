<?php

namespace App\Livewire\Dashboard;

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

    protected $rules = [
        'course_id' => 'required|exists:courses,id',
        'lectures.*.title' => 'required|string|max:255',
        'lectures.*.description' => 'nullable|string',
        'lectures.*.video_file' => 'required|file|mimetypes:video/mp4,video/quicktime|max:51200', // Max 50MB
        'lectures.*.order' => 'nullable|integer|min:1',
    ];

    public function mount()
    {
        $this->courses = Course::where('is_published', true)
            ->where('is_drafted', false)
            ->where('course_type', 'recorded')
            ->get();

        // Initialize with one lecture input block
        $this->lectures[] = [
            'title' => '',
            'description' => '',
            'video_file' => '',
            'order' => '',
        ];
    }

    public function addLectureBlock()
    {
        $this->lectures[] = [
            'title' => '',
            'description' => '',
            'video_file' => '',
            'order' => '',
        ];
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
            // dd($response);

            // Delete temporary file
            Storage::disk('local')->delete($videoPath);

            // Create lecture record in the database
            ModelsLecture::create([
                'course_id' => $this->course_id,
                'title' => $lecture['title'],
                'description' => $lecture['description'],
                'video_file' => $response, // Vimeo video URL or ID
                'order' => $lecture['order'],
            ]);
        }

        $this->alert('success', 'Lectures successfully uploaded to Vimeo!');
        $this->resetFields();
    }

    private function resetFields()
    {
        $this->course_id = '';
        $this->lectures = [
            [
                'title' => '',
                'description' => '',
                'video_file' => '',
                'order' => '',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.lecture')->layout('components.layouts.dashboard');
    }
}
