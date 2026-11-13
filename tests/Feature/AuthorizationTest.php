<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckRole;
use App\Livewire\Dashboard\Category;
use App\Livewire\Dashboard\CoursesRequest;
use App\Livewire\Dashboard\HelpRequestsAdmin;
use App\Livewire\Dashboard\MyCourses;
use App\Livewire\Dashboard\Student;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $tutor;

    private User $learner;

    private User $decoy;

    private User $unassigned;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();

        Role::query()->delete();

        DB::table('roles')->insert([
            ['id' => 1, 'role_name' => Role::TUTOR, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'role_name' => Role::LEARNER, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'role_name' => 'Decoy', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'role_name' => Role::ADMIN, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->tutor = $this->makeUser('auth-tutor@example.test', 1);
        $this->learner = $this->makeUser('auth-learner@example.test', 2);
        $this->decoy = $this->makeUser('auth-decoy@example.test', 3);
        $this->admin = $this->makeUser('auth-admin@example.test', 4);
        $this->unassigned = $this->makeUser('auth-missing@example.test', 0);
    }

    public function test_guests_are_redirected_from_private_dashboards(): void
    {
        foreach ([
            '/dashboard',
            '/dashboard/settings',
            '/dashboard/categories',
            '/dashboard/students',
            '/dashboard/mycourses',
            '/dashboard/enrollments',
            '/quizzes/create/1',
            '/payment/success',
        ] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_public_pages_stay_available_to_guests(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/course/catalogue')->assertOk();
    }

    public function test_learners_cannot_open_tutor_or_admin_routes(): void
    {
        $this->actingAs($this->learner);

        foreach ([
            '/dashboard/categories',
            '/dashboard/students',
            '/dashboard/tutor/Request',
            '/dashboard/emails',
            '/dashboard/mycourses',
            '/dashboard/lectures',
            '/dashboard/enrollments',
            '/quizzes/create/1',
        ] as $uri) {
            $this->get($uri)->assertForbidden();
        }

        $this->get('/dashboard/settings')->assertOk();
    }

    public function test_tutors_cannot_open_admin_routes(): void
    {
        $this->actingAs($this->tutor);

        foreach ([
            '/dashboard/categories',
            '/dashboard/tags',
            '/dashboard/students',
            '/dashboard/coursesRequest',
            '/dashboard/emails',
            '/dashboard/help/Request/Admin',
        ] as $uri) {
            $this->get($uri)->assertForbidden();
        }
    }

    public function test_admins_can_open_admin_pages_and_authenticated_users_can_open_settings(): void
    {
        $this->actingAs($this->admin)->get('/dashboard/categories')->assertOk();
        $this->actingAs($this->tutor)->get('/dashboard/mycourses')->assertOk();
        $this->actingAs($this->learner)->get('/dashboard/settings')->assertOk();
    }

    public function test_missing_and_unknown_roles_fail_closed(): void
    {
        $this->assertNull($this->unassigned->role);
        $this->assertSame('Decoy', $this->decoy->role->role_name);

        foreach ([$this->unassigned, $this->decoy] as $user) {
            $this->actingAs($user);
            $this->get('/dashboard/categories')->assertForbidden();
            $this->get('/dashboard/mycourses')->assertForbidden();
            $this->assertSame(403, $this->middleware($user, Role::ADMIN)->getStatusCode());
            $this->assertSame(403, $this->middleware($user, Role::TUTOR, Role::ADMIN)->getStatusCode());
        }

        $request = Request::create('/dashboard/categories', 'GET');
        $response = app(CheckRole::class)->handle($request, fn () => response('ok'), Role::ADMIN);

        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertSame(403, $this->middleware($this->admin)->getStatusCode());
        $this->assertSame(403, $this->middleware($this->admin, 'admin')->getStatusCode());
    }

    public function test_role_checks_do_not_depend_on_ids_one_two_or_three(): void
    {
        $this->assertSame(1, Role::findByName(Role::TUTOR)->id);
        $this->assertSame(2, Role::findByName(Role::LEARNER)->id);
        $this->assertSame(3, Role::findByName('Decoy')->id);
        $this->assertSame(4, Role::findByName(Role::ADMIN)->id);

        $this->actingAs($this->tutor)->get('/dashboard/categories')->assertForbidden();
        $this->actingAs($this->learner)->get('/dashboard/mycourses')->assertForbidden();
        $this->actingAs($this->admin)->get('/dashboard/categories')->assertOk();

        $this->assertSame(200, $this->middleware($this->admin, Role::ADMIN)->getStatusCode());
        $this->assertSame(403, $this->middleware($this->tutor, Role::ADMIN)->getStatusCode());
        $this->assertSame(200, $this->middleware($this->tutor, Role::TUTOR, Role::ADMIN)->getStatusCode());
        $this->assertSame(403, $this->middleware($this->learner, Role::TUTOR, Role::ADMIN)->getStatusCode());
    }

    public function test_direct_livewire_calls_cannot_bypass_admin_authorization(): void
    {
        $course = $this->makeCourse($this->tutor, false);
        $categoryCount = DB::table('categories')->count();
        $helpId = DB::table('requests')->insertGetId([
            'user_id' => $this->learner->id,
            'subject' => 'Need help',
            'request_detail' => 'Details',
            'status' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $statusBefore = DB::table('requests')->where('id', $helpId)->value('status');

        Livewire::actingAs($this->tutor)->test(Category::class)->assertForbidden();
        Livewire::actingAs($this->learner)->test(CoursesRequest::class)->assertForbidden();
        Livewire::actingAs($this->tutor)->test(Student::class)->assertForbidden();
        Livewire::actingAs($this->learner)->test(HelpRequestsAdmin::class)->assertForbidden();

        $this->assertSame($categoryCount, DB::table('categories')->count());
        $this->assertDatabaseMissing('categories', ['category_name' => 'Blocked Category']);
        $this->assertFalse((bool) $course->fresh()->is_published);
        $this->assertSame(2, $this->learner->fresh()->role_id);
        $this->assertNull($this->learner->fresh()->deleted_at);
        $this->assertSame($statusBefore, DB::table('requests')->where('id', $helpId)->value('status'));
    }

    public function test_learners_cannot_delete_courses_through_livewire(): void
    {
        $course = $this->makeCourse($this->tutor, false);

        Livewire::actingAs($this->learner)->test(MyCourses::class)->assertForbidden();

        $this->assertNotNull(Course::query()->find($course->id));
    }

    public function test_admins_can_run_authorized_actions_without_using_role_ids(): void
    {
        $course = $this->makeCourse($this->tutor, false);

        Livewire::actingAs($this->admin)
            ->test(Category::class)
            ->set('category_name', 'Auth Category')
            ->call('createCategory');

        Livewire::actingAs($this->admin)
            ->test(CoursesRequest::class)
            ->call('approveCourse', $course->id);

        Livewire::actingAs($this->admin)
            ->test(Student::class)
            ->call('promoteUser', $this->learner->id, Role::TUTOR);

        $this->assertDatabaseHas('categories', ['category_name' => 'Auth Category']);
        $this->assertTrue((bool) $course->fresh()->is_published);
        $this->assertTrue($this->learner->fresh()->hasRoleName(Role::TUTOR));
        $this->assertSame(1, $this->learner->fresh()->role_id);
    }

    public function test_promotion_rejects_a_numeric_role_id_before_saving(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Student::class)
            ->call('promoteUser', $this->learner->id, 2)
            ->assertForbidden();

        $this->assertTrue($this->learner->fresh()->hasRoleName(Role::LEARNER));
        $this->assertSame(2, $this->learner->fresh()->role_id);
    }

    private function makeUser(string $email, int $roleId): User
    {
        return User::query()->create([
            'email' => $email,
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'role_id' => $roleId,
            'email_verified_at' => now(),
        ]);
    }

    private function makeCourse(User $owner, bool $published): Course
    {
        return Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'free',
            'title' => 'Auth boundary '.$owner->id.'-'.bin2hex(random_bytes(3)),
            'description' => 'Boundary',
            'thumbnail' => 'thumb.png',
            'video_path' => 'video.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => $published,
            'is_drafted' => false,
        ]);
    }

    private function middleware(User $user, string ...$roles): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create('/dashboard/categories', 'GET');
        $request->setUserResolver(fn () => $user);

        try {
            return app(CheckRole::class)->handle(
                $request,
                fn () => response('ok'),
                ...$roles
            );
        } catch (HttpException $exception) {
            return response('', $exception->getStatusCode());
        }
    }
}
