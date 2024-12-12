<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Lecture;
use App\Models\VideoActivity;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Vimeo\Laravel\Facades\Vimeo;

class RecordedLacture extends Component
{
    use LivewireAlert;

    public $courseId;
    public $currentLectureId;
    public $lectures;
    public $currentLecture;
    public $lecturescountcurrent;
    public $videoDetails = [];

    public function mount($courseId, $lectureId = null)
    {
        $this->courseId = $courseId;
        $this->currentLectureId = $lectureId;

        // Fetch all lectures for the given course, ordered by 'order'
        $this->lectures = Lecture::where('course_id', $this->courseId)->orderBy('order')->get();

        // Set the current lecture based on passed lectureId or default to the first lecture
        if ($this->currentLectureId) {
            $this->currentLecture = $this->lectures->firstWhere('id', $this->currentLectureId);
        } else {
            $this->currentLecture = $this->lectures->first();
        }

        // Assign the current lecture's order to the lecturescountcurrent
        if ($this->currentLecture) {
            $this->lecturescountcurrent = $this->currentLecture->order;
        }

        // Fetch video details for the current lecture
        if ($this->currentLecture) {
            $this->fetchVideoDetails($this->currentLecture->video_file);
        }
    }

    // Mark the video as watched for the user
    public function markAsWatched($lectureId)
    {
        $user = Auth::user();

        // Check if activity already exists for this user and lecture
        $activity = VideoActivity::where('user_id', $user->id)
            ->where('lecture_id', $lectureId)
            ->first();

        // If activity doesn't exist, create a new one
        if (!$activity) {
            VideoActivity::create([
                'user_id' => $user->id,
                'lecture_id' => $lectureId,
                'watched' => 1,  // Mark as fully watched
            ]);
            $this->alert('success', 'Video marked as watched!');
        } else {
            // If activity exists, update it
            $activity->watched = 1;
            $activity->save();
            $this->alert('success', 'Video watched successfully.');
        }
    }

    // Change to the next lecture
    public function nextLecture()
    {
        $nextLecture = $this->lectures->where('order', '>', $this->lecturescountcurrent)->sortBy('order')->first();

        if ($nextLecture) {
            $this->changeLecture($nextLecture->id);
        }
    }

    // Change to the previous lecture
    public function previousLecture()
    {
        $previousLecture = $this->lectures->where('order', '<', $this->lecturescountcurrent)->sortByDesc('order')->first();

        if ($previousLecture) {
            $this->changeLecture($previousLecture->id);
        }
    }

    // Change the current lecture and fetch its video details
    public function changeLecture($lectureId)
    {
        $this->currentLectureId = $lectureId;
        $this->currentLecture = $this->lectures->firstWhere('id', $lectureId);

        if ($this->currentLecture) {
            $this->fetchVideoDetails($this->currentLecture->video_file);
            $this->lecturescountcurrent = $this->currentLecture->order;
        }
    }

    // Fetch video details from Vimeo API
    private function fetchVideoDetails($videoId)
    {
        try {
            $response = Vimeo::request("{$videoId}", [], 'GET');
            $this->videoDetails = $response['body'];
            // dd($this->videoDetails);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
    public function pollVideoDuration()
    {
        $this->lecture->refresh(); // Reload the model
    }

    // Render the view
    public function render()
    {
        return view('livewire.recorded-lacture');
    }
}
