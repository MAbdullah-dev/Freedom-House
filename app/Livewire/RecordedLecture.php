<?php

namespace App\Livewire;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseProgress;
use Livewire\Component;
use App\Models\Lecture;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\Review;
use App\Models\VideoActivity;
use App\Services\ContentAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Livewire\Concerns\LivewireAlert;
use Vimeo\Laravel\Facades\Vimeo;

class RecordedLecture extends Component
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
    public $rating;
    public $review;
    public $showReviewModal = false;
    public  $certificateData = null;
    public $cetificatepathformodal;
    public $allLecturesWatched = false;


    public function mount($courseId, $lectureId = null)
    {
        $authorized = $this->guardLecture($lectureId, $courseId);
        $this->currentLectureId = $authorized->id;

        // Fetch all lectures for the authorized course, ordered by 'order'
        $this->lectures = Lecture::where('course_id', $authorized->course_id)->orderBy('order')->get();
        $this->quiz = Quiz::where('lecture_id', $authorized->id)->first();
        $this->currentLecture = $this->lectures->firstWhere('id', $authorized->id);

        // Assign the current lecture's order to the lecturescountcurrent
        if ($this->currentLecture) {
            $this->lecturescountcurrent = $this->currentLecture->order;
            $this->fetchVideoDetails($authorized->video_file);
        }

        // Check if the user has watched each lecture
        $user = Auth::user();
        foreach ($this->lectures as $lecture) {
            $lecture->watched = VideoActivity::where('user_id', $user->id)
                ->where('lecture_id', $lecture->id)
                ->exists();
        }

        $this->progressPercentage = $this->getCourseProgress($authorized->course_id);
    }

    public function setRating($value)
    {
        $this->guardLecture();
        $this->rating = $value;
        $user = Auth::user();
        foreach ($this->lectures as $lecture) {
            $lecture->watched = VideoActivity::where('user_id', $user->id)
                ->where('lecture_id', $lecture->id)
                ->exists();
        }
    }

    // Mark the video as watched for the user
    public function markAsWatched($lectureId)
    {
        $authorized = $this->guardLecture($lectureId);
        $user = Auth::user();

        // Mark the lecture as watched
        $activity = VideoActivity::where('user_id', $user->id)
            ->where('lecture_id', $authorized->id)
            ->first();

        if (!$activity) {
            VideoActivity::create([
                'user_id' => $user->id,
                'lecture_id' => $authorized->id,
                'watched' => 1,
            ]);
        } else {
            $activity->watched = 1;
            $activity->save();
        }

        $this->alert('success', 'Video marked as watched!');

        // Refresh watched status for all lectures
        foreach ($this->lectures as $lecture) {
            $lecture->watched = VideoActivity::where('user_id', $user->id)
                ->where('lecture_id', $lecture->id)
                ->exists();
        }

        // Update the course progress
        $this->progressPercentage = $this->getCourseProgress($authorized->course_id);

        // Trigger certificate generation only if the user is on the last lecture and progress is 100%
        if ($this->isLastLecture() && $this->progressPercentage == 100) {
            $this->checkCompletionAndGenerateCertificate();
        }

        // Optionally, show the review modal on the last lecture
        if ($this->isLastLecture() && $this->progressPercentage == 100) {
            $this->checkCompletionAndRequestReview();
        }
    }
    private function isLastLecture()
    {
        return $this->currentLectureId === $this->lectures->last()->id;
    }



    // Method to open the modal when the user completes all lectures
    public function checkCompletionAndRequestReview()
    {
        $authorized = $this->guardLecture();
        $this->lectures = Lecture::where('course_id', $authorized->course_id)->orderBy('order')->get();
        $user = Auth::user();

        // Check if the user has already reviewed this course
        $existingReview = Review::where('user_id', $user->id)
            ->where('course_id', $this->courseId)
            ->exists();

        if ($existingReview) {
            // If the user has already submitted a review, do not dispatch the modal event
            return;
        }

        // Retrieve the lecture IDs to avoid repeated database queries in the loop
        $lectureIds = $this->lectures->pluck('id')->toArray();
        $totalLectures = count($lectureIds); // Count directly from the array

        // Check how many lectures the user has watched
        $watchedLectures = VideoActivity::where('user_id', $user->id)
            ->whereIn('lecture_id', $lectureIds)
            ->where('watched', 1)
            ->count();

        // If all lectures are watched and no review exists, trigger the review modal
        if ($totalLectures === $watchedLectures) {
            $this->dispatch('open-review-modal');
        }
    }

    public function submitReview()
    {
        $lecture = $this->guardLecture();
        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|min:5|max:50',
        ]);

        // Save review to the database
        \App\Models\Review::create([
            'user_id' => Auth::id(),
            'course_id' => $lecture->course_id,
            'rating' => $this->rating,
            'review' => $this->review,
        ]);

        // Close the modal and reset form
        $this->reset(['rating', 'review']);
        $this->cetificatepathformodal = Certificate::where('user_id', Auth::id())->pluck('certificate_path');
        if ($this->cetificatepathformodal) {
            $this->cetificatepathformodal = $this->cetificatepathformodal->first();
        }
        $this->dispatch('close-review-modal');
        $this->dispatch('congratulation');
        $this->alert('success', 'Thank you for your review!');
        $user = Auth::user();
        foreach ($this->lectures as $lecture) {
            $lecture->watched = VideoActivity::where('user_id', $user->id)
                ->where('lecture_id', $lecture->id)
                ->exists();
        }
    }

    // Change to the next lecture
    public function nextLecture()
    {
        $current = $this->guardLecture();
        $this->lectures = Lecture::where('course_id', $current->course_id)->orderBy('order')->get();
        $this->lecturescountcurrent = $current->order;
        $nextLecture = $this->lectures->where('order', '>', $this->lecturescountcurrent)->sortBy('order')->first();

        if ($nextLecture) {
            $this->changeLecture($nextLecture->id);
        }
    }

    // Change to the previous lecture
    public function previousLecture()
    {
        $current = $this->guardLecture();
        $this->lectures = Lecture::where('course_id', $current->course_id)->orderBy('order')->get();
        $this->lecturescountcurrent = $current->order;
        $previousLecture = $this->lectures->where('order', '<', $this->lecturescountcurrent)->sortByDesc('order')->first();

        if ($previousLecture) {
            $this->changeLecture($previousLecture->id);
        }
    }

    // Change the current lecture and fetch its video details
    public function changeLecture($lectureId)
    {
        $authorized = $this->guardLecture($lectureId);
        $this->currentLectureId = $authorized->id;
        $this->lectures = Lecture::where('course_id', $authorized->course_id)->orderBy('order')->get();
        $this->currentLecture = $this->lectures->firstWhere('id', $authorized->id);

        if ($this->currentLecture) {
            $this->fetchVideoDetails($authorized->video_file);
            $this->lecturescountcurrent = $this->currentLecture->order;
        }
    }

    // Fetch video details from Vimeo API
    private function fetchVideoDetails($videoId)
    {
        try {
            $response = Vimeo::request("{$videoId}", [], 'GET');
            $this->videoDetails = is_array($response['body'] ?? null) ? $response['body'] : [];
        } catch (\Throwable $e) {
            Log::warning('Recorded lecture video details could not be loaded.', ['exception' => $e::class]);
            $this->videoDetails = [];
            $this->alert('warning', 'The video details could not be loaded.');
        }
    }
    public function pollVideoDuration()
    {
        $this->guardLecture();
        $this->lecture->refresh(); // Reload the model
    }
    public function getCourseProgress($courseId)
    {
        $lecture = $this->guardLecture();
        if (! is_numeric($courseId) || (int) $courseId !== (int) $lecture->course_id) {
            abort(403);
        }
        $courseId = $lecture->course_id;
        $user = Auth::user();

        // Get the total number of lectures in the course
        $lectures = Lecture::where('course_id', $courseId)->pluck('id');
        $totalLectures = $lectures->count();

        if ($totalLectures === 0) {
            $this->allLecturesWatched = false;
            return 0; // Avoid division by zero
        }

        // Count watched lectures by the user
        $watchedLectures = VideoActivity::where('user_id', $user->id)
            ->whereIn('lecture_id', $lectures)
            ->where('watched', 1)
            ->count();

        // Check if all lectures are watched
        $this->allLecturesWatched = ($watchedLectures === $totalLectures);

        // Calculate progress percentage
        $progress = ($watchedLectures / $totalLectures) * 100;
        $progress = round($progress, 2); // Round to 2 decimal places

        // Save the progress in the database
        CourseProgress::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $courseId],
            ['progress' => $progress]
        );

        return $progress;
    }




    public function checkCompletionAndGenerateCertificate()
    {
        $authorized = $this->guardLecture();
        $this->lectures = Lecture::where('course_id', $authorized->course_id)->orderBy('order')->get();
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
            // Check if the certificate already exists
            $existingCertificate = Certificate::where('user_id', $user->id)
                ->where('course_id', $this->courseId)
                ->first();

            if ($existingCertificate) {
                // If the certificate exists, alert the user
                $this->alert('info', 'Your certificate has already been generated.');
            } else {
                // Generate the certificate and store its path in the database
                $certificatePath = $this->generateCertificate($user, $this->courseId);

                // Store the certificate in the database if it's newly generated
                Certificate::create([
                    'user_id' => $user->id,
                    'course_id' => $this->courseId,
                    'certificate_path' => $certificatePath,
                ]);

                // Send the certificate emailcerev
                try {
                    $this->sendCertificateEmail($user, $certificatePath);
                    $this->alert('success', 'Congratulations! Your certificate has been generated and emailed to you.');
                } catch (\Exception $e) {
                    Log::error("Email Sending Error for User {$user->id}, Certificate Path: {$certificatePath}: " . $e->getMessage());
                    $this->alert('warning', 'Certificate generated but email could not be sent. Please contact support.');
                }
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
            $pdf = Pdf::loadView('certificates.dummy', compact('user', 'course'))
                ->setPaper('a4', 'landscape') // Set the paper size and orientation
                ->setOptions([
                    'margin-left' => 10,
                    'margin-right' => 10,
                    'margin-top' => 10,
                    'margin-bottom' => 10,
                ]);

            $certificatePath = 'certificates/' . $user->id . '_' . $course->id . '_' . now()->timestamp . '.pdf';
            if ($pdf != null) {
                $this->certificateData = base64_encode($pdf->output());
            }
            Storage::disk('public')->put($certificatePath, $pdf->output());
            return Storage::url($certificatePath);
        } catch (\Exception $e) {
            Log::error("Certificate Generation Error for User {$user->id}, Course {$courseId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function showcertificate()
    {
        $this->guardLecture();
        $this->cetificatepathformodal = Certificate::where('user_id', Auth::id())->pluck('certificate_path');
        $this->cetificatepathformodal = $this->cetificatepathformodal->first();
        $this->dispatch('showCertificateModal');
        // $this->dispatch('congratulation');
    }
    public function assigncertificate()
    {
        $this->guardLecture();
        $this->cetificatepathformodal = Certificate::where('user_id', Auth::id())->pluck('certificate_path');
        $this->cetificatepathformodal = $this->cetificatepathformodal->first();
        $this->dispatch('close-review-modal');
        $this->dispatch('congratulation');
    }

    private function sendCertificateEmail($user, $certificatePath)
    {
        Mail::to($user->email)->send(new \App\Mail\CertificateMail($user, public_path($certificatePath)));
    }

    public function sendCertificate()
    {
        $this->guardLecture();
        $user = Auth::user();
        foreach ($this->lectures as $lecture) {
            $lecture->watched = VideoActivity::where('user_id', $user->id)
                ->where('lecture_id', $lecture->id)
                ->exists();
        }
        $this->checkCompletionAndGenerateCertificate();
    }

    // Render the view
    public function render()
    {
        $this->guardLecture();

        return view('livewire.recorded-lecture');
    }

    private function guardLecture(mixed $lectureId = null, mixed $courseId = null): Lecture
    {
        $lecture = app(ContentAccess::class)->authorizeRecordedLecture(
            Auth::user(),
            $courseId,
            $lectureId ?? $this->currentLectureId
        );
        $this->courseId = $lecture->course_id;
        $this->currentLectureId = $lecture->id;
        $this->lectures = Lecture::query()
            ->where('course_id', $lecture->course_id)
            ->orderBy('order')
            ->get();
        $userId = Auth::id();
        foreach ($this->lectures as $item) {
            $item->watched = VideoActivity::query()
                ->where('user_id', $userId)
                ->where('lecture_id', $item->id)
                ->exists();
        }
        $this->currentLecture = $this->lectures->firstWhere('id', $lecture->id) ?? $lecture;
        $this->lecturescountcurrent = $this->currentLecture->order;
        $this->fetchVideoDetails($lecture->video_file);

        return $lecture;
    }
}
