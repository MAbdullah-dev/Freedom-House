<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\ClassForm;
use App\Livewire\Dashboard\CreateCourse;
use App\Livewire\Dashboard\Lecture as LectureForm;
use App\Livewire\Dashboard\MyCourses;
use App\Livewire\Dashboard\ViewEnrollments;
use App\Livewire\Dashboard\ViewLectures;
use App\Livewire\Quizzes\QuizCreate;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Events;
use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Services\ResourceAuthorizer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;
use Vimeo\Laravel\Facades\Vimeo;

class OwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    private User $otherTutor;

    private User $assignedTutor;

    private User $admin;

    private User $learner;

    private Course $course;

    private Category $category;

    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        foreach ([Role::ADMIN, Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }

        $this->owner = $this->makeUser('owner-auth@example.test', Role::TUTOR);
        $this->otherTutor = $this->makeUser('other-auth@example.test', Role::TUTOR);
        $this->assignedTutor = $this->makeUser('assigned-auth@example.test', Role::TUTOR);
        $this->admin = $this->makeUser('admin-auth@example.test', Role::ADMIN);
        $this->learner = $this->makeUser('learner-auth@example.test', Role::LEARNER);
        $this->category = Category::query()->create(['category_name' => 'Ownership']);
        $this->tag = Tag::query()->create(['name' => 'Ownership Tag']);
        $this->course = $this->makeCourse($this->owner, 'Owned Course');

        DB::table('course_tutors')->insert([
            'course_id' => $this->course->id,
            'user_id' => $this->assignedTutor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_tutor_can_edit_their_own_course(): void
    {
        $this->fillCourseEditor($this->owner, $this->course, 'Renamed Owned Course')->call('updateCourse');

        $fresh = $this->course->fresh();
        $this->assertSame('Renamed Owned Course', $fresh->title);
        $this->assertSame($this->owner->id, $fresh->user_id);
        $this->assertTrue($fresh->tags->contains($this->tag));
    }

    public function test_tutor_cannot_edit_or_delete_another_tutors_course(): void
    {
        $this->fillCourseEditor($this->otherTutor, $this->course, 'Stolen Course')
            ->call('updateCourse')
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(MyCourses::class)
            ->set('courseId', $this->course->id)
            ->call('destroyCourse')
            ->assertForbidden();

        $this->assertSame($this->course->title, $this->course->fresh()->title);
        $this->assertNotNull(Course::query()->find($this->course->id));
    }

    public function test_tutor_cannot_modify_another_tutors_lectures_quizzes_or_vimeo_video(): void
    {
        Vimeo::shouldReceive('request')->never();
        Vimeo::shouldReceive('upload')->never();

        $lecture = Lecture::query()->create([
            'course_id' => $this->course->id,
            'title' => 'Owned Lecture',
            'video_file' => '/videos/owned',
            'order' => 1,
        ]);
        $ownCourse = $this->makeCourse($this->otherTutor, 'Other Tutor Course');
        Lecture::query()->create([
            'course_id' => $ownCourse->id,
            'title' => 'Other Lecture',
            'video_file' => '/videos/other',
            'order' => 1,
        ]);

        Livewire::actingAs($this->otherTutor)
            ->test(ViewLectures::class, ['courseId' => $this->course->id])
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(ViewLectures::class, ['courseId' => $ownCourse->id])
            ->set('selectedLectureId', $lecture->id)
            ->call('deleteLecture')
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(ViewLectures::class, ['courseId' => $ownCourse->id])
            ->call('deleteVimeoVideo', '/videos/owned')
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(QuizCreate::class, ['lectureId' => $lecture->id])
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(LectureForm::class)
            ->set('course_id', $this->course->id)
            ->assertForbidden();

        $this->assertNotNull(Lecture::query()->find($lecture->id));
        $this->assertSame(0, Quiz::query()->where('lecture_id', $lecture->id)->count());
    }

    public function test_tutor_can_open_their_own_lecture_and_quiz_form(): void
    {
        $lecture = Lecture::query()->create([
            'course_id' => $this->course->id,
            'title' => 'Editable Lecture',
            'order' => 1,
        ]);

        Livewire::actingAs($this->owner)
            ->test(ViewLectures::class, ['courseId' => $this->course->id])
            ->assertOk()
            ->call('editLecture', $lecture->id)
            ->assertSet('lectureTitle', 'Editable Lecture');

        Livewire::actingAs($this->owner)
            ->test(QuizCreate::class, ['lectureId' => $lecture->id])
            ->assertSet('lecture_id', $lecture->id)
            ->set('title', 'Owner Quiz')
            ->set('description', 'Quiz description')
            ->set('questions', [[
                'question_text' => 'Question',
                'points' => 1,
                'answers' => [
                    ['answer_text' => 'Yes', 'is_correct' => true],
                    ['answer_text' => 'No', 'is_correct' => false],
                ],
            ]])
            ->call('submit');

        $this->assertDatabaseHas('quizzes', [
            'lecture_id' => $lecture->id,
            'title' => 'Owner Quiz',
        ]);
    }

    public function test_tutor_cannot_modify_another_tutors_class_event_or_enrollments(): void
    {
        $class = $this->makeClass($this->owner, 'Owned Class');
        $event = $this->makeEvent($this->owner, 'Owned Event');
        $booking = Booking::query()->create([
            'class_id' => $class->id,
            'order_number' => 'OWN-'.bin2hex(random_bytes(4)),
            'email' => 'student@example.test',
            'phone' => '555',
            'payment_status' => 'free',
            'type' => 'manual',
        ]);

        Livewire::actingAs($this->otherTutor)
            ->test(ClassForm::class)
            ->set('classId', $class->id)
            ->set('title', 'Stolen Class')
            ->call('updateClass')
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(ClassForm::class)
            ->set('eventId', $event->id)
            ->set('title', 'Stolen Event')
            ->call('updateClass')
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(ViewEnrollments::class)
            ->call('selectItem', $class->id, 'class')
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(ViewEnrollments::class)
            ->set('selectedClass', $class)
            ->call('deleteItem')
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(ViewEnrollments::class)
            ->set('selectedModelId', $class->id)
            ->set('selectedModelType', 'class')
            ->set('newAttendees', [[
                'email' => 'intruder@example.test',
                'phone' => '555',
                'payment_status' => 'free',
            ]])
            ->call('saveManualAttendees')
            ->assertForbidden();

        Livewire::actingAs($this->otherTutor)
            ->test(ViewEnrollments::class)
            ->call('deleteAttendee', $booking->id)
            ->assertForbidden();

        $this->assertSame('Owned Class', $class->fresh()->title);
        $this->assertSame('Owned Event', $event->fresh()->title);
        $this->assertNotNull(Booking::query()->find($booking->id));
        $this->assertDatabaseMissing('bookings', ['email' => 'intruder@example.test']);
    }

    public function test_owner_can_update_a_class_and_assigned_tutors_cannot_manage_the_course(): void
    {
        $class = $this->makeClass($this->owner, 'Class To Rename');

        Livewire::actingAs($this->owner)
            ->test(ClassForm::class)
            ->set('classId', $class->id)
            ->set('title', 'Renamed Class')
            ->set('description', 'A longer class description')
            ->set('class_date', '2026-12-20')
            ->set('class_time', '10:00')
            ->set('booking_start_date', '2026-11-01')
            ->set('booking_end_date', '2026-12-01')
            ->set('class_type', 'onsite')
            ->set('course_id', null)
            ->call('updateClass');

        $this->assertSame('Renamed Class', $class->fresh()->title);
        $this->assertSame($this->owner->id, $class->fresh()->user_id);

        $authorizer = app(ResourceAuthorizer::class);
        $this->assertTrue($authorizer->isAssignedTutor($this->assignedTutor, $this->course));
        $this->assertFalse($authorizer->isAssignedTutor($this->assignedTutor, $this->makeCourse($this->owner, 'Unassigned Course')));

        Livewire::actingAs($this->assignedTutor)
            ->test(CreateCourse::class, ['courseId' => $this->course->id])
            ->assertForbidden();

        Livewire::actingAs($this->assignedTutor)
            ->test(MyCourses::class)
            ->set('courseId', $this->course->id)
            ->call('destroyCourse')
            ->assertForbidden();

        $this->assertSame($this->course->title, $this->course->fresh()->title);
    }

    public function test_admin_can_manage_another_tutors_course_and_learners_and_guests_stay_denied(): void
    {
        $this->fillCourseEditor($this->admin, $this->course, 'Admin Renamed Course')->call('updateCourse');
        $this->assertSame('Admin Renamed Course', $this->course->fresh()->title);

        Livewire::actingAs($this->learner)->test(MyCourses::class)->assertForbidden();
        Livewire::actingAs($this->learner)->test(ViewLectures::class, ['courseId' => $this->course->id])->assertForbidden();

        $this->actingAs($this->learner)->get(route('dashboard.create.class'))->assertForbidden();
        $this->actingAs($this->learner)->get(route('dashboard.create.event'))->assertForbidden();
    }

    public function test_guests_are_redirected_from_teaching_routes(): void
    {
        $this->get(route('dashboard.mycourses'))->assertRedirect(route('login'));
        $this->get(route('dashboard.create.class'))->assertRedirect(route('login'));
        $this->get(route('dashboard.create.event'))->assertRedirect(route('login'));
    }

    public function test_missing_ids_fail_safely_and_class_and_event_routes_are_distinct(): void
    {
        Livewire::actingAs($this->owner)
            ->test(MyCourses::class)
            ->set('courseId', 999999)
            ->call('destroyCourse')
            ->assertNotFound();

        Livewire::actingAs($this->owner)
            ->test(CreateCourse::class)
            ->set('courseId', 'not-a-course')
            ->call('loadCourseData')
            ->assertNotFound();

        $classUri = route('dashboard.create.class', ['classId' => 8]);
        $eventUri = route('dashboard.create.event', ['eventId' => 8]);

        $this->assertNotSame($classUri, $eventUri);
        $this->assertStringContainsString('/dashboard/create/class/8', $classUri);
        $this->assertStringContainsString('/dashboard/create/event/8', $eventUri);

        $class = $this->makeClass($this->owner, 'Routed Class');
        $event = $this->makeEvent($this->owner, 'Routed Event');

        $this->actingAs($this->owner)->get(route('dashboard.create.class', ['classId' => $class->id]))->assertOk();
        $this->actingAs($this->owner)->get(route('dashboard.create.event', ['eventId' => $event->id]))->assertOk();
        $this->actingAs($this->otherTutor)->get(route('dashboard.create.class', ['classId' => $class->id]))->assertForbidden();
        $this->actingAs($this->otherTutor)->get(route('dashboard.create.event', ['eventId' => $event->id]))->assertForbidden();
    }

    private function fillCourseEditor(User $user, Course $course, string $title)
    {
        return Livewire::actingAs($user)
            ->test(CreateCourse::class)
            ->set('courseId', $course->id)
            ->set('courseTitle', $title)
            ->set('description', 'Updated description')
            ->set('category', [$this->category->id])
            ->set('tags', [$this->tag->id])
            ->set('tutors', [])
            ->set('learnDetails', 'Outcomes')
            ->set('audienceDetails', 'Audience')
            ->set('requirements', 'Requirements')
            ->set('courseType', 'recorded')
            ->set('is_paid', 'free');
    }

    private function makeUser(string $email, string $roleName): User
    {
        return User::query()->create([
            'email' => $email,
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'role_id' => Role::findByName($roleName)->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
    }

    private function makeCourse(User $owner, string $title): Course
    {
        return Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'free',
            'title' => $title.' '.bin2hex(random_bytes(3)),
            'description' => 'Description',
            'thumbnail' => 'thumb.png',
            'video_path' => 'video.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
        ]);
    }

    private function makeClass(User $owner, string $title): Classe
    {
        return Classe::query()->create([
            'user_id' => $owner->id,
            'title' => $title,
            'description' => 'Class description',
            'visibility' => true,
            'class_type' => 'onsite',
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
            'booking_start_date' => '2026-11-01',
            'booking_end_date' => '2026-12-01',
        ]);
    }

    private function makeEvent(User $owner, string $title): Events
    {
        return Events::query()->create([
            'user_id' => $owner->id,
            'title' => $title,
            'description' => 'Event description',
            'capacity' => 10,
            'class_type' => 'onsite',
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
            'booking_start_date' => '2026-11-01',
            'booking_end_date' => '2026-12-01',
        ]);
    }
}
