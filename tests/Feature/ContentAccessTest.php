<?php

namespace Tests\Feature;

use App\Livewire\Quizzes\QuizView;
use App\Livewire\RecordedLecture;
use App\Models\Booking;
use App\Models\Classe;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\QuizSubmission;
use App\Models\Role;
use App\Models\User;
use App\Models\VideoActivity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleComponents\Checksum;
use Livewire\Mechanisms\HandleComponents\HandleComponents;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Vimeo\Laravel\Facades\Vimeo;

class ContentAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const VIDEO_FILE = '/videos/secret-lecture-file-1o';

    private const LECTURE_DESCRIPTION = 'secret-lecture-description-1o';

    private const ANSWER_TEXT = 'secret-correct-answer-1o';

    private User $owner;

    private User $otherTutor;

    private User $admin;

    private User $learner;

    private Course $course;

    private Course $otherCourse;

    private Lecture $lecture;

    private Lecture $otherLecture;

    private Quiz $quiz;

    private Quiz $otherQuiz;

    private QuizAnswer $correctAnswer;

    private QuizAnswer $wrongAnswer;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        foreach ([Role::ADMIN, Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }

        $this->owner = $this->makeUser('owner-content@example.test', Role::TUTOR);
        $this->otherTutor = $this->makeUser('other-content@example.test', Role::TUTOR);
        $this->admin = $this->makeUser('admin-content@example.test', Role::ADMIN);
        $this->learner = $this->makeUser('learner-content@example.test', Role::LEARNER);
        $this->course = $this->makeCourse($this->owner, 'Protected Course', 'free');
        $this->otherCourse = $this->makeCourse($this->owner, 'Other Course', 'paid');
        $this->lecture = $this->makeLecture($this->course, 'Protected Lecture');
        $this->otherLecture = $this->makeLecture($this->otherCourse, 'Other Lecture');
        [$this->quiz, $this->correctAnswer, $this->wrongAnswer] = $this->makeQuiz($this->lecture, 'Protected Quiz');
        [$this->otherQuiz] = $this->makeQuiz($this->otherLecture, 'Other Quiz');
    }

    public function test_guests_are_redirected_and_public_catalogue_pages_stay_available(): void
    {
        $this->get(route('recorded.lecture', ['courseId' => $this->course->id, 'lectureId' => $this->lecture->id]))
            ->assertRedirect(route('login'));
        $this->get(route('quiz.take', ['quizId' => $this->quiz->id]))
            ->assertRedirect(route('login'));

        $this->get('/')->assertOk();
        $this->get('/course/catalogue')->assertOk();
        $this->get(route('course.details', ['id' => $this->course->id]))->assertOk();
        $this->get(route('onsite.course.details', ['id' => $this->course->id]))
            ->assertOk()
            ->assertSee($this->course->title)
            ->assertDontSee(self::VIDEO_FILE)
            ->assertDontSee(self::ANSWER_TEXT);
    }

    public function test_guest_livewire_calls_cannot_load_protected_content(): void
    {
        Vimeo::shouldReceive('request')->never();

        Livewire::test(RecordedLecture::class, [
            'courseId' => $this->course->id,
            'lectureId' => $this->lecture->id,
        ])->assertForbidden()->assertDontSee(self::VIDEO_FILE)->assertDontSee(self::LECTURE_DESCRIPTION);

        Livewire::test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertForbidden()
            ->assertDontSee(self::ANSWER_TEXT);

        $this->assertSame(0, QuizSubmission::query()->count());
        $this->assertSame(0, VideoActivity::query()->count());
    }

    public function test_learner_without_enrollment_is_denied_even_when_the_course_is_free(): void
    {
        Vimeo::shouldReceive('request')->never();

        $this->actingAs($this->learner)
            ->get(route('recorded.lecture', ['courseId' => $this->course->id, 'lectureId' => $this->lecture->id]))
            ->assertForbidden()
            ->assertDontSee(self::VIDEO_FILE)
            ->assertDontSee(self::LECTURE_DESCRIPTION);

        $this->actingAs($this->learner)
            ->get(route('quiz.take', ['quizId' => $this->quiz->id]))
            ->assertForbidden()
            ->assertDontSee(self::ANSWER_TEXT);

        Livewire::actingAs($this->learner)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertForbidden();

        $this->assertSame(0, QuizSubmission::query()->count());
        $this->assertSame(0, CourseProgress::query()->where('user_id', $this->learner->id)->count());
    }

    public function test_learner_with_a_paid_booking_can_open_the_lecture_and_quiz(): void
    {
        $this->allowVideo();
        $this->book($this->learner, $this->course, 'paid');

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->assertOk()
            ->assertSee('Protected Lecture');

        Livewire::actingAs($this->learner)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertOk()
            ->assertSee('Protected Quiz')
            ->assertSee(self::ANSWER_TEXT)
            ->set('answers.0', $this->correctAnswer->id)
            ->call('submit');

        $this->assertDatabaseHas('quiz_submissions', [
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->learner->id,
            'score' => 5,
        ]);
    }

    public function test_learner_with_a_free_booking_can_open_protected_content(): void
    {
        $this->allowVideo();
        $this->book($this->learner, $this->course, 'free');

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->assertOk()
            ->assertSee('Protected Lecture');

        Livewire::actingAs($this->learner)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertOk()
            ->assertSee(self::ANSWER_TEXT);
    }

    public function test_pending_unpaid_and_refunded_bookings_do_not_grant_access(): void
    {
        Vimeo::shouldReceive('request')->never();

        foreach (['pending', 'unpaid', 'refunded'] as $status) {
            $learner = $this->makeUser($status.'-content@example.test', Role::LEARNER);
            $this->book($learner, $this->course, $status);

            Livewire::actingAs($learner)
                ->test(RecordedLecture::class, [
                    'courseId' => $this->course->id,
                    'lectureId' => $this->lecture->id,
                ])
                ->assertForbidden()
                ->assertDontSee(self::VIDEO_FILE);

            Livewire::actingAs($learner)
                ->test(QuizView::class, ['quizId' => $this->quiz->id])
                ->assertForbidden()
                ->assertDontSee(self::ANSWER_TEXT);
        }

        $this->assertSame(0, QuizSubmission::query()->count());
    }

    public function test_a_booking_for_another_course_does_not_grant_access(): void
    {
        Vimeo::shouldReceive('request')->never();
        $this->book($this->learner, $this->otherCourse, 'paid');

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->assertForbidden()
            ->assertDontSee(self::LECTURE_DESCRIPTION);

        Livewire::actingAs($this->learner)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertForbidden()
            ->assertDontSee(self::ANSWER_TEXT);
    }

    public function test_a_paid_class_booking_for_the_course_grants_access(): void
    {
        $this->allowVideo();
        $class = Classe::query()->create([
            'user_id' => $this->owner->id,
            'course_id' => $this->course->id,
            'title' => 'Linked Class',
            'description' => 'Class description',
            'visibility' => true,
            'class_type' => 'onsite',
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
            'booking_start_date' => '2026-11-01',
            'booking_end_date' => '2026-12-01',
        ]);
        Booking::query()->create([
            'user_id' => $this->learner->id,
            'class_id' => $class->id,
            'course_id' => null,
            'payment_status' => 'paid',
            'order_number' => 'class-'.bin2hex(random_bytes(4)),
        ]);

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->assertOk()
            ->assertSee('Protected Lecture');
    }

    public function test_course_owner_can_preview_without_a_booking(): void
    {
        $this->allowVideo();

        Livewire::actingAs($this->owner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->assertOk()
            ->assertSee('Protected Lecture');

        Livewire::actingAs($this->owner)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertOk()
            ->assertSee(self::ANSWER_TEXT);
    }

    public function test_unrelated_tutor_is_denied_without_enrollment(): void
    {
        Vimeo::shouldReceive('request')->never();

        Livewire::actingAs($this->otherTutor)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->assertForbidden()
            ->assertDontSee(self::VIDEO_FILE);

        Livewire::actingAs($this->otherTutor)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertForbidden()
            ->assertDontSee(self::ANSWER_TEXT);
    }

    public function test_admin_can_access_content_without_a_booking(): void
    {
        $this->allowVideo();

        Livewire::actingAs($this->admin)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->assertOk()
            ->assertSee('Protected Lecture');

        Livewire::actingAs($this->admin)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertOk()
            ->assertSee(self::ANSWER_TEXT);
    }

    public function test_missing_lecture_or_quiz_fails_safely(): void
    {
        Vimeo::shouldReceive('request')->never();
        $this->book($this->learner, $this->course, 'paid');

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => 999999,
            ])
            ->assertNotFound();

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->otherCourse->id,
                'lectureId' => $this->lecture->id,
            ])
            ->assertNotFound();

        Livewire::actingAs($this->learner)
            ->test(QuizView::class, ['quizId' => 999999])
            ->assertNotFound();

        Livewire::actingAs($this->learner)
            ->test(QuizView::class, ['quizId' => 'not-a-quiz'])
            ->assertNotFound();
    }

    public function test_direct_livewire_calls_cannot_bypass_authorization(): void
    {
        $this->allowVideo();
        $this->book($this->learner, $this->course, 'paid');

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->call('markAsWatched', $this->otherLecture->id)
            ->assertForbidden();

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->call('getCourseProgress', $this->otherCourse->id)
            ->assertForbidden();

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->set('currentLectureId', $this->otherLecture->id)
            ->assertForbidden()
            ->assertDontSee(self::VIDEO_FILE);

        Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->set('courseId', $this->otherCourse->id)
            ->set('rating', 5)
            ->set('review', 'Trying to review another course')
            ->call('submitReview');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $this->learner->id,
            'course_id' => $this->otherCourse->id,
        ]);

        $quizComponent = Livewire::actingAs($this->learner)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertOk();

        $snapshot = $quizComponent->snapshot;
        $snapshot['data']['quiz'][1]['key'] = $this->otherQuiz->id;
        unset($snapshot['checksum']);
        $snapshot['checksum'] = Checksum::generate($snapshot);

        try {
            app(HandleComponents::class)->update($snapshot, [], [[
                'method' => 'submit',
                'params' => [],
                'path' => '',
            ]]);
            $this->fail('A swapped quiz id was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertStringNotContainsString(self::ANSWER_TEXT, $exception->getMessage());
        }

        Livewire::actingAs($this->learner)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->set('answers.0', $this->wrongAnswer->id)
            ->call('submit');

        $this->assertSame(0, VideoActivity::query()->where('lecture_id', $this->otherLecture->id)->count());
        $this->assertSame(0, CourseProgress::query()->where('course_id', $this->otherCourse->id)->count());
        $this->assertSame(0, QuizSubmission::query()->where('quiz_id', $this->otherQuiz->id)->count());
        $this->assertDatabaseHas('quiz_submissions', [
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->learner->id,
            'score' => 0,
        ]);
    }

    public function test_refunding_a_paid_booking_revokes_access(): void
    {
        $this->allowVideo();
        $booking = $this->book($this->learner, $this->course, 'paid');

        $lecture = Livewire::actingAs($this->learner)
            ->test(RecordedLecture::class, [
                'courseId' => $this->course->id,
                'lectureId' => $this->lecture->id,
            ])
            ->assertOk();

        $quiz = Livewire::actingAs($this->learner)
            ->test(QuizView::class, ['quizId' => $this->quiz->id])
            ->assertOk();

        $booking->update(['payment_status' => 'refunded']);

        $lecture->call('markAsWatched', $this->lecture->id)
            ->assertForbidden()
            ->assertDontSee(self::VIDEO_FILE);
        $quiz->call('submit')
            ->assertForbidden()
            ->assertDontSee(self::ANSWER_TEXT);

        $this->assertSame(0, VideoActivity::query()->where('user_id', $this->learner->id)->count());
        $this->assertSame(0, QuizSubmission::query()->where('user_id', $this->learner->id)->count());
    }

    private function allowVideo(): void
    {
        Vimeo::shouldReceive('request')->andReturn([
            'body' => ['uri' => '/videos/12345', 'name' => 'Authorized video'],
        ]);
    }

    private function book(User $user, Course $course, string $status): Booking
    {
        return Booking::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payment_status' => $status,
            'order_number' => $status.'-'.bin2hex(random_bytes(4)),
        ]);
    }

    private function makeUser(string $email, string $roleName): User
    {
        return User::query()->create([
            'email' => str_replace('@', '-'.bin2hex(random_bytes(3)).'@', $email),
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'role_id' => Role::findByName($roleName)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
    }

    private function makeCourse(User $owner, string $title, string $isPaid): Course
    {
        return Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => $isPaid,
            'title' => $title.' '.bin2hex(random_bytes(3)),
            'description' => 'Public course description',
            'thumbnail' => 'thumb.png',
            'video_path' => 'promo.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
        ]);
    }

    private function makeLecture(Course $course, string $title): Lecture
    {
        return Lecture::query()->create([
            'course_id' => $course->id,
            'title' => $title,
            'description' => self::LECTURE_DESCRIPTION,
            'video_file' => self::VIDEO_FILE,
            'order' => 1,
        ]);
    }

    private function makeQuiz(Lecture $lecture, string $title): array
    {
        $quiz = Quiz::query()->create([
            'lecture_id' => $lecture->id,
            'title' => $title,
            'description' => 'Quiz description',
        ]);
        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Which answer is protected?',
            'points' => 5,
        ]);
        $correct = QuizAnswer::query()->create([
            'question_id' => $question->id,
            'answer_text' => self::ANSWER_TEXT,
            'is_correct' => true,
        ]);
        $wrong = QuizAnswer::query()->create([
            'question_id' => $question->id,
            'answer_text' => 'secret-wrong-answer-1o',
            'is_correct' => false,
        ]);

        return [$quiz, $correct, $wrong];
    }
}
