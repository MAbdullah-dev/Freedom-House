<?php

namespace Tests\Feature;

use App\Livewire\AboutUs;
use App\Livewire\CourseCatelogue;
use App\Livewire\CoursesCards\InstructorCards;
use App\Livewire\CoursesCards\MostPopularCards;
use App\Livewire\Home;
use App\Livewire\TutorPrfile;
use App\Models\Category;
use App\Models\Course;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_cards_link_recorded_and_scheduled_courses(): void
    {
        $owner = $this->makeUser('catalog-owner@example.test');
        $recorded = $this->makeCourse($owner, 'Catalog recorded '.uniqid(), 'recorded', '');
        $scheduled = $this->makeCourse($owner, 'Catalog class '.uniqid(), 'classtype', 'thumb.webp');

        $recordedHtml = view('components.public-course-card', ['course' => $recorded->load('user')])->render();
        $scheduledHtml = view('components.public-course-card', ['course' => $scheduled->load('user')])->render();

        $this->assertStringContainsString(route('onsite.course.details', $recorded->id), $recordedHtml);
        $this->assertStringContainsString('Image not available', $recordedHtml);
        $this->assertStringContainsString(route('course.details', $scheduled->id), $scheduledHtml);
        $this->assertStringContainsString('thumb.webp', $scheduledHtml);
        $this->assertStringNotContainsString('Freedom House', $recordedHtml.$scheduledHtml);

        Livewire::test(Home::class)
            ->assertSee('Stellix Soft LMS')
            ->assertSee($recorded->title)
            ->assertSee($scheduled->title)
            ->assertDontSee('Freedom House');
    }

    public function test_catalog_search_and_category_filter_keep_their_actions(): void
    {
        $owner = $this->makeUser('catalog-filter@example.test');
        $category = Category::query()->create(['category_name' => 'Catalog filter '.uniqid()]);
        $match = $this->makeCourse($owner, 'Filter match '.uniqid(), 'classtype', 'cover.webp');
        $other = $this->makeCourse($owner, 'Filter other '.uniqid(), 'classtype', 'cover.webp');
        $match->categories()->attach($category->id);

        Livewire::test(CourseCatelogue::class)
            ->set('search', $match->title)
            ->assertSee($match->title)
            ->assertDontSee($other->title)
            ->set('search', '')
            ->call('selectedCategorieschanged', $category->id)
            ->assertSee($match->title)
            ->assertDontSee($other->title)
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('selectedCategories', []);
    }

    public function test_wishlist_toggle_adds_and_removes_the_course(): void
    {
        $learner = $this->makeUser('catalog-learner@example.test');
        $owner = $this->makeUser('catalog-seller@example.test');
        $course = $this->makeCourse($owner, 'Wishlist course '.uniqid(), 'recorded', 'cover.webp');

        $component = Livewire::actingAs($learner)->test(MostPopularCards::class);

        $component->call('toggleWishlist', $course->id);
        $this->assertTrue($learner->wishlist()->where('course_id', $course->id)->exists());

        $component->call('toggleWishlist', $course->id);
        $this->assertFalse($learner->fresh()->wishlist()->where('course_id', $course->id)->exists());
    }

    public function test_tutor_cards_and_about_page_link_to_the_tutor_profile(): void
    {
        $user = $this->makeUser('catalog-tutor@example.test');
        Tutor::query()->create([
            'user_id' => $user->id,
            'specialization' => 'Software training',
            'preferred_teaching_method' => 'online',
            'is_verified' => true,
        ]);

        Livewire::test(InstructorCards::class)
            ->assertSee($user->first_name)
            ->assertSee(route('tutor.profile', $user->id));

        Livewire::test(AboutUs::class)
            ->assertSee('Stellix Soft LMS')
            ->assertSee('View profile')
            ->assertDontSee('Freedom House');

        Livewire::test(TutorPrfile::class, ['id' => $user->id])
            ->assertSee($user->first_name)
            ->assertSee('No courses are listed for this tutor yet.');
    }

    public function test_empty_public_lists_explain_that_records_are_missing(): void
    {
        Livewire::test(Home::class)
            ->set('courses', collect())
            ->assertSee('No published courses are available yet.');

        Livewire::test(InstructorCards::class)
            ->set('featuredTutors', collect())
            ->assertSee('No tutor profiles are available yet.');
    }

    private function makeUser(string $email): User
    {
        $role = Role::query()->firstOrCreate(['role_name' => Role::LEARNER]);

        return User::query()->create([
            'email' => $email,
            'first_name' => 'Catalog',
            'last_name' => 'Fixture',
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }

    private function makeCourse(User $owner, string $title, string $type, string $thumbnail): Course
    {
        return Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => $type,
            'is_paid' => 'free',
            'title' => $title,
            'description' => 'Public catalog fixture',
            'thumbnail' => $thumbnail,
            'video_path' => 'video.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
        ]);
    }
}
