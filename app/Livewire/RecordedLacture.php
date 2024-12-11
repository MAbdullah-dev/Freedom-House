<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Lecture; // Assuming there's a Lecture model for fetching lecture data
use Vimeo\Laravel\Facades\Vimeo;

class RecordedLacture extends Component
{
    public $courseId; // ID of the course
    public $currentLectureId; // ID of the currently selected lecture
    public $lectures; // Collection of lectures for the course
    public $currentLecture; // Current lecture details
    public $videoDetails; // Details of the video fetched from Vimeo

    /**
     * Mount method to initialize the component with course and lecture IDs.
     */
    public function mount($courseId, $lectureId = null)
    {
        $this->courseId = $courseId;
        $this->currentLectureId = $lectureId;

        // Fetch all lectures for the given course
        $this->lectures = Lecture::where('course_id', $this->courseId)->orderBy('order')->get();

        // Set the current lecture or default to the first lecture
        $this->currentLecture = $this->currentLectureId
            ? $this->lectures->firstWhere('id', $this->currentLectureId)
            : $this->lectures->first();

        // Fetch video details if there's a current lecture
        if ($this->currentLecture) {
            // dd($this->currentLecture->video_file);
            $this->fetchVideoDetails($this->currentLecture->video_file);
        }
    }

    /**
     * Change the current lecture and fetch its video details.
     */
    public function changeLecture($lectureId)
    {
        $this->currentLectureId = $lectureId;
        $this->currentLecture = $this->lectures->firstWhere('id', $lectureId);
        dd($this->currentLecture);

        if ($this->currentLecture) {
            $this->fetchVideoDetails($this->currentLecture->video_file);
        }
    }

    /**
     * Fetch video details from Vimeo using the video file ID.
     */
    private function fetchVideoDetails($videoId)
    {
        // dd($videoId);
        try {
            // Make the request to Vimeo
            $response = Vimeo::request("/videos/{$videoId}", [], 'GET');
            dd($response);
            // Check the response status code to determine success
            if ($response['status'] === 200) {
                $this->videoDetails = $response['body']; // Populate your property with video details
            } else {
                $this->videoDetails = null; // Handle cases where the response status isn't 200
            }
        } catch (\Exception $e) {
            // Log or handle the exception as needed
            $this->videoDetails = null; // Gracefully handle errors
            // \Log::error('Error fetching video from Vimeo: ' . $e->getMessage());
        }
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        return view('livewire.recorded-lacture');
    }
}
