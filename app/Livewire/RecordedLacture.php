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
    public $progressPercentage;

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

        // Check if the user has watched each lecture
        $user = Auth::user();
        foreach ($this->lectures as $lecture) {
            $lecture->watched = VideoActivity::where('user_id', $user->id)
                ->where('lecture_id', $lecture->id)
                ->exists();
        }
        $this->progressPercentage = $this->getCourseProgress($courseId);
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
        $user = Auth::user();
        foreach ($this->lectures as $lecture) {
            $lecture->watched = VideoActivity::where('user_id', $user->id)
                ->where('lecture_id', $lecture->id)
                ->exists();
        }
        $this->progressPercentage = $this->getCourseProgress($this->courseId);
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
    public function getCourseProgress($courseId)
    {
        $user = Auth::user();

        // Get all lectures for the course
        $lectures = Lecture::where('course_id', $courseId)->get();

        // Total number of lectures in the course
        $totalLectures = $lectures->count();

        if ($totalLectures === 0) {
            return 0; // Avoid division by zero
        }

        // Count watched lectures by the user
        $watchedLectures = VideoActivity::where('user_id', $user->id)
            ->whereIn('lecture_id', $lectures->pluck('id'))
            ->count();

        // Calculate progress percentage
        $progress = ($watchedLectures / $totalLectures) * 100;

        return round($progress, 2); // Return progress rounded to 2 decimal places
    }


    // Render the view
    public function render()
    {
        return view('livewire.recorded-lacture');
    }
}
