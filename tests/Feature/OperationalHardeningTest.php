<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\ViewEnrollments;
use App\Livewire\Inc\DashboardHead;
use App\Livewire\Inc\DashbordSidebar;
use App\Livewire\RecordedLecture;
use App\Models\Booking;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Lecture;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Vimeo\Laravel\Facades\Vimeo;
use Tests\TestCase;

class OperationalHardeningTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([Role::ADMIN, Role::TUTOR, Role::LEARNER] as $roleName) {
            Role::query()->firstOrCreate(['role_name' => $roleName]);
        }
    }

    public function test_public_certificate_route_is_not_available(): void
    {
        $this->get('/certificate')->assertNotFound();

        $admin = $this->makeUser('certificate-admin@example.test', Role::ADMIN);
        $this->actingAs($admin)->get('/certificate')->assertNotFound();
    }

    public function test_paid_manual_attendance_without_a_user_does_not_grant_content(): void
    {
        Http::preventStrayRequests();
        $tutor = $this->makeUser('manual-tutor@example.test', Role::TUTOR);
        $learner = $this->makeUser('manual-learner@example.test', Role::LEARNER);
        $course = $this->makeCourse($tutor);
        $lecture = Lecture::query()->create([
            'course_id' => $course->id,
            'title' => 'Protected lecture',
            'description' => 'Lecture description',
            'video_file' => 'video-1',
            'order' => 1,
        ]);
        $class = Classe::query()->create([
            'user_id' => $tutor->id,
            'course_id' => $course->id,
            'microsoft_event_id' => 'manual-event',
            'title' => 'Manual class',
            'description' => 'Class description',
            'visibility' => true,
            'class_type' => 'onsite',
            'is_paid' => 'paid',
            'price' => 10,
            'capacity' => 5,
            'class_date' => '2026-12-20',
            'class_time' => '10:00:00',
            'booking_start_date' => '2026-11-01',
            'booking_end_date' => '2026-12-01',
        ]);

        Livewire::actingAs($tutor)
            ->test(ViewEnrollments::class)
            ->set('selectedModelId', $class->id)
            ->set('selectedModelType', 'class')
            ->set('newAttendees', [[
                'email' => 'walkin@example.test',
                'phone' => '5551234',
                'payment_status' => 'paid',
                'name' => 'Walk In',
            ]])
            ->call('saveManualAttendees');

        $booking = Booking::query()->where('email', 'walkin@example.test')->first();
        $this->assertNotNull($booking);
        $this->assertNull($booking->user_id);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertNull($booking->active_enrollment_key);

        $this->actingAs($learner)
            ->get('/recorded/lecture/'.$course->id.'/'.$lecture->id)
            ->assertForbidden();
    }

    public function test_vimeo_failure_does_not_dump_the_exception(): void
    {
        $entries = [];
        Log::listen(function ($event) use (&$entries) {
            $entries[] = $event->message.' '.json_encode($event->context);
        });
        Vimeo::shouldReceive('request')
            ->andThrow(new \RuntimeException('vimeo-secret-should-not-leak'));

        $admin = $this->makeUser('video-admin@example.test', Role::ADMIN);
        $course = $this->makeCourse($admin);
        $lecture = Lecture::query()->create([
            'course_id' => $course->id,
            'title' => 'Video lecture',
            'description' => 'Lecture description',
            'video_file' => 'video-secret',
            'order' => 1,
        ]);

        $component = Livewire::actingAs($admin)->test(RecordedLecture::class, [
            'courseId' => $course->id,
            'lectureId' => $lecture->id,
        ]);

        $component->assertSet('videoDetails', []);
        $encoded = json_encode($component->effects);
        $this->assertStringNotContainsString('vimeo-secret-should-not-leak', (string) $encoded);
        $logged = implode("\n", $entries);
        $this->assertStringContainsString('Recorded lecture video details could not be loaded.', $logged);
        $this->assertStringNotContainsString('vimeo-secret-should-not-leak', $logged);
    }

    public function test_dashboard_presentation_uses_role_names_when_ids_are_swapped(): void
    {
        $now = now();
        Role::query()->delete();
        DB::table('roles')->insert([
            ['id' => 1, 'role_name' => Role::TUTOR, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'role_name' => Role::LEARNER, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'role_name' => 'Decoy', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'role_name' => Role::ADMIN, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $tutor = $this->makeUser('role-tutor@example.test', 1);
        $learner = $this->makeUser('role-learner@example.test', 2);
        $admin = $this->makeUser('role-admin@example.test', 4);

        Livewire::actingAs($tutor)->test(DashbordSidebar::class)
            ->assertSee('Help Request')
            ->assertSee('Instructor')
            ->assertDontSee('Payment reconciliation');

        Livewire::actingAs($learner)->test(DashbordSidebar::class)
            ->assertSee('Help Request')
            ->assertDontSee('Instructor')
            ->assertDontSee('Payment reconciliation');

        Livewire::actingAs($admin)->test(DashbordSidebar::class)
            ->assertDontSee('Help Request')
            ->assertSee('Instructor')
            ->assertSee('Payment reconciliation');

        Livewire::actingAs($tutor)->test(DashboardHead::class)
            ->assertSee('Become an Instructor')
            ->assertDontSee('Create Course');

        Livewire::actingAs($admin)->test(DashboardHead::class)
            ->assertSee('Create Course');

        $this->actingAs($tutor);
        $tutorDashboard = view('livewire.dashboard.dashboard', $this->dashboardData())->render();
        $this->assertStringContainsString('Total Student', $tutorDashboard);
        $this->assertStringContainsString('My Courses', $tutorDashboard);

        $this->actingAs($learner);
        $learnerDashboard = view('livewire.dashboard.dashboard', $this->dashboardData())->render();
        $this->assertStringNotContainsString('Total Student', $learnerDashboard);
        $this->assertStringNotContainsString('My Courses', $learnerDashboard);
    }

    private function dashboardData(): array
    {
        return [
            'studentCount' => 0,
            'totalCourses' => 0,
            'bookedCoursesCount' => 0,
            'inProgressCoursesCount' => 0,
            'completedCoursesCount' => 0,
            'inProgressCourses' => collect(),
            'courses' => collect(),
        ];
    }

    private function makeCourse(User $owner): Course
    {
        return Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'paid',
            'title' => 'Hardening course '.bin2hex(random_bytes(3)),
            'description' => 'Description',
            'thumbnail' => 'thumb.png',
            'video_path' => 'promo.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
        ]);
    }

    private function makeUser(string $email, string|int $role): User
    {
        $roleId = is_int($role) ? $role : Role::findByName($role)->id;

        return User::query()->create([
            'email' => str_replace('@', '-'.bin2hex(random_bytes(3)).'@', $email),
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'role_id' => $roleId,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
    }
}
