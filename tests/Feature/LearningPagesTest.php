<?php

namespace Tests\Feature;

use App\Livewire\Login;
use App\Livewire\OnSiteCourseDetails;
use App\Livewire\Register;
use App\Models\Category;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LearningPagesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_recorded_course_details_show_the_stored_description_and_enrollment_action(): void
    {
        $owner = $this->makeUser();
        $course = Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'free',
            'title' => 'Learning page '.uniqid(),
            'description' => 'Stored course description',
            'thumbnail' => '',
            'video_path' => 'video.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
            'price' => 0,
        ]);

        Livewire::test(OnSiteCourseDetails::class, ['id' => $course->id])
            ->assertSee($course->title)
            ->assertSee('Stored course description')
            ->assertSee('Enroll now')
            ->assertSee('wire:click="enrollCourse"', false)
            ->assertDontSee('personality disorders')
            ->assertDontSee('Freedom House');
    }

    public function test_free_and_paid_labels_follow_the_stored_price_and_categories_stay_visible(): void
    {
        $owner = $this->makeUser();
        $free = $this->makeCourse($owner, 'Free learning '.uniqid(), 0);
        $paid = $this->makeCourse($owner, 'Paid learning '.uniqid(), 19.5);
        $category = Category::query()->create(['category_name' => 'Learning category '.uniqid()]);
        $paid->categories()->attach($category->id);

        Livewire::test(OnSiteCourseDetails::class, ['id' => $free->id])
            ->assertSee($free->title)
            ->assertSee('FREE')
            ->assertSee('This course is free.')
            ->assertDontSee('$19.50');

        Livewire::test(OnSiteCourseDetails::class, ['id' => $paid->id])
            ->assertSee($paid->title)
            ->assertSee('$19.50')
            ->assertSee($category->category_name)
            ->assertDontSee('This course is free.');
    }

    public function test_authentication_forms_keep_their_submit_actions_and_microsoft_route(): void
    {
        Livewire::test(Login::class)
            ->assertSee('wire:submit.prevent="login"', false)
            ->assertSee('wire:submit.prevent="sendPasswordResetLink"', false)
            ->assertSee(route('microsoft.redirect'))
            ->assertSee('Continue with Microsoft');

        Livewire::test(Register::class)
            ->assertSee('wire:submit.prevent="register"', false)
            ->assertSee(route('microsoft.redirect'));

        $this->get(route('password.reset', ['token' => 'token', 'email' => 'learner@example.test']))
            ->assertOk()
            ->assertSee(route('password.update'), false)
            ->assertSee('name="password"', false)
            ->assertSee('Choose a new password');
    }

    private function makeCourse(User $owner, string $title, float $price): Course
    {
        return Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => $price > 0 ? 'paid' : 'free',
            'title' => $title,
            'description' => 'Stored course description',
            'thumbnail' => '',
            'video_path' => 'video.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
            'price' => $price,
        ]);
    }

    private function makeUser(): User
    {
        $role = Role::query()->firstOrCreate(['role_name' => Role::LEARNER]);

        return User::query()->create([
            'email' => 'learning-'.uniqid().'@example.test',
            'first_name' => 'Learning',
            'last_name' => 'Fixture',
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }
}
