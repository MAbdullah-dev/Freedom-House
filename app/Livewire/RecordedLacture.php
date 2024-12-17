<?php

namespace App\Livewire;

use App\Models\Certificate;
use App\Models\Course;
use Livewire\Component;
use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\VideoActivity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
    public $quiz;

    public function mount($courseId, $lectureId = null)
    {
        $this->courseId = $courseId;
        $this->currentLectureId = $lectureId;

        // Fetch all lectures for the given course, ordered by 'order'
        $this->lectures = Lecture::where('course_id', $this->courseId)->orderBy('order')->get();
        $this->quiz = Quiz::where('lecture_id', $this->currentLectureId)->first();
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
    public function checkCompletionAndGenerateCertificate()
    {
        $user = Auth::user();

        if (!$user) {
            $this->alert('error', 'User not found.');
            return;
        }

        // Get the total and watched lectures in one query
        $watchedLectures = VideoActivity::where('user_id', $user->id)
            ->whereIn('lecture_id', $this->lectures->pluck('id'))
            ->where('watched', 1)
            ->count();

        $totalLectures = $this->lectures->count();

        // Log the user's progress
        Log::info("User {$user->id}: Total Lectures: $totalLectures, Watched Lectures: $watchedLectures");

        // If all lectures are watched, check and generate certificate
        if ($totalLectures === $watchedLectures) {
            $existingCertificate = Certificate::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $this->courseId],
                ['certificate_path' => $this->generateCertificate($user, $this->courseId)]
            );

            // Check if the certificate already exists
            if ($existingCertificate->wasRecentlyCreated) {
                try {
                    $this->sendCertificateEmail($user, $existingCertificate->certificate_path);
                    $this->alert('success', 'Congratulations! Your certificate has been emailed.');
                } catch (\Exception $e) {
                    Log::error("Email Sending Error for User {$user->id}, Certificate {$existingCertificate->certificate_path}: " . $e->getMessage());
                    $this->alert('warning', 'Certificate generated but email could not be sent. Please contact support.');
                }
            } else {
                $this->alert('info', 'Your certificate has already been issued.');
            }
        } else {
            $this->alert('warning', 'You need to complete all lectures to earn a certificate.');
        }
    }

    private function generateCertificate($user, $courseId)
    {
        $course = Course::find($courseId);
        if (!$course) {
            throw new \Exception("Course with ID $courseId not found.");
        }

        try {
            // Generate PDF and save it in storage
            $pdf = Pdf::loadView('certificates.dummy', compact('user', 'course'));
            $certificatePath = 'certificates/' . $user->id . '_' . $course->id . '_' . now()->timestamp . '.pdf';

            // Ensure the directory exists and store the PDF
            Storage::disk('public')->put($certificatePath, $pdf->output());

            return Storage::url($certificatePath);
        } catch (\Exception $e) {
            Log::error("Certificate Generation Error for User {$user->id}, Course {$courseId}: " . $e->getMessage());
            throw $e;
        }
    }

    private function sendCertificateEmail($user, $certificatePath)
    {
        // Use public_path to get the absolute path for email attachment
        $certificateFullPath = public_path($certificatePath);

        // Send the certificate email
        Mail::raw("Congratulations on completing the course! Your certificate is attached.", function ($message) use ($user, $certificateFullPath) {
            $message->to($user->email)
                ->subject('Your Certificate of Completion')
                ->attach($certificateFullPath, [
                    'as' => 'Certificate.pdf',
                    'mime' => 'application/pdf',
                ]);
        });
    }

    public function sendCertificate()
    {
        $this->checkCompletionAndGenerateCertificate();
    }


    // Render the view
    public function render()
    {
        return view('livewire.recorded-lacture');
    }
}
