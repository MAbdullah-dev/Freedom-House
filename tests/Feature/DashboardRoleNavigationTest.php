<?php

namespace Tests\Feature;

use App\Livewire\CourseAnalytics\CourseDetail;
use App\Livewire\Dashboard\StudentView;
use App\Livewire\Inc\DashbordSidebar;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardRoleNavigationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sidebar_links_follow_the_signed_in_role(): void
    {
        Livewire::actingAs($this->makeUser(Role::LEARNER))
            ->test(DashbordSidebar::class)
            ->assertSee('Enrolled Courses')
            ->assertSee('Wishlist')
            ->assertDontSee('View Enrollments')
            ->assertDontSee('Payment reconciliation')
            ->assertDontSee('Tutor Requests')
            ->assertDontSee('My Courses');

        Livewire::actingAs($this->makeUser(Role::TUTOR))
            ->test(DashbordSidebar::class)
            ->assertSee('View Enrollments')
            ->assertSee('My Courses')
            ->assertSee('Course Analytics')
            ->assertDontSee('Payment reconciliation')
            ->assertDontSee('Tutor Requests')
            ->assertDontSee('Instructors')
            ->assertDontSee('Admins');

        Livewire::actingAs($this->makeUser(Role::ADMIN))
            ->test(DashbordSidebar::class)
            ->assertSee('View Enrollments')
            ->assertSee('Payment reconciliation')
            ->assertSee('Tutor Requests')
            ->assertSee('Categories')
            ->assertDontSee('Instructors')
            ->assertDontSee('Admins');
    }

    public function test_student_view_shows_the_stored_learner_and_not_sample_branding(): void
    {
        $admin = $this->makeUser(Role::ADMIN);
        $learner = $this->makeUser(Role::LEARNER);

        Livewire::actingAs($admin)
            ->test(StudentView::class, ['id' => $learner->id])
            ->assertSee($learner->email)
            ->assertSee($learner->first_name)
            ->assertSee('This student has no enrolled courses.')
            ->assertDontSee('freedomhousetraining.org')
            ->assertDontSee('Bilal Khalid');

        Livewire::actingAs($learner)
            ->test(StudentView::class, ['id' => $learner->id])
            ->assertForbidden();

        $otherLearner = $this->makeUser(Role::LEARNER);
        Livewire::actingAs($otherLearner)
            ->test(StudentView::class, ['id' => $learner->id])
            ->assertForbidden();

        Livewire::actingAs($this->makeUser(Role::TUTOR))
            ->test(StudentView::class, ['id' => $learner->id])
            ->assertForbidden();
    }

    public function test_course_analytics_detail_does_not_show_sample_people(): void
    {
        Livewire::actingAs($this->makeUser(Role::TUTOR))
            ->test(CourseDetail::class)
            ->assertSee('No enrollment rows are loaded on this page.')
            ->assertDontSee('freedomhouserecovery.org')
            ->assertDontSee('Clinical Documentation');
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['role_name' => $roleName]);

        return User::query()->create([
            'email' => strtolower($roleName).'-'.uniqid().'@example.test',
            'first_name' => $roleName,
            'last_name' => 'Fixture',
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }
}
