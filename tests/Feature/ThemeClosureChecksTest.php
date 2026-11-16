<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\CreateCourse;
use App\Livewire\Quizzes\QuizView;
use App\Livewire\RecordedLecture;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\QuizSubmission;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\VideoActivity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Vimeo\Laravel\Facades\Vimeo;

class ThemeClosureChecksTest extends TestCase
{
    use DatabaseTransactions;

    public function test_course_thumbnail_validation_rejects_a_text_file_and_stores_a_png(): void
    {
        Storage::fake('public');
        $tutor = $this->makeUser(Role::TUTOR);
        $course = $this->makeCourse($tutor);
        $category = Category::query()->create(['category_name' => 'Closure '.uniqid()]);
        $tag = Tag::query()->create(['name' => 'closure-'.uniqid()]);

        Livewire::actingAs($tutor)
            ->test(CreateCourse::class, ['courseId' => $course->id])
            ->set('file', UploadedFile::fake()->create('notes.txt', 20, 'text/plain'))
            ->call('updateCourse')
            ->assertHasErrors(['file']);

        $this->assertSame('thumb.png', $course->fresh()->thumbnail);

        Livewire::actingAs($tutor)
            ->test(CreateCourse::class, ['courseId' => $course->id])
            ->set('category', [$category->id])
            ->set('tags', [$tag->id])
            ->set('file', UploadedFile::fake()->createWithContent(
                'thumb.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
            ))
            ->call('updateCourse')
            ->assertHasNoErrors();

        $stored = $course->fresh()->thumbnail;
        $this->assertStringStartsWith('uploads/', $stored);
        Storage::disk('public')->assertExists($stored);
    }

    public function test_an_enrolled_learner_can_record_lecture_progress_without_a_live_video_call(): void
    {
        Mail::fake();
        Vimeo::shouldReceive('request')->andReturn([
            'body' => ['uri' => '/videos/12345', 'name' => 'Mocked video'],
        ]);

        $learner = $this->makeUser(Role::LEARNER);
        $course = $this->makeCourse($learner);
        $first = $this->makeLecture($course, 'First lecture', 1);
        $this->makeLecture($course, 'Second lecture', 2);
        Booking::query()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'payment_status' => 'free',
            'order_number' => 'progress-'.bin2hex(random_bytes(4)),
        ]);

        Livewire::actingAs($learner)
            ->test(RecordedLecture::class, [
                'courseId' => $course->id,
                'lectureId' => $first->id,
            ])
            ->call('markAsWatched', $first->id)
            ->assertSet('progressPercentage', 50);

        $this->assertDatabaseHas('video_activities', [
            'user_id' => $learner->id,
            'lecture_id' => $first->id,
            'watched' => 1,
        ]);
        $this->assertSame(1, VideoActivity::query()->where('user_id', $learner->id)->count());
        $this->assertSame(50.0, (float) CourseProgress::query()->where('user_id', $learner->id)->value('progress'));
        $this->assertSame(0, Certificate::query()->count());
    }

    public function test_an_enrolled_learner_can_still_submit_a_quiz(): void
    {
        Vimeo::shouldReceive('request')->never();
        $learner = $this->makeUser(Role::LEARNER);
        $course = $this->makeCourse($learner);
        $lecture = $this->makeLecture($course, 'Quiz lecture', 1);
        $quiz = Quiz::query()->create([
            'lecture_id' => $lecture->id,
            'title' => 'Closure quiz',
            'description' => 'Answer the question.',
        ]);
        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Which answer is stored?',
            'points' => 3,
        ]);
        $answer = QuizAnswer::query()->create([
            'question_id' => $question->id,
            'answer_text' => 'Stored answer',
            'is_correct' => true,
        ]);
        Booking::query()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'payment_status' => 'free',
            'order_number' => 'quiz-'.bin2hex(random_bytes(4)),
        ]);

        Livewire::actingAs($learner)
            ->test(QuizView::class, ['quizId' => $quiz->id])
            ->set('answers.0', $answer->id)
            ->call('submit');

        $this->assertDatabaseHas('quiz_submissions', [
            'quiz_id' => $quiz->id,
            'user_id' => $learner->id,
            'score' => 3,
        ]);
        $this->assertSame(1, QuizSubmission::query()->count());
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['role_name' => $roleName]);

        return User::query()->create([
            'email' => strtolower($roleName).'-closure-'.uniqid().'@example.test',
            'first_name' => 'Closure',
            'last_name' => $roleName,
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }

    private function makeCourse(User $owner): Course
    {
        return Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'free',
            'title' => 'Closure '.uniqid(),
            'description' => 'Stored description',
            'thumbnail' => 'thumb.png',
            'video_path' => 'video.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
            'price' => 0,
        ]);
    }

    private function makeLecture(Course $course, string $title, int $order): Lecture
    {
        return Lecture::query()->create([
            'course_id' => $course->id,
            'title' => $title,
            'description' => 'Lecture description',
            'video_file' => '/videos/mocked',
            'order' => $order,
        ]);
    }
}
